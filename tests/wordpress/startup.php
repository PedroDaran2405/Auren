<?php
require '/var/www/html/wp-load.php';
if(wp_get_environment_type()!=='local'||!defined('AUREN_TEST_FIXTURES')){exit(1);}
$count=0;
function start_check($ok,$label){global $count;$count++;if(!$ok){throw new RuntimeException('FAIL '.$label);}echo "PASS $label\n";}
function start_request($params=array()){$r=new WP_REST_Request('GET','/auren/v1/startup');foreach($params as $k=>$v){$r->set_param($k,$v);}return rest_do_request($r);}
$a=get_user_by('login','alumno-a')->ID;$b=get_user_by('login','alumno-b')->ID;
wp_set_current_user(0);start_check(start_request()->get_status()===401,'startup denies anonymous reads');
wp_set_current_user($a);$r=start_request();$data=$r->get_data();start_check($r->get_status()===200&&$data['identity']['id']===$a,'startup binds identity to current member');
foreach(array('profile','community','notifications','catalog') as $key){start_check(is_array($data[$key]),'startup includes permitted '.$key);}
start_check(!in_array((int)get_option('auren_fixture_pro'),array_column($data['catalog']['items'],'id'),true),'batch retains course enrollment restrictions');
start_check(start_request(array('user_id'=>$b))->get_status()===400,'batch rejects actor overrides');
start_check(start_request(array('routes'=>array('/wp/v2/users')))->get_status()===400,'batch accepts no arbitrary route list');
wp_set_current_user($b);$other=start_request()->get_data();start_check($other['identity']['id']===$b&&$other['identity']['id']!==$data['identity']['id'],'batch cannot reuse another account identity');
$r=apply_filters('rest_post_dispatch',$r,rest_get_server(),new WP_REST_Request('GET','/auren/v1/startup'));
start_check(str_contains($r->get_headers()['Cache-Control'],'no-store'),'batch response remains private and uncached');
echo "$count startup checks passed.\n";
