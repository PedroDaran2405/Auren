<?php
require '/var/www/html/wp-load.php';
if(wp_get_environment_type()!=='local'||!defined('AUREN_TEST_FIXTURES'))exit(1);
$checks=0;function space_check($ok,$label){global $checks;$checks++;if(!$ok)throw new RuntimeException('FAIL '.$label);echo "PASS $label\n";}
function space_request($path,$method='GET',$body=null,$query=array()){$r=new WP_REST_Request($method,'/auren/v1'.$path);$r->set_query_params($query);if($body!==null){$r->set_header('Content-Type','application/json');$r->set_body(wp_json_encode($body));}return rest_do_request($r);}
$i=auren_fixture_space_seed();$a=get_user_by('login','alumno-a')->ID;$b=get_user_by('login','alumno-b')->ID;
wp_set_current_user(0);space_check(space_request('/spaces')->get_status()===401,'anonymous directory denied');
wp_set_current_user($a);$spaces=space_request('/spaces')->get_data();space_check(count($spaces['groups']['items'])===2,'overview includes only current member groups');
space_check(!in_array(203,array_column(space_request('/groups', 'GET',null,array('filter'=>'all'))->get_data()['items'],'id'),true),'hidden other group excluded');
space_check(space_request('/groups','GET',null,array('user_id'=>$b))->get_status()===400,'actor override rejected');
space_check(space_request('/groups','GET',null,array('page'=>array('1')))->get_status()===400,'malformed page rejected');
space_check(space_request('/groups','GET',null,array('filter'=>'all','search'=>'abierto'))->get_data()['items'][0]['id']===205,'group search and filter reach native query in correct order');
$g=space_request('/groups/202')->get_data();space_check($g['group']['can_read']&&count($g['forums'])===1,'member sees private group forum');
space_check(space_request('/groups/203')->get_status()===403,'hidden group detail denied to nonmember');
$g=space_request('/groups/204')->get_data();space_check(!$g['group']['can_read']&&$g['forums']===array()&&$g['activity']===null,'private nonmember gets no feed or forums');
space_check(space_request('/groups/204/members')->get_status()===403,'private member directory denied');
$locked=space_request('/forums/'.$i['locked']);space_check($locked->get_status()===403&&!str_contains(wp_json_encode($locked->get_data()),'never-leak-this'),'password protected content and password are withheld');
space_check(space_request('/forums/'.$i['category'])->get_data()['forums']['items'][0]['id']===$i['public'],'category hierarchy preserved');
$f=space_request('/forums/'.$i['public'])->get_data();space_check($f['forum']['can_create']&&count($f['topics']['items'])>=1,'forum batches topics with metadata');
$t=space_request('/topics/'.$i['topic'])->get_data();space_check($t['topic']['can_reply']&&isset($t['replies']['items']),'topic batches authorized replies');
$key=wp_generate_uuid4();$payload=array('title'=>'Pregunta de integración','content'=>'Contenido de prueba','request_key'=>$key);
space_check(space_request('/forums/'.$i['public'].'/topics','POST',$payload+array('author'=>$b))->get_status()===400,'write author override rejected');
$r=space_request('/forums/'.$i['public'].'/topics','POST',$payload);$created=$r->get_data();space_check($r->get_status()===200&&get_post($created['id'])->post_author==(string)$a,'topic write owns real WP record for current member');
$again=space_request('/forums/'.$i['public'].'/topics','POST',$payload)->get_data();space_check($again['id']===$created['id'],'same write key cannot duplicate discussion');
$changed=$payload;$changed['content']='another';space_check(space_request('/forums/'.$i['public'].'/topics','POST',$changed)->get_status()===409,'write key cannot be reused with different text');
$answer=array('content'=>'Respuesta de integración','request_key'=>wp_generate_uuid4());$r=space_request('/topics/'.$created['id'].'/replies','POST',$answer)->get_data();space_check(get_post($r['id'])->post_parent===$created['id'],'reply persists under native topic');$reply=$r['id'];
space_check(space_request('/forums/'.$i['closed'].'/topics','POST',array_merge($payload,array('request_key'=>wp_generate_uuid4())))->get_status()===403,'closed forum rejects creation');
update_option('auren_fixture_moderate_spaces',true);$pending=space_request('/forums/'.$i['public'].'/topics','POST',array_merge($payload,array('request_key'=>wp_generate_uuid4())))->get_data();space_check($pending['pending']&&get_post_status($pending['id'])==='pending','native moderation remains pending');delete_option('auren_fixture_moderate_spaces');
wp_set_current_user($b);space_check(space_request('/forums/'.$i['private'])->get_status()===403,'another member cannot read private forum');space_check(space_request('/groups/202/activity')->get_status()===403,'another member cannot read private group activity');
wp_set_current_user($a);$join=space_request('/groups/205/join','POST',array('request_key'=>wp_generate_uuid4()))->get_data();space_check($join['saved']&&auren_fixture_group_member(205),'public join persists current member role');
$join=space_request('/groups/204/join','POST',array('request_key'=>wp_generate_uuid4()))->get_data();space_check($join['pending']&&!auren_fixture_group_member(204),'private request does not grant membership');
wp_delete_post($created['id'],true);wp_delete_post($reply,true);wp_delete_post($pending['id'],true);delete_user_meta($a,'fixture_joined_groups');delete_user_meta($a,'fixture_pending_group_204');
echo "$checks space checks passed. Real local WP posts, explicit BuddyBoss fixtures.\n";
