<?php
require_once __DIR__.'/gamification-setup.php';
$count=0;
function header_check($ok,$message){global $count;$count++;if(!$ok){throw new RuntimeException('FAIL '.$message);}echo "PASS $message\n";}
$user=get_user_by('login','alumno-a')->ID;
wp_set_current_user(0);
header_check(!auren_app_header_gamification_enabled() && auren_app_header_gamification_markup()==='','visitors receive no private header values');
wp_set_current_user($user);
header_check(auren_app_header_gamification_enabled(),'logged-in website header is enabled');
$_GET['auren_app']=1;header_check(!auren_app_header_gamification_enabled(),'app view does not duplicate website widget');unset($_GET['auren_app']);
$small=auren_app_gamification(false);$full=auren_app_gamification();
header_check($small['credits']===$full['credits']&&$small['rank']===$full['rank']&&!$small['achievements'],'header reads same native balance/rank without loading achievement catalogue');
$markup=auren_app_header_gamification_markup();
header_check(strpos($markup,'aria-valuenow="50"')!==false&&strpos($markup,'>100</span>')!==false,'initial HTML already contains real balance and progress');
header_check(strpos($markup,'width="34" height="38"')!==false||strpos($markup,'aria-hidden="true">★')!==false,'rank artwork has explicit dimensions or a fixed fallback');
function header_fixture($name,$body,$directory=null){
    $file=($directory??get_template_directory().'/template-parts').'/'.$name;
    ob_start();do_action('wp_before_load_template',$file,false,array());
    echo $body;
    // Unrelated nested template events must not close the owned header buffer.
    do_action('wp_before_load_template','/unrelated/nested.php',false,array());
    do_action('wp_after_load_template','/unrelated/nested.php',false,array());
    do_action('wp_after_load_template',$file,false,array());return ob_get_clean();
}
$desktop=header_fixture('header-aside.php','<div id="header-aside"><div class="header-aside-inner"><button id="menu-control">Cuenta</button></div></div>');
$mobile=header_fixture('header-mobile.php','<div class="bb-mobile-header"><a class="logo">AUREN FX</a><div class="header-aside"><button id="mobile-menu">Cuenta</button></div></div>');
header_check(substr_count($desktop,'data-auren-gami')===1&&strpos($desktop,'data-auren-gami')<strpos($desktop,'id="menu-control"'),'desktop template contains widget before existing controls at first render');
header_check(substr_count($mobile,'data-auren-gami')===1&&strpos($mobile,'id="mobile-menu"')!==false,'mobile template keeps original controls and receives exactly one widget');
header_check(header_fixture('header-aside.php',$desktop)===$desktop,'already integrated headers are never duplicated');
header_check(header_fixture('header-aside.php','<div>Future theme markup</div>')==='<div>Future theme markup</div>','unknown theme markup fails safely without DOM injection');
header_check(header_fixture('header-aside.php','<div class="header-aside-inner"></div>','/unrelated')==='<div class="header-aside-inner"></div>','unrelated templates are never intercepted');
header_check(empty($GLOBALS['auren_app_header_buffers']),'nested template buffer ownership is balanced');
$css=file_get_contents(WP_PLUGIN_DIR.'/auren-app-bridge/assets/gamification.css');
file_put_contents('/tmp/auren-header-fixture.html','<!doctype html><meta name="viewport" content="width=device-width, initial-scale=1"><style>body{margin:0;font:16px Arial;background:#001629;color:white}.desktop,.bb-mobile-header{display:flex;align-items:center;justify-content:space-between;padding:16px;box-sizing:border-box}.desktop>nav{margin-left:auto;margin-right:25px}.header-aside-inner,.header-aside{display:flex;align-items:center}button{width:65px;height:40px}.bb-mobile-header{display:none}@media(max-width:900px){.desktop{display:none}.bb-mobile-header{display:flex}}</style><style>'.$css.'</style><header><div class="desktop"><a>AUREN FX</a><nav id="navigation">Cursos · Comunidad</nav>'.$desktop.'</div>'.$mobile.'</header><script>window.aurenGamification={endpoint:location.origin+"/gamification",nonce:"local-fixture"}</script>');
echo "$count header checks passed against real GamiPress.\n";
