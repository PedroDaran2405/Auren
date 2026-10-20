<?php
defined('ABSPATH') || exit;

function auren_app_profile() {
    $response=auren_app_buddyboss_request('GET','/buddyboss/v1/xprofile/groups',array('context'=>'view','user_id'=>get_current_user_id(),'fetch_fields'=>true,'fetch_field_data'=>true,'fetch_visibility_level'=>true,'hide_empty_fields'=>false));
    if(is_wp_error($response)){return $response;}$fields=array();
    foreach((array)$response->get_data() as $group){foreach(($group['fields']??array()) as $field){
        $type=$field['type']??'';$multi=in_array($type,array('checkbox','multiselectbox'),true);$raw=$multi?($field['data']['value']['unserialized']??array()):($field['data']['value']['raw']??$field['data']['value']??'');
        if($multi){$raw=is_array($raw)?array_values(array_filter($raw,'is_string')):(is_string($raw)&&$raw!==''?array($raw):array());}
        elseif(!is_string($raw)){$raw=is_numeric($raw)?(string)$raw:'';}
        if((!is_string($raw)&&!is_array($raw)) || !in_array($type,array('textbox','textarea','number','url','telephone','selectbox','radio','checkbox','multiselectbox'),true)){continue;}
        $options=array();foreach(($field['options']??array()) as $opt){$options[]=array('value'=>auren_app_plain_content($opt['name']??''),'label'=>auren_app_plain_content($opt['name']??''));}
        // Member types, usernames and email fields are managed by WordPress, never by this editor.
        if(function_exists('bp_get_xprofile_member_type_field_id') && (int)$field['id']===(int)bp_get_xprofile_member_type_field_id()){continue;}
        $fields[]=array('id'=>(int)$field['id'],'group_id'=>(int)$group['id'],'group'=>auren_app_plain_content($group['name']??''),'label'=>auren_app_plain_content($field['name']??''),'type'=>$type,'required'=>!empty($field['is_required']),'value'=>$raw,'options'=>$options);
    }}
    return array('fields'=>$fields);
}

function auren_app_save_profile($r) {
    $input=$r->get_param('fields');if(!is_array($input)||!count($input)||count($input)>50){return auren_app_error('invalid_content','Revisa los campos del perfil.',400);}
    return auren_app_write(function()use($input){
        $current=auren_app_profile();if(is_wp_error($current)){return $current;}$allowed=array_column($current['fields'],null,'id');$changes=array();$seen=array();
        foreach($input as $item){
            $id=is_array($item)?absint($item['id']??0):0;$field=$allowed[$id]??null;$value=$item['value']??null;
            if(isset($seen[$id])){return auren_app_error('invalid_content','Hay un campo repetido en el perfil.',400);}$seen[$id]=true;
            $multi=$field&&in_array($field['type'],array('checkbox','multiselectbox'),true);
            $valid=$multi?is_array($value)&&count($value)<=50&&!array_filter($value,static fn($v)=>!is_string($v)||mb_strlen($v)>2000):is_string($value)&&mb_strlen($value)<=2000;
            if(!$field||!$valid||($field['required']&&($multi?!count($value):trim($value)===''))){return auren_app_error('invalid_content','Revisa los campos y sus valores antes de guardar.',400);}
            if(($multi&&array_diff($value,array_column($field['options'],'value')))||(!$multi&&in_array($field['type'],array('selectbox','radio'),true)&&$value!==''&&!in_array($value,array_column($field['options'],'value'),true))){return auren_app_error('invalid_content','Elige una opción válida.',400);}
            if($value===$field['value']){continue;}
            $changes[]=array('field_id'=>$id,'group_id'=>$field['group_id'],'type'=>$field['type'],'value'=>$multi?array_map('sanitize_text_field',$value):sanitize_textarea_field($value));
        }
        if($changes && class_exists('BP_REST_XProfile_Update_Endpoint')){
            $validator=new BP_REST_XProfile_Update_Endpoint();
            foreach($changes as $change){if($validator->validate_update($change['field_id'],get_current_user_id(),$change['value'])){return auren_app_error('profile_validation','Revisa los campos del perfil antes de guardar.',400);}}
        }
        if($changes){
            $response=auren_app_buddyboss_request('PATCH','/buddyboss/v1/xprofile/update',array('fields'=>$changes));if(is_wp_error($response)){return $response;}
            if(($response->get_data()['updated']??false)!==true){return auren_app_error('profile_validation','WordPress rechazó alguno de los campos. Actualiza el perfil para revisar lo guardado.',400);}
        }
        return auren_app_profile();
    });
}

function auren_app_members($page,$search='') {
    $response=auren_app_buddyboss_request('GET','/buddyboss/v1/members',array('context'=>'view','page'=>$page,'per_page'=>12,'search'=>$search,'type'=>'alphabetical','populate_extras'=>false));
    if(is_wp_error($response)){return $response;}$items=array();
    foreach((array)$response->get_data() as $data){$items[]=auren_app_member_data($data);}
    return auren_app_collection($response,$items,$page);
}
function auren_app_member_data($data) {
    return array('id'=>absint($data['id']??0),'name'=>auren_app_plain_content($data['name']??''),'avatar_url'=>auren_app_safe_url($data['avatar_urls']['full']??$data['avatar_urls']['thumb']??''),'joined'=>substr((string)($data['registered_date']??''),0,10));
}
function auren_app_member($id) {
    $response=auren_app_buddyboss_request('GET','/buddyboss/v1/members/'.$id,array('context'=>'view','populate_extras'=>false));
    if(is_wp_error($response)){return $response;}$data=$response->get_data();$result=auren_app_member_data($data);$result['fields']=array();
    $hidden=function_exists('bp_xprofile_get_hidden_fields_for_user')?bp_xprofile_get_hidden_fields_for_user($id,get_current_user_id()):null;
    foreach(($data['xprofile']['groups']??array()) as $group){foreach(($group['fields']??array()) as $field_id=>$field){
        if($hidden===null||in_array((int)$field_id,array_map('intval',$hidden),true)){continue;}
        $raw=$field['value']['rendered']??$field['value']['raw']??'';
        if(is_string($raw)&&trim($raw)!==''){$result['fields'][]=array('label'=>auren_app_plain_content($field['name']??''),'value'=>auren_app_plain_content($raw));}
    }}
    return $result;
}
