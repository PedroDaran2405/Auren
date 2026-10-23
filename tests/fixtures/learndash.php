<?php
/** TEST DOUBLE ONLY. Never ship to Auren or call this a LearnDash integration test. */
if (!defined('ABSPATH') || wp_get_environment_type() !== 'local' || !defined('AUREN_TEST_FIXTURES') || !AUREN_TEST_FIXTURES) { return; }
define('LEARNDASH_VERSION','fixture-1');
add_filter('pre_wp_mail','__return_true');
add_action('init',function(){
    register_post_type('sfwd-courses',array('public'=>true,'show_in_rest'=>true,'supports'=>array('title','editor','excerpt')));
    register_post_type('sfwd-lessons',array('public'=>true,'show_in_rest'=>true,'supports'=>array('title','editor')));
});
function sfwd_lms_has_access($course,$user){return in_array((int)$course,array_map('intval',(array)get_user_meta($user,'fixture_courses',true)),true);}
function learndash_get_setting($post,$key){return get_post_meta($post,'fixture_'.$key,true);}
function learndash_get_course_lessons_list($course,$user,$args=array()){
    return array_map(fn($p)=>array('post'=>$p),get_posts(array('post_type'=>'sfwd-lessons','numberposts'=>-1,'meta_key'=>'fixture_course','meta_value'=>$course,'orderby'=>'menu_order','order'=>'ASC')));
}
function learndash_is_lesson_complete($user,$lesson,$course){return (bool)get_user_meta($user,'fixture_done_'.$course.'_'.$lesson,true);}
function learndash_course_progress($args){
    $lessons=learndash_get_course_lessons_list($args['course_id'],$args['user_id']);$done=0;
    foreach($lessons as $l){if(learndash_is_lesson_complete($args['user_id'],$l['post']->ID,$args['course_id']))$done++;}
    return array('completed'=>$done,'total'=>count($lessons));
}
function ld_lesson_access_from($lesson,$user,$course,$bypass_transient=false){return (int)get_post_meta($lesson,'fixture_available_from',true);}
function learndash_is_course_prerequities_completed($course,$user){return !get_post_meta($course,'fixture_prerequisite_blocked',true);}
function learndash_can_complete_step($user,$step,$course,$ignore_timer=false){return !get_post_meta($step,'fixture_completion_blocked',true);}
function learndash_get_topic_list($lesson,$course){return get_post_meta($lesson,'fixture_topics',true) ?: array();}
function learndash_get_lesson_quiz_list($lesson,$user,$course){return get_post_meta($lesson,'fixture_quizzes',true) ?: array();}
function learndash_process_mark_complete($user,$lesson,$calculate=false,$course=0,$force=false){
    if($calculate||$force)throw new RuntimeException('Unexpected completion override');
    update_user_meta($user,'fixture_done_'.$course.'_'.$lesson,1);return true;
}

