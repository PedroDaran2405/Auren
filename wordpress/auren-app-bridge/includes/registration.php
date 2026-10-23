<?php
defined('ABSPATH') || exit;

function auren_app_registration_url(){
    return add_query_arg(array('auren_app_auth'=>'1','redirect_to'=>add_query_arg('auren_app','1',home_url('/'))),wp_registration_url());
}
function auren_app_registration_enabled(){
    $role=get_role(get_option('default_role','subscriber'));
    return (bool)get_option('users_can_register') && function_exists('bp_core_signup_user') && $role && !$role->has_cap('manage_options') && !$role->has_cap('promote_users') && !$role->has_cap('install_plugins');
}
function auren_app_registration_native_only(){
    // Do not sidestep a CAPTCHA configured for the original registration form.
    return function_exists('bb_recaptcha_is_enabled') && bb_recaptcha_is_enabled('bb_register');
}
function auren_app_registration_challenge($issued=null,$random=null){
    $payload=($issued??time()).'.'.($random??bin2hex(random_bytes(16)));
    return $payload.'.'.hash_hmac('sha256',$payload.'|'.($_SERVER['REMOTE_ADDR']??''),wp_salt('nonce'));
}
function auren_app_registration_fields(){
    $response=auren_app_buddyboss_request('GET','/buddyboss/v1/signup/form');
    if(is_wp_error($response)){return $response;}
    $fields=array();
    foreach($response->get_data() as $field){
        $id=(string)($field['id']??'');
        if(!preg_match('/^(signup_(email|email_confirm|password|password_confirm|username)|field_[1-9][0-9]*|legal_agreement)$/D',$id)){continue;}
        $options=array();
        foreach(($field['options']??array()) as $option){
            if(!is_array($option)){continue;}
            $value=(string)($option['value']??$option['name']??'');
            $label=(string)($option['label']??$option['name']??$value);
            $options[]=array('value'=>$value,'label'=>auren_app_plain_content($label));
        }
        $fields[]=array('id'=>$id,'label'=>auren_app_plain_content($field['label']??''),'type'=>(string)($field['type']??''),'required'=>!empty($field['required']),'options'=>$options);
    }
    return $fields;
}
function auren_app_registration_supported(){return array('email','password','textbox','textarea','number','url','telephone','checkbox','radio','selectbox','multiselectbox');}
function auren_app_registration_config(){
    if(!auren_app_secure_transport()){return auren_app_error('https_required','Se requiere HTTPS.',503);}
    $enabled=auren_app_registration_enabled();$fields=$enabled?auren_app_registration_fields():array();
    if(is_wp_error($fields)){return $fields;}
    $supported=auren_app_registration_supported();
    $native=auren_app_registration_native_only() || (bool)array_filter($fields,static fn($f)=>!in_array($f['type'],$supported,true));
    return array('enabled'=>$enabled,'native_form_only'=>$native,'url'=>auren_app_registration_url(),'fields'=>$fields,
        'challenge'=>$enabled&&!$native?auren_app_registration_challenge():null,
        'privacy_url'=>get_privacy_policy_url(),'terms_url'=>function_exists('bp_core_get_directory_page_ids')?get_permalink(bp_core_get_directory_page_ids()['terms']??0):'');
}
function auren_app_registration_rate_limit($ip){
    global $wpdb;
    $lock=$ip.'_lock';$token=time().':'.wp_generate_uuid4();$before=get_option($lock,false);
    if($before!==false && (int)$before<time()-30){$wpdb->delete($wpdb->options,array('option_name'=>$lock,'option_value'=>$before));wp_cache_delete($lock,'options');}
    if(!add_option($lock,$token,'',false)){return auren_app_error('rate_limited','Espera un momento antes de volver a intentar.',429);}
    try{
        $count=(int)get_transient($ip);
        if($count>=4){return auren_app_error('rate_limited','Espera unos minutos antes de volver a registrarte.',429);}
        set_transient($ip,$count+1,10*MINUTE_IN_SECONDS);return true;
    }finally{$wpdb->delete($wpdb->options,array('option_name'=>$lock,'option_value'=>$token));wp_cache_delete($lock,'options');}
}
function auren_app_register($request){
    if(!auren_app_secure_transport()){return auren_app_error('https_required','Se requiere HTTPS.',503);}
    if(!auren_app_registration_enabled()){return auren_app_error('registration_disabled','El registro no está disponible por ahora.',403);}
    if(auren_app_registration_native_only()){return auren_app_error('registration_web_required','Completa el registro protegido en la academia.',409);}
    if(strlen($request->get_body())>32768){return auren_app_error('invalid_registration','El formulario es demasiado grande.',400);}
    $params=$request->get_json_params();
    if(!is_array($params) || array_diff(array_keys($params),array('fields','challenge','website')) || !empty($params['website'])){return auren_app_error('invalid_registration','Revisa el formulario de registro.',400);}
    $challenge=$params['challenge']??'';$parts=is_string($challenge)?explode('.',$challenge):array();
    if(count($parts)!==3 || !ctype_digit($parts[0]) || !preg_match('/^[a-f0-9]{32}$/D',$parts[1]) || (int)$parts[0]>time()+5 || (int)$parts[0]<time()-600 || !hash_equals(auren_app_registration_challenge((int)$parts[0],$parts[1]),$challenge)){
        return auren_app_error('registration_expired','Este formulario venció. Vuelve a abrir el registro.',400);
    }
    // One use, bounded requests, no trust in caller-provided forwarding headers.
    $ip='auren_signup_ip_'.hash('sha256',$_SERVER['REMOTE_ADDR']??'unknown');
    $limit=auren_app_registration_rate_limit($ip);if(is_wp_error($limit)){return $limit;}
    $key='auren_signup_'.hash('sha256',$challenge);
    if(!add_option($key,(string)time(),'',false)){return auren_app_error('duplicate_request','La solicitud ya se envió. Revisa tu correo antes de repetirla.',409);}
    $fields=auren_app_registration_fields();if(is_wp_error($fields)){delete_option($key);return $fields;}
    $values=$params['fields']??null;$allowed=array_column($fields,'id');
    if(!is_array($values) || array_diff(array_keys($values),$allowed)){delete_option($key);return auren_app_error('invalid_registration','Revisa los campos del registro.',400);}
    foreach($values as $value){if((!is_string($value)&&!is_array($value)) || (is_string($value)&&strlen($value)>2000) || (is_array($value)&&(count($value)>50 || array_filter($value,static fn($v)=>!is_string($v)||strlen($v)>2000)))){delete_option($key);return auren_app_error('invalid_registration','Revisa los campos del registro.',400);}}
    foreach($fields as $field){
        if(!in_array($field['type'],auren_app_registration_supported(),true)){delete_option($key);return auren_app_error('registration_web_required','Completa este registro en la academia.',409);}
        $value=$values[$field['id']]??'';
        $multi=in_array($field['type'],array('checkbox','multiselectbox'),true)&&count($field['options']);
        if(($multi&&!is_array($value))||(!$multi&&!is_string($value))){delete_option($key);return auren_app_error('invalid_registration','Revisa los campos del registro.',400);}
        $missing=is_array($value)?!count($value):(!is_string($value)||trim($value)==='');
        if($missing&&!$field['required']){continue;}
        if(($field['required']&&$missing) || ($field['id']==='legal_agreement'&&$value!=='1') || ($field['type']==='email'&&(!is_string($value)||!is_email($value))) || ($field['type']==='password'&&(!is_string($value)||mb_strlen($value)<12))){delete_option($key);return auren_app_error('invalid_registration','Completa los campos requeridos y usa una contraseña de al menos 12 caracteres.',400);}
        if($field['options'] && !$missing){$selected=is_array($value)?$value:array($value);if(array_diff($selected,array_column($field['options'],'value'))){delete_option($key);return auren_app_error('invalid_registration','Selecciona una opción válida.',400);}}
    }

    $previous=$_POST;
    try{$result=auren_app_buddyboss_request('POST','/buddyboss/v1/signup',$values);}finally{$_POST=$previous;}
    if(is_wp_error($result)){delete_option($key);return auren_app_error('registration_validation','No se pudo completar el registro. Revisa los campos; si ya tienes cuenta, inicia sesión o recupera tu acceso.',400);}
    // Never return user IDs, password hashes, activation links, or platform internals.
    return array('submitted'=>true,'message'=>'Tu solicitud se envió. Revisa tu correo y sigue las indicaciones de Auren para completar el acceso.');
}
// Remove expired replay markers without loading or recording any email/password.
add_action('auren_app_cleanup_signups',function(){global $wpdb;$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s AND CAST(option_value AS UNSIGNED) < %d",$wpdb->esc_like('auren_signup_').'%',time()-DAY_IN_SECONDS));});
add_action('init',function(){if(!wp_next_scheduled('auren_app_cleanup_signups'))wp_schedule_event(time()+HOUR_IN_SECONDS,'daily','auren_app_cleanup_signups');});
