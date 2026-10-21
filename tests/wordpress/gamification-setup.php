<?php
// Isolated, labelled test content. Requires the real free GamiPress plugin.
// A failed PHP assertion must produce a nonzero CLI exit, not a recovery HTML page.
defined('WP_DISABLE_FATAL_ERROR_HANDLER')||define('WP_DISABLE_FATAL_ERROR_HANDLER',true);
require_once '/var/www/html/wp-load.php';
if(wp_get_environment_type()!=='local'||!defined('AUREN_TEST_FIXTURES')){exit(1);}
if(!function_exists('GamiPress')){throw new RuntimeException('Install local GamiPress with scripts/local-gamipress.py first.');}
// CLI requests do not visit admin_init, where GamiPress installs its native tables.
foreach(array('gamipress_logs','gamipress_user_earnings') as $table){
    $object=ct_get_table_object($table);$object->db->maybe_upgrade();
    if($object->meta){$object->meta->db->maybe_upgrade();}
}
function gami_fixture_post($key,$type,$title,$extra=array(),$meta=array()){
    $id=(int)get_option('auren_fixture_gami_'.$key);
    $args=array_merge(array('post_type'=>$type,'post_title'=>$title,'post_status'=>'publish','post_name'=>$key),$extra);
    if($id){$args['ID']=$id;}
    $id=wp_insert_post($args,true);if(is_wp_error($id)){throw new RuntimeException($id->get_error_message());}
    update_option('auren_fixture_gami_'.$key,$id);
    foreach($meta as $k=>$value){gamipress_update_post_meta($id,'_gamipress_'.$k,$value);}
    return $id;
}
$points=gami_fixture_post('credits','points-type','Credit · fixture',array(),array('plural_name'=>'Auren Credits'));
gamipress_register_points_type($points,'Credit','Auren Credits','credits');
$type=gami_fixture_post('niveles-auren','rank-type','Nivel · fixture',array(),array('plural_name'=>'Niveles Auren'));
gamipress_register_rank_type($type,'Nivel','Niveles Auren','niveles-auren');
$achievements=gami_fixture_post('logros','achievement-type','Logro · fixture',array(),array('plural_name'=>'Logros'));
gamipress_register_achievement_type($achievements,'Logro','Logros','logros');
gamipress_register_post_types();
$base=gami_fixture_post('inicio','niveles-auren','Inicio · fixture',array('menu_order'=>1));
$next=gami_fixture_post('explorador','niveles-auren','Explorador · fixture',array('menu_order'=>2));
$requirement=gami_fixture_post('rank-requirement','rank-requirement','Alcanza 200 Auren Credits · fixture',array('post_parent'=>$next),array('trigger_type'=>'points-balance','points_required'=>200,'points_type_required'=>'credits','points_condition'=>'greater_or_equal','count'=>1));
$achievement=gami_fixture_post('primer-paso','logros','Primer paso · fixture',array('post_excerpt'=>'Completa una acción de prueba local.'),array('earned_by'=>'triggers','points'=>20,'points_type'=>'credits','hidden'=>'show'));
$step=gami_fixture_post('step','step','Acción local · fixture',array('post_parent'=>$achievement),array('trigger_type'=>'gamipress_test_only','count'=>1));
$hidden=gami_fixture_post('hidden','logros','Logro oculto · fixture',array(),array('earned_by'=>'triggers','hidden'=>'hidden'));
$empty=gami_fixture_post('empty','logros','Sin requisitos · fixture',array(),array('earned_by'=>'triggers'));
foreach(array('alumno-a'=>100,'alumno-b'=>25) as $name=>$balance){
    $uid=get_user_by('login',$name)->ID;
    // Seed only the isolated local accounts; acceptance tests award through GamiPress.
    gamipress_update_user_meta($uid,'_gamipress_credits_points',$balance);
    gamipress_delete_user_meta($uid,'_gamipress_niveles-auren_rank');
}
delete_option('auren_app_points_type');delete_option('auren_app_rank_type');
echo "Real GamiPress local fixtures ready.\n";
