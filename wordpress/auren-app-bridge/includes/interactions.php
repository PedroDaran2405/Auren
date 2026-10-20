<?php
defined('ABSPATH') || exit;

/** Fixed internal routes only. Keep all native BuddyBoss permission/validation hooks. */
function auren_app_buddyboss_request($method,$route,$params=array()) {
    $request=new WP_REST_Request($method,$route);
    foreach($params as $key=>$value){$request->set_param($key,$value);}
    // Header authentication can run after BuddyBoss has cached its logged-in user.
    $bp=function_exists('buddypress')?buddypress():null;$previous=$bp->loggedin_user??null;
    $displayed=$bp->displayed_user??null;
    if($bp && $previous){$bp->loggedin_user=clone $previous;$bp->loggedin_user->id=get_current_user_id();}
    if($bp && $displayed){$bp->displayed_user=clone $displayed;}
    try{$response=rest_do_request($request);}finally{if($bp && $previous){$bp->loggedin_user=$previous;}if($bp && $displayed){$bp->displayed_user=$displayed;}}
    if($response->is_error()){
        $status=$response->get_status();
        return auren_app_error('community_unavailable','No se pudo completar la operación. Revisa tus permisos y vuelve a intentarlo.',$status>=400&&$status<500?$status:503);
    }
    // xProfile embeds stdClass values. Match the JSON representation of a real REST
    // response before adapting it; internal dispatch does not perform serialization.
    $json=wp_json_encode($response->get_data());
    if($json===false){return auren_app_error('invalid_response','No pudimos leer la respuesta de la plataforma.',502);}
    $response->set_data(json_decode($json,true));
    return $response;
}

function auren_app_text_input($value,$limit=5000) {
    if(!is_string($value)){return auren_app_error('invalid_content','Escribe un texto válido.',400);}
    $text=trim(sanitize_textarea_field($value));
    if($text==='' || mb_strlen($text)>$limit){return auren_app_error('invalid_content','Revisa el texto y su longitud antes de guardar.',400);}
    return $text;
}

/** Serialize mutations per member; never retry a non-idempotent platform toggle automatically. */
function auren_app_write($callback) {
    global $wpdb;
    $key='auren_app_write_lock_'.get_current_user_id();$now=time();$previous=get_option($key,false);$token=$now.':'.wp_generate_uuid4();
    $release=static function($value)use($wpdb,$key){$wpdb->delete($wpdb->options,array('option_name'=>$key,'option_value'=>$value),array('%s','%s'));wp_cache_delete($key,'options');};
    if($previous!==false && (int)$previous<$now-30){$release((string)$previous);}
    if(!add_option($key,$token,'',false)){return auren_app_error('write_in_progress','Hay un cambio guardándose. Espera un momento.',409);}
    try{return $callback();}finally{$release($token);}
}