/** BuddyBoss REST contract double, local only. Not a privacy test of real BuddyBoss. */
function bp_is_user_spammer($user){return (bool)get_user_meta($user,'fixture_spam',true);}
function bp_core_get_user_domain($user){return home_url('/members/fixture-'.$user.'/');}
function bp_get_activity_directory_permalink(){return home_url('/community-web/');}
function bp_notifications_get_unread_notification_count($user){return get_user_meta($user,'fixture_notification_read',true)?0:1;}
function auren_fixture_activity($id){
    return array('id'=>$id,'name'=>'Comunidad · fixture','content'=>array('rendered'=>'<p>Publicación de prueba local.</p><script>alert("unsafe")</script>'),'date'=>'2026-10-01T10:00:00','type'=>'activity_update','link'=>home_url('/community-web/'),'user_avatar'=>array('thumb'=>''),'comment_count'=>0,'favorite_count'=>0);
}
add_action('rest_api_init',function(){
    register_rest_route('buddyboss/v1','/activity',array('methods'=>'GET','permission_callback'=>'is_user_logged_in','callback'=>function($r){
        $page=max(1,(int)$r->get_param('page'));$per=(int)($r->get_param('per_page')?:10);
        $records=array_map('auren_fixture_activity',range(1001,1013));
        $response=new WP_REST_Response(array_slice($records,($page-1)*$per,$per));$response->header('X-WP-Total',13);$response->header('X-WP-TotalPages',(int)ceil(13/$per));return $response;
    }));
    register_rest_route('buddyboss/v1','/activity/(?P<id>[0-9]+)',array('methods'=>'GET','permission_callback'=>function($r){return is_user_logged_in() && (int)$r['id']!==9999?true:new WP_Error('bp_rest_authorization_required','Private fixture',array('status'=>403));},'callback'=>function($r){$data=auren_fixture_activity((int)$r['id']);$data['comments']=array();return $data;}));
    register_rest_route('buddyboss/v1','/notifications',array('methods'=>'GET','permission_callback'=>function($r){return is_user_logged_in()&&(int)$r['user_id']===get_current_user_id();},'callback'=>function($r){
        $response=new WP_REST_Response(array(array('id'=>800+get_current_user_id(),'user_id'=>get_current_user_id(),'description'=>array('rendered'=>'<p>Aviso privado · fixture</p>'),'date'=>'2026-10-01T12:00:00','link_url'=>home_url('/community-web/'),'is_new'=>1)));$response->header('X-WP-TotalPages',1);return $response;
    }));
});
/** Persistent contract double for write-through flows. Local WP only, mail disabled above. */
function buddypress(){static $bp;if(!$bp){$bp=(object)array('loggedin_user'=>(object)array('id'=>0),'displayed_user'=>(object)array('id'=>0));}return $bp;}
function bp_get_xprofile_member_type_field_id(){return 77;}
function auren_fixture_blocks($uid=null){$v=get_user_meta($uid??get_current_user_id(),'fixture_blocks',true);return is_array($v)?$v:array();}
function auren_fixture_posts(){return get_option('auren_fixture_social_posts',array());}
function auren_fixture_activity_full($id){
    $saved=auren_fixture_posts();$item=$saved[$id]??auren_fixture_activity($id);$uid=(int)($item['user_id']??3);$item['user_id']=$uid;
    if(isset($saved[$id])){$item['name']=get_userdata($uid)->display_name;}
    $item['privacy']=$item['privacy']??'loggedin';$item['can_favorite']=true;$item['can_comment']=true;$item['can_edit']=$uid===get_current_user_id();$item['can_delete']=$item['can_edit'];
    $item['favorited']=(bool)get_user_meta(get_current_user_id(),'fixture_like_'.$id,true);$item['favorite_count']=$item['favorited']?1:0;$item['comments']=$item['comments']??array();$item['comment_count']=count($item['comments']);return $item;
}
function auren_fixture_visible($id){$item=auren_fixture_activity_full($id);return (($id>=1001&&$id<=1013)||isset(auren_fixture_posts()[$id]))&&$id!==9999&&!array_key_exists($item['user_id'],auren_fixture_blocks())&&($item['privacy']!=='onlyme'||$item['user_id']===get_current_user_id());}
function auren_fixture_notifications(){return array(array('id'=>800+get_current_user_id(),'user_id'=>get_current_user_id(),'description'=>array('rendered'=>'<p>Aviso privado · fixture</p>'),'date'=>'2026-10-01T12:00:00','link_url'=>home_url('/community-web/'),'component_name'=>'activity','item_id'=>1001,'is_new'=>(int)!get_user_meta(get_current_user_id(),'fixture_notification_read',true)));}
function auren_fixture_profile(){return array(array('id'=>1,'name'=>'Perfil','fields'=>array(array('id'=>1,'name'=>'Nombre','type'=>'textbox','is_required'=>true,'data'=>(object)array('value'=>(object)array('raw'=>get_userdata(get_current_user_id())->display_name))),array('id'=>2,'name'=>'Biografía','type'=>'textarea','is_required'=>false,'data'=>array('value'=>array('raw'=>(string)get_user_meta(get_current_user_id(),'description',true)))),array('id'=>4,'name'=>'Instrumentos','type'=>'checkbox','is_required'=>false,'options'=>array(array('name'=>'Acciones'),array('name'=>'Futuros')),'data'=>array('value'=>array('unserialized'=>(array)get_user_meta(get_current_user_id(),'fixture_instruments',true)))),array('id'=>77,'name'=>'Rol protegido','type'=>'selectbox','data'=>array('value'=>array('raw'=>'Alumno'))))));}
add_action('rest_api_init',function(){
 $logged=static fn()=>is_user_logged_in();
 $view=static fn($r)=>is_user_logged_in()&&auren_fixture_visible((int)$r['id'])?true:new WP_Error('private_activity','Not visible',array('status'=>403));
 register_rest_route('buddyboss/v1','/activity',array(
  array('methods'=>'GET','permission_callback'=>$logged,'callback'=>function($r){
   $map=array_column(array_map('auren_fixture_activity_full',range(1001,1013)),null,'id');foreach(auren_fixture_posts() as $id=>$item){$map[$id]=$item;}krsort($map);$items=array_values($map);
   $items=array_filter($items,function($item)use($r){if(!auren_fixture_visible($item['id']))return false;if($r['scope']==='just-me'&&$item['user_id']!==get_current_user_id())return false;if($r['scope']==='favorites'&&!get_user_meta(get_current_user_id(),'fixture_like_'.$item['id'],true))return false;if($r['search']&&stripos(wp_strip_all_tags(is_array($item['content'])?$item['content']['rendered']:$item['content']),$r['search'])===false)return false;return true;});
   $items=array_map(static fn($x)=>auren_fixture_activity_full($x['id']),array_values($items));$per=(int)($r['per_page']?:10);$page=max(1,(int)$r['page']);$response=new WP_REST_Response(array_slice($items,($page-1)*$per,$per));$response->header('X-WP-TotalPages',(int)ceil(count($items)/$per));return $response;
  }),
  array('methods'=>'POST','permission_callback'=>static fn()=>is_user_logged_in()&&buddypress()->loggedin_user->id===get_current_user_id(),'callback'=>function($r){
   $items=auren_fixture_posts();$id=(int)get_option('auren_fixture_social_next',2000)+1;update_option('auren_fixture_social_next',$id);$items[$id]=array('id'=>$id,'user_id'=>get_current_user_id(),'name'=>wp_get_current_user()->display_name,'content'=>array('rendered'=>$r['content']),'privacy'=>$r['privacy'],'type'=>'activity_update','date'=>gmdate('Y-m-d\TH:i:s'),'link'=>home_url('/community-web/'),'user_avatar'=>array('thumb'=>''));update_option('auren_fixture_social_posts',$items);return auren_fixture_activity_full($id);
  })
 ),true);
 register_rest_route('buddyboss/v1','/activity/(?P<id>[0-9]+)',array(
  array('methods'=>'GET','permission_callback'=>$view,'callback'=>static fn($r)=>auren_fixture_activity_full((int)$r['id'])),
  array('methods'=>'PATCH,DELETE','permission_callback'=>function($r){$item=auren_fixture_activity_full((int)$r['id']);return is_user_logged_in()&&$item['user_id']===get_current_user_id();},'callback'=>function($r){$items=auren_fixture_posts();$id=(int)$r['id'];if($r->get_method()==='DELETE'){unset($items[$id]);update_option('auren_fixture_social_posts',$items);return array('deleted'=>true);} $items[$id]['content']=array('rendered'=>$r['content']);update_option('auren_fixture_social_posts',$items);return auren_fixture_activity_full($id);})
 ),true);
 register_rest_route('buddyboss/v1','/activity/(?P<id>[0-9]+)/favorite',array('methods'=>'PATCH','permission_callback'=>$view,'callback'=>function($r){$id=(int)$r['id'];update_user_meta(get_current_user_id(),'fixture_like_'.$id,!get_user_meta(get_current_user_id(),'fixture_like_'.$id,true));return array('activity'=>auren_fixture_activity_full($id));}),true);
 register_rest_route('buddyboss/v1','/activity/(?P<id>[0-9]+)/comment',array('methods'=>'POST','permission_callback'=>$view,'callback'=>function($r){$id=(int)$r['id'];$item=auren_fixture_activity_full($id);$item['comments'][]=array('id'=>wp_rand(3000,99999),'user_id'=>get_current_user_id(),'name'=>wp_get_current_user()->display_name,'content'=>array('rendered'=>$r['content']),'date'=>gmdate('Y-m-d\TH:i:s'),'user_avatar'=>array('thumb'=>''));$all=auren_fixture_posts();$all[$id]=$item;update_option('auren_fixture_social_posts',$all);return array('created'=>true);}),true);
 register_rest_route('buddyboss/v1','/notifications',array('methods'=>'GET','permission_callback'=>static fn($r)=>is_user_logged_in()&&(int)$r['user_id']===get_current_user_id(),'callback'=>function($r){$items=auren_fixture_notifications();$state=$r->get_param('is_new');$items=array_values(array_filter($items,static fn($x)=>$state===null||(bool)$x['is_new']===$state));$result=new WP_REST_Response($items);$result->header('X-WP-TotalPages',1);return $result;}),true);
 register_rest_route('buddyboss/v1','/notifications/(?P<id>[0-9]+)',array('methods'=>'GET,PATCH','args'=>array('is_new'=>array('type'=>'integer')),'permission_callback'=>static fn($r)=>is_user_logged_in()&&(int)$r['id']===800+get_current_user_id(),'callback'=>function($r){if($r->get_method()==='PATCH'){update_user_meta(get_current_user_id(),'fixture_notification_read',!$r['is_new']);}return auren_fixture_notifications()[0];}),true);
 register_rest_route('buddyboss/v1','/xprofile/groups',array('methods'=>'GET','permission_callback'=>$logged,'callback'=>'auren_fixture_profile'),true);
 register_rest_route('buddyboss/v1','/xprofile/update',array('methods'=>'PATCH','permission_callback'=>static fn()=>is_user_logged_in()&&buddypress()->loggedin_user->id===get_current_user_id(),'callback'=>function($r){foreach($r['fields'] as $item){if($item['field_id']===1){wp_update_user(array('ID'=>get_current_user_id(),'display_name'=>$item['value']));}elseif($item['field_id']===2){update_user_meta(get_current_user_id(),'description',$item['value']);}elseif($item['field_id']===4){update_user_meta(get_current_user_id(),'fixture_instruments',$item['value']);}}return array('updated'=>true);}),true);
 register_rest_route('buddyboss/v1','/members',array('methods'=>'GET','permission_callback'=>$logged,'callback'=>function($r){$users=get_users(array('role'=>'subscriber','search'=>$r['search']?'*'.$r['search'].'*':'','search_columns'=>array('display_name'),'number'=>12));$response=new WP_REST_Response(array_map(static fn($u)=>array('id'=>$u->ID,'name'=>$u->display_name,'avatar_urls'=>array(),'registered_date'=>$u->user_registered),$users));$response->header('X-WP-TotalPages',1);return $response;}),true);
 register_rest_route('buddyboss/v1','/members/(?P<id>[0-9]+)',array('methods'=>'GET','permission_callback'=>$logged,'callback'=>function($r){$u=get_userdata((int)$r['id']);return array('id'=>$u->ID,'name'=>$u->display_name,'avatar_urls'=>array(),'registered_date'=>$u->user_registered,'xprofile'=>array('groups'=>array()));}),true);
 register_rest_route('buddyboss/v1','/moderation/report',array('methods'=>'GET,POST','permission_callback'=>$logged,'callback'=>static fn($r)=>$r->get_method()==='GET'?array(array('name'=>'report_category','options'=>array(array('id'=>1,'name'=>'Spam','value'=>'1'),array('id'=>'','name'=>'Otro','value'=>'other')))):array('reported'=>true)),true);
},30);
add_action('rest_api_init',function(){
 register_rest_route('buddyboss/v1','/moderation',array('methods'=>'GET,POST','permission_callback'=>'is_user_logged_in','callback'=>function($r){
  $uid=get_current_user_id();$all=auren_fixture_blocks($uid);
  if($r->get_method()==='POST'){$id=(int)$r['item_id'];$all[$id]=$uid*10000+$id;update_user_meta($uid,'fixture_blocks',$all);return array('id'=>$all[$id]);}
  $items=array();foreach($all as $id=>$moderation){if($r['item_id']&&(int)$r['item_id']!==(int)$id)continue;$items[]=array('id'=>$moderation,'item_id'=>$id,'type'=>'user','hide_sitewide'=>false);}$result=new WP_REST_Response($items);$result->header('X-WP-TotalPages',1);return $result;
 }),true);
 register_rest_route('buddyboss/v1','/moderation/(?P<id>[0-9]+)',array('methods'=>'DELETE','permission_callback'=>function($r){return in_array((int)$r['id'],auren_fixture_blocks(),true);},'callback'=>function($r){$uid=get_current_user_id();$all=auren_fixture_blocks($uid);$key=array_search((int)$r['id'],$all,true);unset($all[$key]);update_user_meta($uid,'fixture_blocks',$all);return array('deleted'=>true);}),true);
},30);

