<?php
require_once __DIR__.'/gamification-setup.php';
$count=0;
function gami_check($ok,$label){global $count;$count++;if(!$ok){throw new RuntimeException('FAIL '.$label);}echo 'PASS '.$label."\n";}
function gami_request($method,$path,$params=array()){$r=new WP_REST_Request($method,'/auren/v1'.$path);foreach($params as $k=>$v){$r->set_param($k,$v);}return rest_do_request($r);}
$a=get_user_by('login','alumno-a')->ID;$b=get_user_by('login','alumno-b')->ID;
wp_set_current_user(0);gami_check(gami_request('GET','/gamification')->get_status()===401,'GamiPress private endpoint denies visitors');
wp_set_current_user($a);
$data=gami_request('GET','/gamification')->get_data();
gami_check($data['credits']['type']==='credits'&&$data['credits']['balance']===100,'adapter reads original points type and native balance');
gami_check($data['rank']['current']['id']===$base&&$data['rank']['current']['number']===1,'native lowest published rank is the starting level');
gami_check($data['rank']['next']['id']===$next&&$data['rank']['progress']['percent']===50,'native balance requirement drives level progress');
gami_check(count($data['achievements'])===1&&$data['achievements'][0]['id']===$achievement,'hidden and unconfigured rewards are excluded');
gami_check($data['achievements'][0]['reward']===20,'reward uses only selected original currency');
gami_check(gami_request('GET','/gamification',array('user_id'=>$b))->get_status()===400,'caller cannot inspect another member balance');
gami_check(gami_request('POST','/gamification',array('balance'=>90000))->get_status()===404,'mobile client cannot assign points or ranks');
$mutations=0;$counter=function()use(&$mutations){$mutations++;};
add_action('gamipress_award_points_to_user',$counter);add_action('gamipress_award_achievement',$counter);
auren_app_gamification();auren_app_gamification();
gami_check($mutations===0&&gamipress_get_user_points($a,'credits')===100,'repeated reads never grant points or levels');
gamipress_update_post_meta($requirement,'_gamipress_points_required',250);
gami_check(auren_app_gamification()['rank']['progress']['percent']===40,'changing requirement in GamiPress immediately changes app progress');
gamipress_update_post_meta($requirement,'_gamipress_points_required',200);
gamipress_award_points_to_user($a,10,'credits',array('reason'=>'Local integration acceptance'));
gami_check(auren_app_gamification()['credits']['balance']===110&&auren_app_gamification()['rank']['progress']['percent']===55,'native points award appears without a second ledger');
wp_set_current_user($b);gami_check(auren_app_gamification()['credits']['balance']===25&&auren_app_gamification()['rank']['progress']['percent']===12,'balances and progress remain isolated by account');
wp_set_current_user($a);gamipress_deduct_points_to_user($a,10,'credits',array('reason'=>'Restore local acceptance state'));
gami_check(auren_app_gamification()['credits']['balance']===100,'native point deduction also appears in app');
wp_update_post(array('ID'=>$next,'post_title'=>'Navegante · fixture'));
gami_check(auren_app_gamification()['rank']['next']['name']==='Navegante · fixture','WordPress rank edits are the source of app text');
wp_update_post(array('ID'=>$next,'post_title'=>'Explorador · fixture'));
$me=gami_request('GET','/me')->get_data();gami_check($me['gamification']['credits']['balance']===100,'identity and dedicated endpoint share the same adapter');
echo "$count checks passed against real GamiPress ".GAMIPRESS_VER.". No production data used.\n";