function auren_app_create_activity($r,$parent=0) {
    $text=auren_app_text_input($r->get_param('content'));if(is_wp_error($text)){return $text;}
    $key=$r->get_param('request_key');
    if(!is_string($key) || !preg_match('/^[a-f0-9-]{36}$/iD',$key)){return auren_app_error('invalid_request_key','Vuelve a abrir el editor e inténtalo de nuevo.',400);}
    $privacy=$r->get_param('privacy')??'loggedin';
    if(!in_array($privacy,array('loggedin','onlyme'),true)){return auren_app_error('invalid_privacy','Elige una audiencia válida.',400);}
    $title=$r->get_param('title')??'';if(!is_string($title)||mb_strlen($title)>200){return auren_app_error('invalid_content','Revisa el título de la publicación.',400);}
    return auren_app_write(function()use($text,$key,$parent,$privacy,$title){
        $fingerprint=hash('sha256',wp_json_encode(array($parent,$text,$privacy,$title)));
        $cache='auren_app_post_'.hash('sha256',get_current_user_id().':'.$key);$saved=get_transient($cache);
        if($saved){
            if($saved['fingerprint']!==$fingerprint){return auren_app_error('duplicate_request','Esta solicitud ya se utilizó con otro contenido.',409);}
            if(empty($saved['activity_id'])){return auren_app_error('write_in_progress','La solicitud anterior sigue pendiente. Actualiza antes de repetirla.',409);}
            return auren_app_activity((int)$saved['activity_id']);
        }
        if($parent){
            $visible=auren_app_activity($parent);if(is_wp_error($visible)){return $visible;}
            if(empty($visible['permissions']['comment'])){return auren_app_error('action_forbidden','Esta publicación no admite comentarios para tu cuenta.',403);}
            $route='/buddyboss/v1/activity/'.$parent.'/comment';$params=array('content'=>esc_html($text),'display_comments'=>'threaded');
        }else{
            $route='/buddyboss/v1/activity';$params=array('content'=>esc_html($text),'privacy'=>$privacy,'type'=>'activity_update','component'=>'activity','user_id'=>get_current_user_id(),'post_title'=>sanitize_text_field($title));
        }
        set_transient($cache,array('fingerprint'=>$fingerprint,'activity_id'=>0),HOUR_IN_SECONDS);
        $response=auren_app_buddyboss_request('POST',$route,$params);if(is_wp_error($response)){if(($response->get_error_data()['status']??503)<500){delete_transient($cache);}return $response;}
        $data=$response->get_data();$id=$parent?:absint($data['id']??0);
        if(!$id){return auren_app_error('invalid_response','No pudimos confirmar el cambio. Actualiza antes de repetirlo.',502);}
        set_transient($cache,array('fingerprint'=>$fingerprint,'activity_id'=>$id),HOUR_IN_SECONDS);
        return auren_app_activity($id);
    });
}

function auren_app_change_activity($r,$remove=false) {
    $id=(int)$r['activity'];$text=$remove?null:auren_app_text_input($r->get_param('content'));if(is_wp_error($text)){return $text;}
    return auren_app_write(function()use($id,$text,$remove){
        $item=auren_app_activity($id);if(is_wp_error($item)){return $item;}
        if($item['author']['id']!==get_current_user_id() || empty($item['permissions'][$remove?'delete':'edit'])){return auren_app_error('action_forbidden','Solo puedes modificar tus propias publicaciones permitidas.',403);}
        $response=auren_app_buddyboss_request($remove?'DELETE':'PATCH','/buddyboss/v1/activity/'.$id,$remove?array():array('content'=>esc_html($text),'privacy'=>$item['privacy'],'post_title'=>$item['title']??''));
        if(is_wp_error($response)){return $response;}
        return $remove?array('deleted'=>true,'id'=>$id):auren_app_activity($id);
    });
}

function auren_app_like($r) {
    $desired=$r->get_param('liked');if(!is_bool($desired)){return auren_app_error('invalid_content','Revisa la reacción seleccionada.',400);}
    return auren_app_write(function()use($r,$desired){
        $id=(int)$r['activity'];$item=auren_app_activity($id);if(is_wp_error($item)){return $item;}
        if(empty($item['permissions']['like'])){return auren_app_error('action_forbidden','No puedes reaccionar a esta publicación.',403);}
        if($item['liked']===$desired){return $item;}
        $response=auren_app_buddyboss_request('PATCH','/buddyboss/v1/activity/'.$id.'/favorite',array('item_type'=>'activity'));
        if(is_wp_error($response)){return $response;}
        return auren_app_activity($id);
    });
}

function auren_app_notification_item($id) {
    $response=auren_app_buddyboss_request('GET','/buddyboss/v1/notifications/'.$id,array('context'=>'view'));
    if(is_wp_error($response)){return $response;}$data=$response->get_data();
    if((int)($data['user_id']??0)!==get_current_user_id()){return auren_app_error('action_forbidden','Este aviso no pertenece a tu cuenta.',403);}
    return $data;
}

function auren_app_read_notification($r) {
    return auren_app_write(function()use($r){
        $id=(int)$r['notification'];$data=auren_app_notification_item($id);if(is_wp_error($data)){return $data;}
        if(!empty($data['is_new'])){
            $response=auren_app_buddyboss_request('PATCH','/buddyboss/v1/notifications/'.$id,array('is_new'=>0));if(is_wp_error($response)){return $response;}
        }
        return array('id'=>$id,'unread'=>false,'unread_count'=>auren_app_unread_count());
    });
}