// Registration TEST DOUBLE: native WordPress users, BuddyBoss signup/mail simulated.
function bp_core_signup_user($login,$password,$email,$meta=array()){
    return wp_insert_user(array('user_login'=>$login,'user_pass'=>$password,'user_email'=>$email,'role'=>get_option('default_role','subscriber')));
}
function bb_recaptcha_is_enabled($key){return $key==='bb_register'&&(bool)get_option('auren_fixture_signup_captcha',false);}
add_action('rest_api_init',function(){
    register_rest_route('buddyboss/v1','/signup/form',array('methods'=>'GET','permission_callback'=>'__return_true','callback'=>static fn()=>array(
        array('id'=>'signup_email','label'=>'Correo electrónico','type'=>'email','required'=>true,'options'=>array()),
        array('id'=>'signup_password','label'=>'Contraseña','type'=>'password','required'=>true,'options'=>array()),
        array('id'=>'field_1','label'=>'Nombre','type'=>'textbox','required'=>true,'options'=>array()),
        array('id'=>'field_111','label'=>'¿Cómo nos conociste?','type'=>'checkbox','required'=>true,'options'=>array(array('name'=>'Youtube'),array('name'=>'Facebook'))),
        array('id'=>'legal_agreement','label'=>'Acepto los términos y la privacidad','type'=>'checkbox','required'=>true,'options'=>array()),
    )));
    register_rest_route('buddyboss/v1','/signup',array('methods'=>'POST','permission_callback'=>'__return_true','callback'=>function($r){
        $email=$r->get_param('signup_email');$user=bp_core_signup_user(sanitize_user(strstr($email,'@',true)),$r->get_param('signup_password'),$email);
        if(is_wp_error($user))return $user;
        update_user_meta($user,'fixture_pending_activation',true);
        return array('success'=>true,'data'=>array('id'=>$user,'user_email'=>$email,'activation_key'=>'private-fixture-key'));
    }));
});
/** TEST DOUBLE: real WP posts/storage, simulated BuddyBoss groups/forum REST, never deploy. */
if(!defined('AUREN_TEST_FIXTURES')||wp_get_environment_type()!=='local')return;
add_action('init',function(){foreach(array('forum','topic','reply') as $type)register_post_type($type,array('public'=>true,'supports'=>array('title','editor','author')));});
function auren_fixture_space_ids(){return get_option('auren_fixture_spaces',array());}
function auren_fixture_space_seed(){
 $ids=auren_fixture_space_ids();if(!empty($ids['public'])&&get_post($ids['public']))return $ids;
 $a=get_user_by('login','alumno-a')->ID;
 foreach(array('category'=>'Categoría de aprendizaje · fixture','public'=>'Preguntas de trading · fixture','private'=>'Foro privado · fixture','closed'=>'Foro cerrado · fixture','locked'=>'Foro con contraseña · fixture') as $key=>$title){$ids[$key]=wp_insert_post(array('post_type'=>'forum','post_status'=>'publish','post_title'=>$title,'post_content'=>'Espacio de prueba local.','post_author'=>$a,'post_parent'=>$key==='public'?($ids['category']??0):0));}
 $ids['topic']=wp_insert_post(array('post_type'=>'topic','post_status'=>'publish','post_title'=>'Cómo organizas tus sesiones · fixture','post_content'=>'Una pregunta para aprender juntos.','post_parent'=>$ids['public'],'post_author'=>$a));
 update_option('auren_fixture_spaces',$ids,false);return $ids;
}
function auren_fixture_group_member($gid){$user=get_userdata(get_current_user_id());return $user&&in_array($gid,array_merge($user->user_login==='alumno-a'?array(201,202):array(203),(array)get_user_meta($user->ID,'fixture_joined_groups',true)),true);}
function auren_fixture_group($gid){
 $names=array(201=>'Aprendemos trading · fixture',202=>'Grupo privado · fixture',203=>'Grupo oculto · fixture',204=>'Grupo privado ajeno · fixture',205=>'Grupo abierto · fixture');if(!isset($names[$gid]))return null;
 return array('id'=>$gid,'name'=>$names[$gid],'description'=>array('rendered'=>'<p>Grupo de la academia para pruebas locales.</p>'),'status'=>in_array($gid,array(202,204),true)?'private':($gid===203?'hidden':'public'),'is_member'=>auren_fixture_group_member($gid),'request_id'=>get_user_meta(get_current_user_id(),'fixture_pending_group_'.$gid,true),'can_join'=>true,'enable_forum'=>in_array($gid,array(201,202),true),'members_count'=>3,'avatar_urls'=>array('thumb'=>''));
}
function bbp_get_group_forum_ids($gid){$i=auren_fixture_space_ids();return isset($i['public'])?($gid===201?array($i['public']):($gid===202?array($i['private']):array())):array();}
function bbp_get_forum_group_ids($id){$i=auren_fixture_space_ids();return $id===($i['private']??0)?array(202):array();}
function auren_fixture_forum($id){
 $p=get_post($id);if(!$p||$p->post_type!=='forum')return null;$i=auren_fixture_space_ids();$read=$id!==($i['private']??0)||auren_fixture_group_member(202);
 return array('id'=>$id,'title'=>array('rendered'=>$p->post_title),'content'=>array('rendered'=>$p->post_content),'parent'=>$p->post_parent,'password'=>$id===($i['locked']??0)?'never-leak-this':'','is_closed'=>$id===($i['closed']??0),'is_forum_category'=>$id===($i['category']??0),'total_topic_count'=>count(get_posts(array('post_type'=>'topic','post_parent'=>$id,'numberposts'=>-1))),'total_reply_count'=>0,'user_permission'=>array('show_topics'=>$read,'create_topic'=>$read&&$id!==($i['category']??0)&&$id!==($i['closed']??0)));
}
function auren_fixture_topic($id){$p=get_post($id);if(!$p||$p->post_type!=='topic')return null;$f=auren_fixture_forum((int)$p->post_parent);return array('id'=>$id,'forum_id'=>$p->post_parent,'parent'=>$p->post_parent,'title'=>array('rendered'=>$p->post_title),'content'=>array('rendered'=>$p->post_content),'author'=>$p->post_author,'date'=>$p->post_date,'status'=>$p->post_status,'is_closed'=>!empty($f['is_closed']),'total_reply_count'=>count(get_posts(array('post_type'=>'reply','post_parent'=>$id,'numberposts'=>-1))),'current_user_permissions'=>array('show_replies'=>$f['user_permission']['show_topics'],'reply'=>$f['user_permission']['create_topic']));}
function auren_fixture_space_page($items,$r,$per=12){$page=(int)($r['page']?:1);$res=new WP_REST_Response(array_slice($items,($page-1)*$per,$per));$res->header('X-WP-TotalPages',(int)ceil(count($items)/$per));return $res;}
add_action('rest_api_init',function(){
 $add=static function($path,$method,$callback){register_rest_route('buddyboss/v1',$path,array('methods'=>$method,'permission_callback'=>'is_user_logged_in','callback'=>$callback),true);};
 $deny=static fn()=>new WP_Error('denied','Private fixture',array('status'=>403));
 $add('/groups','GET',function($r){$out=array();foreach(range(201,205) as $id){$g=auren_fixture_group($id);if($g['status']==='hidden'&&!$g['is_member'])continue;if($r['scope']==='personal'&&!$g['is_member'])continue;if($r['search']&&stripos($g['name'],$r['search'])===false)continue;$out[]=$g;}return auren_fixture_space_page($out,$r);});
 $add('/groups/(?P<id>[0-9]+)','GET',function($r)use($deny){$g=auren_fixture_group((int)$r['id']);return !$g||($g['status']==='hidden'&&!$g['is_member'])?$deny():$g;});
 $add('/groups/(?P<group_id>[0-9]+)/members','GET',function($r)use($deny){$g=auren_fixture_group((int)$r['group_id']);if(!$g||($g['status']!=='public'&&!$g['is_member']))return $deny();$u=wp_get_current_user();return auren_fixture_space_page(array(array('id'=>$u->ID,'name'=>$u->display_name,'avatar_urls'=>array('thumb'=>''))),$r,20);});
 register_rest_route('buddyboss/v1','/groups/(?P<group_id>[0-9]+)/members',array('methods'=>'POST','permission_callback'=>'is_user_logged_in','callback'=>function($r)use($deny){if((int)$r['user_id']!==get_current_user_id()||$r['role']!=='member')return $deny();$g=auren_fixture_group((int)$r['group_id']);if(!$g||$g['status']!=='public')return $deny();$v=(array)get_user_meta(get_current_user_id(),'fixture_joined_groups',true);$v[]=(int)$r['group_id'];update_user_meta(get_current_user_id(),'fixture_joined_groups',array_values(array_unique($v)));return array('joined'=>true);}));
 $add('/groups/membership-requests','POST',function($r)use($deny){if((int)$r['user_id']!==get_current_user_id())return $deny();update_user_meta(get_current_user_id(),'fixture_pending_group_'.(int)$r['group_id'],77);return array('requested'=>true);});
 $add('/forums','GET',function($r){$posts=get_posts(array('post_type'=>'forum','numberposts'=>-1,'post_parent'=>(int)$r['parent'],'orderby'=>'ID','order'=>'ASC'));$items=array_map(fn($p)=>auren_fixture_forum($p->ID),$posts);if($r['search'])$items=array_values(array_filter($items,fn($f)=>stripos($f['title']['rendered'],$r['search'])!==false));return auren_fixture_space_page($items,$r);});
 $add('/forums/(?P<id>[0-9]+)','GET',function($r)use($deny){$f=auren_fixture_forum((int)$r['id']);return $f&&$f['user_permission']['show_topics']?$f:$deny();});
 $add('/topics','GET',function($r){$out=array();foreach(get_posts(array('post_type'=>'topic','numberposts'=>-1,'post_parent'=>(int)$r['parent'],'orderby'=>'ID','order'=>'DESC')) as $p){$t=auren_fixture_topic($p->ID);if(!$r['search']||stripos($p->post_title.' '.$p->post_content,$r['search'])!==false)$out[]=$t;}return auren_fixture_space_page($out,$r);});
 register_rest_route('buddyboss/v1','/topics',array('methods'=>'POST','permission_callback'=>'is_user_logged_in','callback'=>function($r)use($deny){$f=auren_fixture_forum((int)$r['parent']);if(!$f||!$f['user_permission']['create_topic'])return $deny();$id=wp_insert_post(array('post_type'=>'topic','post_status'=>get_option('auren_fixture_moderate_spaces')?'pending':'publish','post_title'=>wp_unslash($r['title']),'post_content'=>wp_unslash($r['content']),'post_parent'=>$f['id'],'post_author'=>get_current_user_id()));return auren_fixture_topic($id);}));
 $add('/topics/(?P<id>[0-9]+)','GET',function($r)use($deny){$t=auren_fixture_topic((int)$r['id']);return $t&&$t['current_user_permissions']['show_replies']?$t:$deny();});
 $add('/reply','GET',function($r){$out=array_map(fn($p)=>array('id'=>$p->ID,'parent'=>$p->post_parent,'content'=>array('rendered'=>$p->post_content),'author'=>$p->post_author,'date'=>$p->post_date,'status'=>$p->post_status),get_posts(array('post_type'=>'reply','post_parent'=>(int)$r['parent'],'numberposts'=>-1,'orderby'=>'ID','order'=>'ASC')));return auren_fixture_space_page($out,$r,20);});
 register_rest_route('buddyboss/v1','/reply',array('methods'=>'POST','permission_callback'=>'is_user_logged_in','callback'=>function($r)use($deny){$t=auren_fixture_topic((int)$r['topic_id']);if(!$t||!$t['current_user_permissions']['reply']||(int)$r['forum_id']!==$t['forum_id'])return $deny();$id=wp_insert_post(array('post_type'=>'reply','post_status'=>get_option('auren_fixture_moderate_spaces')?'pending':'publish','post_content'=>wp_unslash($r['content']),'post_parent'=>$t['id'],'post_author'=>get_current_user_id()));return array('id'=>$id,'status'=>get_post_status($id));}));
});
