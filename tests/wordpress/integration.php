<?php
require '/var/www/html/wp-load.php';
if(wp_get_environment_type()!=='local'||!defined('AUREN_TEST_FIXTURES')){exit(1);}
$count=0;
function check($condition,$label){global $count;$count++;if(!$condition){fwrite(STDERR,"FAIL $label\n");exit(1);}echo "PASS $label\n";}
function code($value,$expected){return is_wp_error($value)&&$value->get_error_code()===$expected;}
$a=get_user_by('login','alumno-a')->ID;$b=get_user_by('login','alumno-b')->ID;
$course=(int)get_option('auren_fixture_free');$pro=(int)get_option('auren_fixture_pro');
$l1=(int)get_option('auren_fixture_lesson1');$l2=(int)get_option('auren_fixture_lesson2');
foreach(array($a,$b) as $u){foreach(array($l1,$l2) as $l){delete_user_meta($u,'fixture_done_'.$course.'_'.$l);}}
wp_set_current_user(0);check(code(auren_app_permission(),'login_required'),'anonymous denied');
wp_set_current_user($a);check(auren_app_permission()===true,'real WordPress subscriber accepted');
check(code(auren_app_course($pro),'course_forbidden'),'PRO course denied');
check(code(auren_app_lesson($pro,$l1),'course_forbidden'),'PRO lesson denied before content lookup');
check(code(auren_app_lesson($course,$pro),'not_found'),'lesson/course association enforced');
wp_update_post(array('ID'=>$course,'post_password'=>'fixture-protected'));check(code(auren_app_course($course),'password_required'),'WordPress password protected course denied');wp_update_post(array('ID'=>$course,'post_password'=>''));
wp_update_post(array('ID'=>$l1,'post_password'=>'fixture-protected'));check(code(auren_app_lesson($course,$l1),'password_required'),'WordPress password protected lesson denied');check(code(auren_app_complete($course,$l1),'password_required'),'protected lesson progress denied');wp_update_post(array('ID'=>$l1,'post_password'=>''));
check(code(auren_app_lesson($course,$l2),'lesson_locked'),'linear progression enforced');
update_post_meta($l1,'fixture_available_from',time()+3600);check(code(auren_app_lesson($course,$l1),'lesson_scheduled'),'drip release enforced');delete_post_meta($l1,'fixture_available_from');
update_post_meta($course,'fixture_prerequisite_blocked',1);check(code(auren_app_course($course),'prerequisites'),'course prerequisites enforced');delete_post_meta($course,'fixture_prerequisite_blocked');
update_post_meta($l1,'fixture_lesson_timer_enabled',1);check(code(auren_app_complete($course,$l1),'web_required'),'timer cannot be bypassed');delete_post_meta($l1,'fixture_lesson_timer_enabled');
update_post_meta($l1,'fixture_topics',array(1));check(code(auren_app_complete($course,$l1),'web_required'),'topic requirements cannot be bypassed');delete_post_meta($l1,'fixture_topics');
update_post_meta($l1,'fixture_completion_blocked',1);check(code(auren_app_complete($course,$l1),'completion_rejected'),'LMS completion veto respected');delete_post_meta($l1,'fixture_completion_blocked');
$done=auren_app_complete($course,$l1);check(!is_wp_error($done)&&$done['progress']['completed']===1,'progress written through LMS adapter');
$again=auren_app_complete($course,$l1);check($again['progress']['completed']===1,'repeated completion idempotent');
wp_set_current_user($b);check(auren_app_progress($course)['completed']===0,'another student has independent progress');
wp_set_current_user($a);check(auren_app_progress($course)['completed']===1,'progress persists after account switch');
$r=new WP_REST_Request('POST','/auren/v1/courses/'.$course.'/lessons/'.$l1.'/complete');$r->set_param('user_id',$b);$result=rest_do_request($r);check($result->get_status()===400,'client cannot choose another user');
$r=new WP_REST_Request('GET','/auren/v1/courses/'.$course);$response=rest_do_request($r);$response=apply_filters('rest_post_dispatch',$response,rest_get_server(),$r);check($response->get_status()===200,'REST course response');check(str_contains($response->get_headers()['Cache-Control'],'no-store'),'private responses not cached');
$manager=WP_Session_Tokens::get_instance($a);$token=$manager->create(time()+3600);$session=$manager->get($token);$session['auren_app']=true;$manager->update($token,$session);$_SERVER['HTTP_AUTHORIZATION']='Bearer '.$a.'.'.$token;
check(auren_app_validate_session()===$a,'scoped core WordPress session accepted');auren_app_logout();check(auren_app_validate_session()===false,'revoked bearer rejected');
$token=$manager->create(time()-1);$_SERVER['HTTP_AUTHORIZATION']='Bearer '.$a.'.'.$token;check(auren_app_validate_session()===false,'expired session rejected');
$token=$manager->create(time()+3600);$_SERVER['HTTP_AUTHORIZATION']='Bearer '.$a.'.'.$token;check(auren_app_validate_session()===false,'ordinary WordPress token is not an app token');$manager->destroy($token);unset($_SERVER['HTTP_AUTHORIZATION']);
update_option('auren_app_verified_learndash_version','unexpected');check(code(auren_app_course($course),'lms_not_verified'),'unverified LMS version fails closed');update_option('auren_app_verified_learndash_version','fixture-1');
check(auren_app_youtube_id('https://www.youtube.com/watch?v=NVB_hUQFtt4')==='NVB_hUQFtt4','real provider URL parsed');
check(auren_app_youtube_id('https://www.youtube.com.attacker.example/watch?v=NVB_hUQFtt4')===null,'lookalike video origin rejected');
check(auren_app_youtube_id('https://www.youtube.com/watch?v[]=bad')===null,'malformed provider ID rejected');
update_option('auren_app_pilot_users',(string)$b);check(code(auren_app_permission(),'pilot_only'),'non-pilot user denied');update_option('auren_app_pilot_users',$a.','.$b);
// Reproduce the installed BuddyBoss private REST policy without its commercial dependencies.
$private_rest = function($response,$handler,$request) {
    if(!is_user_logged_in() && !in_array($request->get_route(),apply_filters('bb_exclude_endpoints_from_restriction',array('/existing/public/route')),true)){
        return new WP_Error('bb_rest_authorization_required','Authentication required',array('status'=>401));
    }
    return $response;
};
add_filter('rest_request_before_callbacks',$private_rest,100,3);wp_set_current_user(0);
$r=new WP_REST_Request('POST','/auren/v1/auth/start');$r->set_param('challenge',str_repeat('a',43));$r->set_param('state',str_repeat('b',64));$response=rest_do_request($r);check($response->get_status()===200,'anonymous PKCE start works with BuddyBoss private REST policy');
$r=new WP_REST_Request('POST','/auren/v1/auth/exchange');$response=rest_do_request($r);check($response->get_status()===401&&$response->get_data()['code']==='invalid_grant','public exchange still requires valid PKCE grant');
$r=new WP_REST_Request('GET','/auren/v1/courses');check(rest_do_request($r)->get_status()===401,'public auth exceptions do not expose courses');
check(apply_filters('bb_exclude_endpoints_from_restriction',array('/existing/public/route'))===array('/existing/public/route','/auren/v1/auth/start','/auren/v1/auth/exchange'),'existing exclusions preserved and only exact auth routes added');
remove_filter('rest_request_before_callbacks',$private_rest,100);wp_set_current_user($a);
foreach(array($a,$b) as $u){foreach(array($l1,$l2) as $l){delete_user_meta($u,'fixture_done_'.$course.'_'.$l);}}
echo "$count checks passed. WordPress real; LearnDash adapter fixture only.\n";
