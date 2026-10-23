<?php
/** Groups and discussions use the installed BuddyBoss handlers as the current member. */
defined('ABSPATH') || exit;

function auren_app_space_query($r,$filters=array()){
    $q=$r->get_query_params();
    if(array_diff(array_keys($q),array('page','search','filter'))){return auren_app_error('invalid_request','Consulta no válida.',400);}
    $page=$q['page']??'1';$search=$q['search']??'';$filter=$q['filter']??($filters[0]??'all');
    if(!is_scalar($page)||!preg_match('/^[1-9][0-9]{0,3}$/D',(string)$page)||(int)$page>1000||!is_string($search)||mb_strlen($search)>80||!is_string($filter)||($filters&&!in_array($filter,$filters,true))){return auren_app_error('invalid_request','Consulta no válida.',400);}
    return array((int)$page,sanitize_text_field($search),$filter);
}
function auren_app_space_denied(){return auren_app_error('space_forbidden','Este espacio no está disponible para tu cuenta.',403);}
function auren_app_group_data($g){
    $read=($g['status']??'')==='public'||!empty($g['is_member'])||!empty($g['is_admin'])||!empty($g['is_mod'])||current_user_can('bp_moderate');
    return array('id'=>absint($g['id']??0),'name'=>auren_app_plain_content($g['name']??''),'description'=>auren_app_plain_content($g['description']??''),
        'status'=>(string)($g['status']??'private'),'member_count'=>max(0,(int)($g['members_count']??$g['total_member_count']??0)),
        'avatar_url'=>auren_app_safe_url($g['avatar_urls']['thumb']??''),'cover_url'=>auren_app_safe_url($g['cover_url']??''),
        'is_member'=>!empty($g['is_member']),'pending'=>!empty($g['request_id']),'can_read'=>$read,'can_join'=>!empty($g['can_join'])&&empty($g['is_member'])&&empty($g['request_id'])&&($g['status']??'')!=='hidden',
        'has_forum'=>!empty($g['enable_forum']));
}
function auren_app_groups($page=1,$filter='mine',$search=''){
    if(!auren_app_has_buddyboss_route('/buddyboss/v1/groups'))return auren_app_error('spaces_unavailable','Los grupos no están disponibles.',503);
    $params=array('context'=>'view','page'=>$page,'per_page'=>12,'search'=>$search,'type'=>'active','show_hidden'=>false);
    if($filter==='mine'){$params['user_id']=get_current_user_id();$params['scope']='personal';$params['show_hidden']=true;}
    $r=auren_app_buddyboss_read('/buddyboss/v1/groups',$params);if(is_wp_error($r))return $r;
    return auren_app_collection($r,array_map('auren_app_group_data',(array)$r->get_data()),$page);
}
function auren_app_group($id){
    $r=auren_app_buddyboss_read('/buddyboss/v1/groups/'.$id,array('context'=>'view'));return is_wp_error($r)?$r:auren_app_group_data($r->get_data());
}
function auren_app_forum_data($f){
    return array('id'=>absint($f['id']??0),'title'=>auren_app_plain_content($f['title']??''),'description'=>auren_app_plain_content($f['content']??''),
        'parent'=>absint($f['parent']??0),'topic_count'=>max(0,(int)($f['total_topic_count']??0)),'reply_count'=>max(0,(int)($f['total_reply_count']??0)),
        'closed'=>!empty($f['is_closed']),'category'=>!empty($f['is_forum_category']),
        'can_read'=>!empty($f['user_permission']['show_topics'])&&empty($f['password']),
        'can_create'=>!empty($f['user_permission']['create_topic'])&&empty($f['password'])&&empty($f['is_forum_category'])&&empty($f['is_closed']));
}
function auren_app_forum($id){
    $r=auren_app_buddyboss_read('/buddyboss/v1/forums/'.$id,array('context'=>'view'));if(is_wp_error($r))return $r;
    $f=auren_app_forum_data($r->get_data());return $f['can_read']?$f:auren_app_space_denied();
}
function auren_app_forums($page=1,$parent=0,$search=''){
    if(!auren_app_has_buddyboss_route('/buddyboss/v1/forums'))return auren_app_error('spaces_unavailable','Los foros no están disponibles.',503);
    $r=auren_app_buddyboss_read('/buddyboss/v1/forums',array('context'=>'view','page'=>$page,'per_page'=>12,'parent'=>$parent,'search'=>$search,'status'=>array('publish','private','hidden')));
    if(is_wp_error($r))return $r;
    // Native collection + explicit content permission; never expose protected forum content.
    $items=array_values(array_filter(array_map('auren_app_forum_data',(array)$r->get_data()),static fn($f)=>$f['can_read']));
    return auren_app_collection($r,$items,$page);
}
function auren_app_space_author($item){
    $id=absint($item['author']??0);$user=$id?get_userdata($id):false;
    return array('id'=>$id,'name'=>$user?auren_app_plain_content($user->display_name):auren_app_plain_content($item['anonymous_author_data']['name']??'Miembro'),
        'avatar_url'=>$user?auren_app_safe_url(get_avatar_url($id)):null);
}
function auren_app_topic_data($t){
    return array('id'=>absint($t['id']??0),'forum_id'=>absint($t['forum_id']??$t['parent']??0),'title'=>auren_app_plain_content($t['title']??''),
        'text'=>auren_app_plain_content($t['content']??''),'author'=>auren_app_space_author($t),'date'=>(string)($t['date']??''),
        'reply_count'=>max(0,(int)($t['total_reply_count']??0)),'sticky'=>!empty($t['sticky']),'closed'=>!empty($t['is_closed']),
        'can_reply'=>!empty($t['current_user_permissions']['reply'])&&empty($t['is_closed']));
}
function auren_app_topics($id,$page=1,$search=''){
    $r=auren_app_buddyboss_read('/buddyboss/v1/topics',array('context'=>'view','parent'=>$id,'page'=>$page,'per_page'=>12,'search'=>$search,'status'=>array('publish'),'orderby'=>array('activity'),'order'=>'desc'));
    if(is_wp_error($r))return $r;$items=array();
    foreach((array)$r->get_data() as $t){if(absint($t['forum_id']??$t['parent']??0)===$id&&!empty($t['current_user_permissions']['show_replies'])&&empty($t['password'])){$items[]=auren_app_topic_data($t);}}
    return auren_app_collection($r,$items,$page);
}
function auren_app_topic($id){
    $r=auren_app_buddyboss_read('/buddyboss/v1/topics/'.$id,array('context'=>'view'));if(is_wp_error($r))return $r;$t=$r->get_data();
    if(empty($t['current_user_permissions']['show_replies'])||!empty($t['password']))return auren_app_space_denied();
    $f=auren_app_forum(absint($t['forum_id']??$t['parent']??0));return is_wp_error($f)?$f:auren_app_topic_data($t);
}
function auren_app_replies($id,$page=1){
    $r=auren_app_buddyboss_read('/buddyboss/v1/reply',array('context'=>'view','parent'=>$id,'page'=>$page,'per_page'=>20,'thread_replies'=>false,'order'=>'asc','orderby'=>array('date')));
    if(is_wp_error($r))return $r;$items=array();
    foreach((array)$r->get_data() as $v){if(absint($v['parent']??0)!==$id||!empty($v['password'])||($v['status']??'publish')!=='publish')continue;
        $items[]=array('id'=>absint($v['id']??0),'text'=>auren_app_plain_content($v['content']??''),'author'=>auren_app_space_author($v),'date'=>(string)($v['date']??''),'reply_to'=>absint($v['reply_to']??0));}
    return auren_app_collection($r,$items,$page);
}
function auren_app_spaces(){
    $my=auren_app_groups();$forums=auren_app_forums();
    return array('groups'=>is_wp_error($my)?null:$my,'forums'=>is_wp_error($forums)?null:$forums);
}
function auren_app_group_detail($id){
    $g=auren_app_group($id);if(is_wp_error($g))return $g;
    $forums=array();$activity=null;
    if($g['can_read']){
        if($g['has_forum']&&function_exists('bbp_get_group_forum_ids')){
            foreach(array_slice((array)bbp_get_group_forum_ids($id),0,20) as $fid){$f=auren_app_forum((int)$fid);if(!is_wp_error($f))$forums[]=$f;}
        }
        $r=auren_app_buddyboss_read('/buddyboss/v1/activity',array('context'=>'view','group_id'=>$id,'component'=>'groups','page'=>1,'per_page'=>10,'display_comments'=>'false','status'=>'ham_only'));
        if(!is_wp_error($r))$activity=auren_app_collection($r,array_map('auren_app_activity_data',(array)$r->get_data()),1);
    }
    return array('group'=>$g,'forums'=>$forums,'activity'=>$activity);
}
function auren_app_forum_detail($id,$page=1,$search=''){
    $f=auren_app_forum($id);if(is_wp_error($f))return $f;
    $children=auren_app_forums($page,$id,$search);$topics=auren_app_topics($id,$page,$search);
    if(is_wp_error($children))return $children;if(is_wp_error($topics))return $topics;
    return array('forum'=>$f,'forums'=>$children,'topics'=>$topics);
}
function auren_app_topic_detail($id,$page=1){
    $t=auren_app_topic($id);if(is_wp_error($t))return $t;$r=auren_app_replies($id,$page);return is_wp_error($r)?$r:array('topic'=>$t,'replies'=>$r);
}
function auren_app_group_members($id,$page=1){
    $g=auren_app_group($id);if(is_wp_error($g))return $g;if(!$g['can_read'])return auren_app_space_denied();
    $r=auren_app_buddyboss_read('/buddyboss/v1/groups/'.$id.'/members',array('context'=>'view','page'=>$page,'per_page'=>20,'exclude_banned'=>true));if(is_wp_error($r))return $r;
    $items=array_map(static fn($m)=>array('id'=>absint($m['id']??0),'name'=>auren_app_plain_content($m['name']??''),'avatar_url'=>auren_app_safe_url($m['avatar_urls']['thumb']??'')),(array)$r->get_data());return auren_app_collection($r,$items,$page);
}
function auren_app_group_activity($id,$page=1){
    $g=auren_app_group($id);if(is_wp_error($g))return $g;if(!$g['can_read'])return auren_app_space_denied();
    $r=auren_app_buddyboss_read('/buddyboss/v1/activity',array('context'=>'view','group_id'=>$id,'component'=>'groups','page'=>$page,'per_page'=>10,'display_comments'=>'false','status'=>'ham_only'));
    return is_wp_error($r)?$r:auren_app_collection($r,array_map('auren_app_activity_data',(array)$r->get_data()),$page);
}
/** One key per intended write. A lost response never causes an automatic second publication. */
function auren_app_space_write($r,$allowed,$callback){
    $p=$r->get_json_params();if($r->get_query_params()||!is_array($p)||strlen($r->get_body())>32768||array_diff(array_keys($p),array_merge($allowed,array('request_key')))||!is_string($p['request_key']??null)||!preg_match('/^[a-f0-9-]{36}$/iD',$p['request_key']))return auren_app_error('invalid_request','Revisa el formulario.',400);
    return auren_app_write(function()use($r,$p,$callback){
        $key='auren_space_'.hash('sha256',get_current_user_id().':'.$p['request_key']);$fingerprint=hash('sha256',$r->get_route().wp_json_encode($p));$saved=get_transient($key);
        if($saved){if($saved['fingerprint']!==$fingerprint)return auren_app_error('duplicate_request','Esta solicitud ya se utilizó.',409);return $saved['result']??auren_app_error('write_in_progress','Actualiza antes de repetir esta solicitud.',409);}
        set_transient($key,array('fingerprint'=>$fingerprint),HOUR_IN_SECONDS);$result=$callback($p);
        if(is_wp_error($result)){if(($result->get_error_data()['status']??503)<500)delete_transient($key);return $result;}
        set_transient($key,array('fingerprint'=>$fingerprint,'result'=>$result),HOUR_IN_SECONDS);return $result;
    });
}
function auren_app_create_topic($r){return auren_app_space_write($r,array('title','content'),function($p)use($r){
    $f=auren_app_forum((int)$r['id']);if(is_wp_error($f))return $f;if(!$f['can_create'])return auren_app_space_denied();
    $title=auren_app_text_input($p['title']??null,160);$content=auren_app_text_input($p['content']??null);if(is_wp_error($title))return $title;if(is_wp_error($content))return $content;
    $params=array('parent'=>$f['id'],'title'=>esc_html($title),'content'=>esc_html($content));
    // The installed handler derives moderation and group membership; never accept a caller's group/author/status.
    if(function_exists('bbp_get_forum_group_ids')){foreach((array)bbp_get_forum_group_ids($f['id']) as $gid){$g=auren_app_group((int)$gid);if(!is_wp_error($g)&&$g['is_member']){$params['group']=(int)$gid;break;}}}
    $v=auren_app_buddyboss_request('POST','/buddyboss/v1/topics',$params);if(is_wp_error($v))return $v;$d=$v->get_data();if(empty($d['id']))return auren_app_error('invalid_response','Actualiza antes de repetir esta publicación.',502);return array('saved'=>true,'id'=>absint($d['id']),'pending'=>($d['status']??'publish')!=='publish');
});}
function auren_app_create_reply($r){return auren_app_space_write($r,array('content'),function($p)use($r){
    $t=auren_app_topic((int)$r['id']);if(is_wp_error($t))return $t;if(!$t['can_reply'])return auren_app_space_denied();
    $text=auren_app_text_input($p['content']??null);if(is_wp_error($text))return $text;
    $v=auren_app_buddyboss_request('POST','/buddyboss/v1/reply',array('topic_id'=>$t['id'],'forum_id'=>$t['forum_id'],'content'=>esc_html($text),'reply_to'=>0));if(is_wp_error($v))return $v;$d=$v->get_data();if(empty($d['id']))return auren_app_error('invalid_response','Actualiza antes de repetir esta publicación.',502);return array('saved'=>true,'id'=>absint($d['id']),'pending'=>($d['status']??'publish')!=='publish');
});}
function auren_app_join_group($r){return auren_app_space_write($r,array(),function()use($r){
    $g=auren_app_group((int)$r['id']);if(is_wp_error($g))return $g;if($g['is_member']||$g['pending'])return array('saved'=>true,'pending'=>$g['pending']);if(!$g['can_join'])return auren_app_space_denied();
    $route=$g['status']==='public'?'/buddyboss/v1/groups/'.$g['id'].'/members':'/buddyboss/v1/groups/membership-requests';
    $params=$g['status']==='public'?array('user_id'=>get_current_user_id(),'role'=>'member'):array('user_id'=>get_current_user_id(),'group_id'=>$g['id'],'message'=>'');
    $v=auren_app_buddyboss_request('POST',$route,$params);if(is_wp_error($v))return $v;$updated=auren_app_group($g['id']);if(is_wp_error($updated))return $updated;if(!$updated['is_member']&&!$updated['pending'])return auren_app_error('invalid_response','Actualiza el grupo para comprobar tu acceso antes de repetir.',502);return array('saved'=>true,'pending'=>$updated['pending']);
});}
