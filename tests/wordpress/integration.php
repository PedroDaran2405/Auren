<?php
require '/var/www/html/wp-load.php';
ob_start();
if(wp_get_environment_type()!=='local'||!defined('AUREN_TEST_FIXTURES')){exit(1);}
$count=0;
function check($condition,$label){global $count;$count++;if(!$condition){fwrite(STDERR,"FAIL $label\n");exit(1);}echo "PASS $label\n";}
function code($value,$expected){return is_wp_error($value)&&$value->get_error_code()===$expected;}
$a=get_user_by('login','alumno-a')->ID;$b=get_user_by('login','alumno-b')->ID;
delete_option('auren_fixture_social_posts');foreach(array($a,$b) as $uid){delete_user_meta($uid,'fixture_notification_read');delete_user_meta($uid,'fixture_like_1001');delete_user_meta($uid,'fixture_blocks');}
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
update_option('auren_app_pilot_users',(string)$b);delete_option('auren_app_pilot_restricted');check(auren_app_permission()===true,'ordinary student accepted without pilot allowlist');
update_option('auren_app_pilot_restricted',true);check(code(auren_app_permission(),'pilot_only'),'optional restricted pilot still enforced');
update_option('auren_app_pilot_restricted',false);update_option('auren_app_pilot_users',$a.','.$b);
update_user_meta($a,'fixture_spam',1);check(code(auren_app_permission(),'account_unavailable'),'suspended student denied');delete_user_meta($a,'fixture_spam');
check(auren_app_is_return_url(home_url('/?auren_app=1')),'app return URL accepted');
check(!auren_app_is_return_url('https://attacker.example/?auren_app=1'),'external app-looking redirect rejected');
check(!auren_app_is_return_url(home_url('/wp-admin/')),'ordinary WordPress redirects remain outside app flow');
$user=get_user_by('id',$a);$return=home_url('/?auren_app=1');
add_filter('login_redirect',static fn($redirect)=>home_url('/members/'),PHP_INT_MAX);
do_action('login_init');
check(apply_filters('login_redirect',home_url('/members/'),$return,$user)===$return,'student returns to app instead of BuddyBoss dashboard');
check(apply_filters('login_redirect',home_url('/members/'),home_url('/courses/'),$user)===home_url('/members/'),'unrelated website login redirect preserved');
check(str_contains(wp_login_url($return),'auren_app_auth=1'),'app login URL carries scoped presentation flag');
$feed=auren_app_community(1);check(!is_wp_error($feed)&&count($feed['items'])===10&&$feed['next_page']===2,'BuddyBoss feed and pagination delegated to original API');
check(!str_contains($feed['items'][0]['text'],'<script')&&!str_contains($feed['items'][0]['text'],'alert('),'feed content converted to plain text');
check(code(auren_app_activity(9999),'community_unavailable'),'BuddyBoss denies private activity before app receives content');
$alerts=auren_app_notifications(1);check($alerts['items'][0]['id']===800+$a,'notifications are bound to authenticated recipient');
$r=new WP_REST_Request('GET','/auren/v1/notifications');$r->set_param('user_id',$b);check(rest_do_request($r)->get_status()===400,'notification recipient cannot be changed by client');
wp_set_current_user($b);check(auren_app_notifications(1)['items'][0]['id']===800+$b,'another student sees only their notifications');wp_set_current_user($a);
check(auren_app_safe_url('javascript:alert(1)')===null&&auren_app_safe_url('https://attacker.example/',true)===null,'unsafe and cross-origin action URLs excluded');
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
check(apply_filters('bb_exclude_endpoints_from_restriction',array('/existing/public/route'))===array('/existing/public/route','/auren/v1/auth/start','/auren/v1/auth/exchange','/auren/v1/registration'),'existing exclusions preserved and only exact auth/registration routes added');
remove_filter('rest_request_before_callbacks',$private_rest,100);wp_set_current_user($a);
foreach(array($a,$b) as $u){foreach(array($l1,$l2) as $l){delete_user_meta($u,'fixture_done_'.$course.'_'.$l);}}
// Write-through contracts: WordPress persists state, original platform handlers remain authoritative.
delete_option('auren_fixture_social_posts');
foreach(array($a,$b) as $uid){delete_user_meta($uid,'fixture_notification_read');delete_user_meta($uid,'fixture_like_1001');delete_user_meta($uid,'fixture_blocks');}
wp_set_current_user($a);
function app_request($method,$path,$params=array()){$r=new WP_REST_Request($method,'/auren/v1'.$path);foreach($params as $k=>$v){$r->set_param($k,$v);}return rest_do_request($r);}
$key=wp_generate_uuid4();$create=app_request('POST','/community',array('content'=>'Aprendizaje privado de integración','privacy'=>'onlyme','request_key'=>$key));
check($create->get_status()===200,'member creates through original platform handler');$post=$create->get_data();$id=$post['id'];
check($post['author']['id']===$a&&$post['privacy']==='onlyme','server fixes post author and preserves private audience');
check((int)buddypress()->loggedin_user->id===0,'temporary BuddyBoss auth context is restored');
$again=app_request('POST','/community',array('content'=>'Aprendizaje privado de integración','privacy'=>'onlyme','request_key'=>$key));check($again->get_data()['id']===$id&&count(auren_fixture_posts())===1,'retry key does not duplicate an acknowledged post');
check(app_request('POST','/community',array('content'=>'Different payload','privacy'=>'onlyme','request_key'=>$key))->get_status()===409,'request key cannot be reused for different content');
check(app_request('POST','/community',array('content'=>'','request_key'=>wp_generate_uuid4()))->get_status()===400,'empty post rejected before persistence');
check(app_request('POST','/community',array('content'=>'Not sent','request_key'=>wp_generate_uuid4(),'user_id'=>$b))->get_status()===400,'post author spoofing rejected');
$before=auren_fixture_posts();wp_set_current_user($b);check(app_request('GET','/community/'.$id)->get_status()===403,'another member cannot read private post');
check(app_request('PATCH','/community/'.$id,array('content'=>'Overwrite'))->get_status()===403,'another member cannot edit private post');check(auren_fixture_posts()===$before,'denied mutation leaves content unchanged');
wp_set_current_user($a);$edited=app_request('PATCH','/community/'.$id,array('content'=>'Mi aprendizaje actualizado'));check($edited->get_status()===200&&str_contains(auren_fixture_posts()[$id]['content']['rendered'],'actualizado'),'app edit persists into platform storage');check($edited->get_data()['privacy']==='onlyme','editing text retains the original private audience');
$r=app_request('POST','/community/1001/comments',array('content'=>'Comentario desde la app','request_key'=>wp_generate_uuid4()));check($r->get_status()===200&&$r->get_data()['comment_count']===1,'comment persists and conversation is returned');
$r=app_request('PUT','/community/1001/like',array('liked'=>true));check($r->get_status()===200&&$r->get_data()['liked']===true,'like is persisted for authenticated member');
$r=app_request('PUT','/community/1001/like',array('liked'=>true));check($r->get_data()['liked']===true,'repeated desired like state does not toggle it off');
check(app_request('GET','/community',array('filter'=>'liked'))->get_data()['items'][0]['id']===1001,'liked feed reads original member favorites');
$profile=app_request('GET','/profile')->get_data();check(count($profile['fields'])===3&&!in_array(77,array_column($profile['fields'],'id'),true),'profile editor excludes protected member type field');
$choice=app_request('PATCH','/profile',array('fields'=>array(array('id'=>4,'value'=>array('Futuros')))));check($choice->get_status()===200&&get_user_meta($a,'fixture_instruments',true)===array('Futuros'),'multi-select profile values persist through platform');
check(app_request('PATCH','/profile',array('fields'=>array(array('id'=>4,'value'=>array('Administrator')))))->get_status()===400,'unknown multi-select option is rejected');
$name=get_userdata($a)->display_name;$role=get_userdata($a)->roles;
$r=app_request('PATCH','/profile',array('fields'=>array(array('id'=>1,'value'=>'Alumno sincronizado'))));check($r->get_status()===200&&get_userdata($a)->display_name==='Alumno sincronizado','profile edit writes through platform and WordPress');
check(app_request('PATCH','/profile',array('fields'=>array(array('id'=>77,'value'=>'administrator'))))->get_status()===400&&get_userdata($a)->roles===$role,'protected profile field cannot grant membership or roles');
app_request('PATCH','/profile',array('fields'=>array(array('id'=>1,'value'=>$name))));
check(app_request('PUT','/notifications/'.(800+$b).'/read')->get_status()===403,'recipient isolation on notification writes');
check(app_request('PUT','/notifications/read',array('ids'=>array(800+$a,800+$b)))->get_status()===403&&!get_user_meta($a,'fixture_notification_read',true),'bulk read checks every recipient before any mutation');
$r=app_request('PUT','/notifications/'.(800+$a).'/read');check($r->get_status()===200&&$r->get_data()['unread_count']===0,'read receipt persists and unread count is recalculated');
check(app_request('PUT','/notifications/'.(800+$a).'/read')->get_status()===200,'read receipt is idempotent');
check(count(app_request('GET','/notifications',array('filter'=>'read'))->get_data()['items'])===1,'read history uses platform read-state filter');
check(app_request('GET','/members',array('search'=>'Alumno'))->get_status()===200,'member directory delegates platform visibility');
$del=app_request('DELETE','/community/'.$id);check($del->get_status()===200&&!isset(auren_fixture_posts()[$id]),'owner delete removes original platform post');
update_option('auren_app_write_lock_'.$a,time().':another-request',false);check(app_request('PUT','/community/1001/like',array('liked'=>false))->get_status()===409,'concurrent mutation lock rejects conflicting write');delete_option('auren_app_write_lock_'.$a);
wp_set_current_user($a);delete_user_meta($a,'fixture_blocks');delete_user_meta($b,'fixture_blocks');
check(app_request('PUT','/members/'.$b.'/block',array('blocked'=>true))->get_status()===200,'block uses original platform mutation');
check(app_request('PUT','/members/'.$b.'/block',array('blocked'=>true))->get_status()===200,'repeated desired block state is idempotent');
check(auren_app_blocked_members()['items'][0]['id']===$b,'member can inspect own block list');
wp_set_current_user($b);check(count(auren_app_blocked_members()['items'])===0,'block list is isolated between accounts');
wp_set_current_user($a);check(app_request('PUT','/members/'.$b.'/block',array('blocked'=>false))->get_status()===200&&count(auren_app_blocked_members()['items'])===0,'unblock updates the original platform and own list');
check(app_request('PUT','/members/'.$a.'/block',array('blocked'=>true))->get_status()===400,'self block rejected');
// Restore fixture state for the browser acceptance flows.
delete_option('auren_fixture_social_posts');foreach(array($a,$b) as $uid){delete_user_meta($uid,'fixture_notification_read');delete_user_meta($uid,'fixture_like_1001');delete_user_meta($uid,'fixture_blocks');}
echo "$count checks passed. WordPress real; LearnDash/BuddyBoss adapters use explicit fixtures.\n";
