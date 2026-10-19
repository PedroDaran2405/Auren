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
function bp_notifications_get_unread_notification_count($user){return 1;}
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
