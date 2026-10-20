<?php
defined('ABSPATH') || exit;

function auren_app_has_buddyboss_route($route) {
    return isset(rest_get_server()->get_routes()[$route]);
}

/** Dispatch as the current member; BuddyBoss retains its visibility and block rules. */
function auren_app_buddyboss_read($route,$params=array()) {
    return auren_app_buddyboss_request('GET',$route,$params);
}

function auren_app_plain_content($value) {
    if(is_array($value)){$value=$value['rendered']??'';}
    if(!is_string($value)){return '';}
    $value=preg_replace('~</(?:p|div|h[1-6]|li)>|<br\s*/?>~i',"\n\n",$value);
    return trim(html_entity_decode(wp_strip_all_tags($value),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'));
}

function auren_app_safe_url($url,$same_origin=false) {
    if(!is_string($url) || !$url){return null;}
    $parts=wp_parse_url($url);$home=wp_parse_url(home_url('/'));
    if(!$parts || !empty($parts['user']) || !empty($parts['pass'])){return null;}
    if(($parts['scheme']??'')!=='https' && !(wp_get_environment_type()==='local' && ($parts['scheme']??'')==='http' && ($parts['host']??'')===($home['host']??''))){return null;}
    if($same_origin && (($parts['host']??'')!==($home['host']??'') || ($parts['port']??null)!==($home['port']??null))){return null;}
    return esc_url_raw($url,array('https','http'));
}

function auren_app_activity_data($item,$detail=false) {
    $own=(int)($item['user_id']??0)===get_current_user_id();
    $data=array('id'=>absint($item['id']??0),'author'=>array('id'=>absint($item['user_id']??0),'name'=>auren_app_plain_content($item['name']??''),'avatar_url'=>auren_app_safe_url($item['user_avatar']['thumb']??'')),
        'text'=>auren_app_plain_content($item['content']??''),'title'=>auren_app_plain_content($item['post_title']??''),'date'=>(string)($item['date']??''),'type'=>(string)($item['type']??''),
        'image_url'=>auren_app_safe_url($item['feature_media']??''),'url'=>auren_app_safe_url($item['link']??'',true),
        'group_name'=>auren_app_plain_content($item['activity_data']['group_name']??''),
        'reaction_count'=>max(0,(int)($item['favorite_count']??0)),'comment_count'=>max(0,(int)($item['comment_count']??0)),
        'liked'=>!empty($item['favorited']),'privacy'=>(string)($item['privacy']??'loggedin'),
        'permissions'=>array('like'=>!empty($item['can_favorite']),'comment'=>!empty($item['can_comment']),
            'edit'=>$own&&!empty($item['can_edit'])&&($item['type']??'')==='activity_update'&&($item['component']??'activity')==='activity','delete'=>$own&&!empty($item['can_delete'])));
    if($detail){$data['comments']=auren_app_activity_comments($item['comments']??array());}
    return $data;
}

function auren_app_activity_comments($items,$depth=0,&$count=0) {
    $result=array();
    if(!is_array($items) || $depth>3){return $result;}
    foreach($items as $item){
        if(!is_array($item) || ++$count>50){break;}
        $comment=array('id'=>absint($item['id']??0),'author'=>array('id'=>absint($item['user_id']??0),'name'=>auren_app_plain_content($item['name']??''),'avatar_url'=>auren_app_safe_url($item['user_avatar']['thumb']??'')),
            'text'=>auren_app_plain_content($item['content']??''),'title'=>auren_app_plain_content($item['post_title']??''),'date'=>(string)($item['date']??''));
        $comment['replies']=auren_app_activity_comments($item['comments']??array(),$depth+1,$count);$result[]=$comment;
    }
    return $result;
}

function auren_app_collection($response,$items,$page) {
    $headers=array_change_key_case($response->get_headers(),CASE_LOWER);
    $pages=max(0,(int)($headers['x-wp-totalpages']??0));
    return array('items'=>$items,'next_page'=>$page<$pages?$page+1:null);
}

function auren_app_community($page,$filter='all',$search='') {
    if(!auren_app_has_buddyboss_route('/buddyboss/v1/activity')){return auren_app_error('community_unavailable','La comunidad no está disponible por ahora.',503);}
    $params=array('context'=>'view','page'=>$page,'per_page'=>10,'display_comments'=>'false','status'=>'ham_only','search'=>$search);
    if($filter==='mine'){$params['scope']='just-me';$params['user_id']=get_current_user_id();}
    elseif($filter==='liked'){$params['scope']='favorites';$params['user_id']=get_current_user_id();}
    $response=auren_app_buddyboss_read('/buddyboss/v1/activity',$params);
    if(is_wp_error($response)){return $response;}
    $items=$response->get_data();
    if(!is_array($items)){return auren_app_error('community_unavailable','No pudimos consultar la comunidad.',503);}
    return auren_app_collection($response,array_map('auren_app_activity_data',$items),$page);
}

function auren_app_activity($id) {
    $response=auren_app_buddyboss_read('/buddyboss/v1/activity/'.$id,array('context'=>'view','display_comments'=>'threaded','status'=>'ham_only'));
    return is_wp_error($response)?$response:auren_app_activity_data($response->get_data(),true);
}

function auren_app_notifications($page,$read=false) {
    if(!auren_app_has_buddyboss_route('/buddyboss/v1/notifications')){return auren_app_error('notifications_unavailable','Tus avisos no están disponibles por ahora.',503);}
    // Never accept a recipient from the client, including for an administrator.
    $params=array('context'=>'view','user_id'=>get_current_user_id(),'page'=>$page,'per_page'=>20);
    $params['is_new']=!$read;$params['sort_order']='DESC';
    $response=auren_app_buddyboss_read('/buddyboss/v1/notifications',$params);
    if(is_wp_error($response)){return $response;}
    $items=array();
    foreach((array)$response->get_data() as $item){
        if((int)($item['user_id']??0)!==get_current_user_id()){continue;}
        $items[]=array('id'=>absint($item['id']??0),'text'=>auren_app_plain_content($item['description']??''),'date'=>(string)($item['date']??''),
            'url'=>auren_app_safe_url($item['link_url']??'',true),'avatar_url'=>auren_app_safe_url($item['avatar_urls']['thumb']??''),'unread'=>(bool)($item['is_new']??false),
            'target'=>auren_app_notification_target($item));
    }
    $result=auren_app_collection($response,$items,$page);$result['unread_count']=auren_app_unread_count();return $result;
}

function auren_app_notification_target($item) {
    if(($item['component_name']??'')==='activity' && !empty($item['item_id'])){
        $id=absint($item['item_id']);
        if(class_exists('BP_Activity_Activity')){$activity=new BP_Activity_Activity($id);if(($activity->type??'')==='activity_comment'){$id=absint($activity->item_id);}}
        return array('type'=>'activity','id'=>$id);
    }
    return null;
}

function auren_app_unread_count() {
    return function_exists('bp_notifications_get_unread_notification_count')?max(0,(int)bp_notifications_get_unread_notification_count(get_current_user_id())):0;
}
