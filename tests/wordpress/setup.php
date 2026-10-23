<?php
define('WP_INSTALLING',true);
require '/var/www/html/wp-load.php';
if(wp_get_environment_type()!=='local'||!defined('AUREN_TEST_FIXTURES')){exit(1);}
require_once ABSPATH.'wp-admin/includes/upgrade.php';
require_once ABSPATH.'wp-admin/includes/plugin.php';
$password=getenv('AUREN_TEST_PASSWORD');
if(!$password){throw new RuntimeException('Missing local test password');}
if(!is_blog_installed()){wp_install('Auren · integración local','auren-admin','admin@example.invalid',false,'',$password);}
$activation=activate_plugin('auren-app-bridge/auren-app-bridge.php');
if(is_wp_error($activation)){throw new RuntimeException($activation->get_error_message());}
require_once WP_PLUGIN_DIR.'/auren-app-bridge/auren-app-bridge.php';
auren_app_install();
update_option('auren_app_verified_learndash_version','fixture-1');
update_option('permalink_structure','/%postname%/');
flush_rewrite_rules();
$ids=array();
foreach(array('alumno-a','alumno-b') as $name){
 $user=get_user_by('login',$name);
 $ids[$name]=$user?$user->ID:wp_insert_user(array('user_login'=>$name,'user_pass'=>$password,'user_email'=>$name.'@example.invalid','display_name'=>ucwords(str_replace('-',' ',$name)),'role'=>'subscriber'));
}
foreach(array('free'=>'Curso de integración · fixture','pro'=>'Curso protegido · fixture') as $key=>$title){
 $id=(int)get_option('auren_fixture_'.$key);
 if(!$id){$id=wp_insert_post(array('post_type'=>'sfwd-courses','post_status'=>'publish','post_title'=>$title,'post_excerpt'=>'Contenido aislado para probar contratos. No pertenece a la academia real.'));update_option('auren_fixture_'.$key,$id);}
 $ids[$key]=$id;update_post_meta($id,'fixture_course_price_type',$key==='free'?'free':'closed');
}
for($i=1;$i<=2;$i++){
 $id=(int)get_option('auren_fixture_lesson'.$i);
 if(!$id){$id=wp_insert_post(array('post_type'=>'sfwd-lessons','post_status'=>'publish','post_title'=>'Lección '.$i.' · fixture','post_content'=>'Esta es una lección de prueba local. Sirve para comprobar permisos y persistencia; no es contenido de Auren.','menu_order'=>$i));update_option('auren_fixture_lesson'.$i,$id);}
 $ids['lesson'.$i]=$id;update_post_meta($id,'fixture_course',$ids['free']);
}
foreach(array('alumno-a','alumno-b') as $name){update_user_meta($ids[$name],'fixture_courses',array($ids['free']));}
update_option('auren_app_pilot_users',$ids['alumno-a'].','.$ids['alumno-b']);
update_option('auren_app_pilot_restricted',false);
auren_fixture_space_seed();
echo wp_json_encode($ids);
