<?php
require '/var/www/html/wp-load.php';
if(wp_get_environment_type()!=='local'||!defined('AUREN_TEST_FIXTURES'))exit(1);
$count=0;function signup_check($ok,$label){global $count;$count++;if(!$ok)throw new RuntimeException('FAIL '.$label);echo "PASS $label\n";}
wp_set_current_user(0);$old=get_option('users_can_register');update_option('users_can_register',true);update_option('default_role','subscriber');delete_option('auren_fixture_signup_captcha');
$ip='auren_signup_ip_'.hash('sha256',$_SERVER['REMOTE_ADDR']??'unknown');delete_transient($ip);
function signup_request($values,$challenge=null,$extra=array()){
 $r=new WP_REST_Request('POST','/auren/v1/registration');$r->set_header('Content-Type','application/json');$r->set_body(wp_json_encode(array_merge(array('fields'=>$values,'challenge'=>$challenge??auren_app_registration_challenge(),'website'=>''),$extra)));return rest_do_request($r);
}
$config=rest_do_request(new WP_REST_Request('GET','/auren/v1/registration'))->get_data();
signup_check($config['enabled']&&!$config['native_form_only']&&count($config['fields'])===5,'public config reads original signup fields without privileged settings');
signup_check(!str_contains(wp_json_encode($config),wp_salt('nonce')),'public challenge exposes no signing secret');
$values=array('signup_email'=>'signup-fixture@example.invalid','signup_password'=>'Local-Test-Only-123!','field_1'=>'Alumno nuevo','field_111'=>array('Youtube'),'legal_agreement'=>'1');
signup_check(signup_request($values,'invalid')->get_status()===400,'forged challenge rejected');
signup_check(signup_request($values,auren_app_registration_challenge(time()-700))->get_status()===400,'expired challenge rejected');
signup_check(signup_request($values,null,array('website'=>'bot'))->get_status()===400,'honeypot rejected without signup');
signup_check(signup_request($values+array('role'=>'administrator'))->get_status()===400,'role injection rejected before platform dispatch');
$missing=$values;unset($missing['field_111']);signup_check(signup_request($missing)->get_status()===400,'all required native fields must be provided');
$bad=$values;$bad['field_111']=array('administrator');signup_check(signup_request($bad)->get_status()===400,'options must match original platform choices');
$bad=$values;$bad['signup_email']=array('bad');signup_check(signup_request($bad)->get_status()===400,'malformed field types cannot crash validation');delete_transient($ip);
$existing=get_user_by('email',$values['signup_email']);if($existing){require_once ABSPATH.'wp-admin/includes/user.php';wp_delete_user($existing->ID);}
$challenge=auren_app_registration_challenge();$_POST=array('previous'=>'preserved');$r=signup_request($values,$challenge);$data=$r->get_data();
signup_check($r->get_status()===200&&$data['submitted']===true,'registration creates through original signup route');
$user=get_user_by('email',$values['signup_email']);signup_check($user&&$user->roles===array('subscriber'),'new account is a real WordPress subscriber');
signup_check(wp_check_password($values['signup_password'],$user->user_pass,$user->ID),'same WordPress account owns the submitted password');
signup_check(!str_contains(wp_json_encode($data),'activation_key')&&!isset($data['data'])&&!isset($data['id']),'response contains no activation key, user internals or credentials');
signup_check($_POST===array('previous'=>'preserved'),'native POST context restored after signup');
signup_check(signup_request($values,$challenge)->get_status()===409,'replaying an acknowledged submission cannot create another account');
update_option('auren_fixture_signup_captcha',true);signup_check(auren_app_registration_config()['native_form_only']&&signup_request($values)->get_status()===409,'configured original CAPTCHA cannot be bypassed by app registration');delete_option('auren_fixture_signup_captcha');
update_option('default_role','administrator');signup_check(!auren_app_registration_config()['enabled'],'privileged default role disables anonymous app signup');update_option('default_role','subscriber');
set_transient($ip,4,600);signup_check(signup_request($values)->get_status()===429,'request limit is enforced before user creation');delete_transient($ip);
require_once ABSPATH.'wp-admin/includes/user.php';wp_delete_user($user->ID);update_option('users_can_register',$old);
echo "$count signup checks passed: WordPress users real; BuddyBoss signup/activation explicit fixtures; outgoing mail disabled.\n";
