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