function auren_app_report_form() {
    $response=auren_app_buddyboss_request('GET','/buddyboss/v1/moderation/report');if(is_wp_error($response)){return $response;}
    $data=$response->get_data();$result=array();
    // Platform returns a field descriptor with category options, not arbitrary report destinations.
    foreach((array)$data as $field){if(($field['name']??'')!=='report_category'){continue;}foreach(($field['options']??array()) as $option){$result[]=array('id'=>(string)($option['value']??$option['id']??''),'label'=>auren_app_plain_content($option['name']??$option['label']??''));}}
    return array('categories'=>$result);
}

function auren_app_report($r) {
    $item=auren_app_activity((int)$r['activity']);if(is_wp_error($item)){return $item;}
    $category=$r->get_param('category');$note=$r->get_param('note')??'';
    if(!is_string($category)||!is_string($note)||mb_strlen($note)>1000){return auren_app_error('invalid_content','Revisa el motivo del reporte.',400);}
    $response=auren_app_buddyboss_request('POST','/buddyboss/v1/moderation/report',array('item_id'=>$item['id'],'item_type'=>'activity','report_category'=>$category,'note'=>sanitize_textarea_field($note)));
    return is_wp_error($response)?$response:array('reported'=>true);
}

function auren_app_read_visible_notifications($r) {
    $ids=$r->get_param('ids');
    if(!is_array($ids)||count($ids)>20||!count($ids)||array_filter($ids,static fn($id)=>!is_int($id)||$id<1)){return auren_app_error('invalid_content','Selecciona hasta veinte avisos válidos.',400);}
    return auren_app_write(function()use($ids){
        $items=array();foreach(array_unique($ids) as $id){$data=auren_app_notification_item($id);if(is_wp_error($data)){return $data;}$items[]=$data;}
        $changed=0;foreach($items as $data){if(empty($data['is_new'])){continue;}$result=auren_app_buddyboss_request('PATCH','/buddyboss/v1/notifications/'.absint($data['id']),array('is_new'=>0));if(is_wp_error($result)){return $result;}$changed++;}
        return array('changed'=>$changed,'unread_count'=>auren_app_unread_count());
    });
}

function auren_app_blocked_members($page=1) {
    $response=auren_app_buddyboss_request('GET','/buddyboss/v1/moderation',array('page'=>$page,'per_page'=>20,'status'=>false,'reporters'=>false));
    if(is_wp_error($response)){return $response;}$items=array();
    foreach((array)$response->get_data() as $item){
        $id=absint($item['item_id']??0);if(!$id||($item['type']??'')!=='user'||!empty($item['hide_sitewide'])){continue;}
        $u=get_userdata($id);if(!$u){continue;}
        $items[]=array('id'=>$id,'name'=>auren_app_plain_content($u->display_name),'avatar_url'=>get_avatar_url($id));
    }
    return auren_app_collection($response,$items,$page);
}
function auren_app_block_member($r) {
    $id=(int)$r['member'];$blocked=$r->get_param('blocked');
    if(!is_bool($blocked)||$id===get_current_user_id()){return auren_app_error('invalid_content','Selecciona otra persona para esta acción.',400);}
    return auren_app_write(function()use($id,$blocked){
        $response=auren_app_buddyboss_request('GET','/buddyboss/v1/moderation',array('item_id'=>$id,'per_page'=>1,'status'=>false,'reporters'=>false));
        if(is_wp_error($response)){return $response;}$existing=null;
        foreach((array)$response->get_data() as $item){if((int)($item['item_id']??0)===$id&&($item['type']??'')==='user'&&empty($item['hide_sitewide'])){$existing=$item;break;}}
        if($blocked&&!$existing){
            $member=auren_app_member($id);if(is_wp_error($member)){return $member;}
            $result=auren_app_buddyboss_request('POST','/buddyboss/v1/moderation',array('item_id'=>$id,'type'=>'user'));if(is_wp_error($result)){return $result;}
        }elseif(!$blocked&&$existing){
            $result=auren_app_buddyboss_request('DELETE','/buddyboss/v1/moderation/'.absint($existing['id']));if(is_wp_error($result)){return $result;}
        }
        return array('id'=>$id,'blocked'=>$blocked);
    });
}
