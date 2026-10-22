<?php
defined('ABSPATH') || exit;

function auren_app_web_release() {
    $file=dirname(__DIR__).'/web/index.html';
    return array('version'=>AUREN_APP_VERSION,'build_id'=>is_readable($file)?hash_file('sha256',$file):'');
}

function auren_app_web_manifest() {
    $start=add_query_arg('auren_app','1',home_url('/'));
    $icons=array();
    foreach(array(192,512) as $size){
        $icons[]=array('src'=>add_query_arg(array('auren_app_icon'=>(string)$size,'v'=>AUREN_APP_VERSION),home_url('/')),'sizes'=>$size.'x'.$size,'type'=>'image/png','purpose'=>'any');
    }
    return array('id'=>$start,'name'=>'Auren','short_name'=>'Auren','description'=>'Tu academia, tu comunidad y tu aprendizaje conectado.',
        'lang'=>'es-MX','start_url'=>$start,'scope'=>home_url('/'),'display'=>'standalone','orientation'=>'portrait',
        'background_color'=>'#00101e','theme_color'=>'#00101e','icons'=>$icons);
}

add_action('template_redirect',function() {
    // Public installation metadata contains no user, session or course information.
    if(isset($_GET['auren_app_manifest']) && $_GET['auren_app_manifest']==='1'){
        auren_app_private_headers();header('Content-Type: application/manifest+json; charset=utf-8');header('X-Content-Type-Options: nosniff');
        echo wp_json_encode(auren_app_web_manifest(),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);exit;
    }
    if(isset($_GET['auren_app_release']) && $_GET['auren_app_release']==='1'){
        auren_app_private_headers();header('Content-Type: application/json; charset=utf-8');header('X-Content-Type-Options: nosniff');
        echo wp_json_encode(auren_app_web_release());exit;
    }
    if(isset($_GET['auren_app_icon'])){
        $size=$_GET['auren_app_icon'];
        if(!is_string($size) || !in_array($size,array('180','192','512'),true)){status_header(404);exit;}
        $icon=dirname(__DIR__).'/assets/icon-'.$size.'.png.b64';
        $bytes=is_readable($icon)?base64_decode(trim(file_get_contents($icon)),true):false;
        if($bytes===false){status_header(404);exit;}
        header('Content-Type: image/png');header('X-Content-Type-Options: nosniff');header('Cache-Control: public, max-age=86400');
        echo $bytes;exit;
    }
    if (!isset($_GET['auren_app']) || $_GET['auren_app'] !== '1') { return; }
    auren_app_private_headers();
    $file = dirname(__DIR__) . '/web/index.html';
    if (!is_readable($file)) { wp_die('La app está en preparación. Vuelve a la academia para continuar.', 'Auren', array('response'=>503)); }
    $bootstrap = array('api_url'=>rest_url('auren/v1/'),'site_url'=>home_url('/'),
        'nonce'=>is_user_logged_in() ? wp_create_nonce('wp_rest') : null,
        'login_url'=>wp_login_url(add_query_arg('auren_app','1',home_url('/'))),
        'register_url'=>wp_registration_url(),'lost_password_url'=>add_query_arg(array('auren_app_auth'=>'1','redirect_to'=>add_query_arg('auren_app','1',home_url('/'))),wp_lostpassword_url()),
        'environment'=>wp_get_environment_type(),'release'=>auren_app_web_release(),
        'release_url'=>add_query_arg('auren_app_release','1',home_url('/')));
    $html = file_get_contents($file);
    $asset_base = plugins_url('web/',dirname(__DIR__) . '/auren-app-bridge.php');
    $html = str_replace('"/_expo/','"' . esc_url($asset_base) . '_expo/',$html);
    $share_icon=add_query_arg(array('auren_app_icon'=>'512','v'=>AUREN_APP_VERSION),home_url('/'));
    $app_url=add_query_arg('auren_app','1',home_url('/'));
    $viewport_style='<style id="auren-viewport">html,body{margin:0;width:100%;height:100%;min-height:100%;background:#00101e;color-scheme:dark;overscroll-behavior:none}body{overflow:hidden}#root{height:100%;min-height:0;background:#00101e;isolation:isolate}@supports(height:100dvh){html,body{height:100dvh;min-height:100dvh}}</style>';
    $tag = '<meta name="robots" content="noindex,nofollow"><meta name="theme-color" content="#00101e"><meta name="apple-mobile-web-app-capable" content="yes"><meta name="apple-mobile-web-app-title" content="Auren"><meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">'
        .'<link rel="manifest" href="'.esc_url(add_query_arg('auren_app_manifest','1',home_url('/'))).'">'
        .'<link rel="apple-touch-icon" sizes="180x180" href="'.esc_url(add_query_arg(array('auren_app_icon'=>'180','v'=>AUREN_APP_VERSION),home_url('/'))).'">'
        .'<link rel="icon" type="image/png" sizes="192x192" href="'.esc_url(add_query_arg(array('auren_app_icon'=>'192','v'=>AUREN_APP_VERSION),home_url('/'))).'">'
        .'<meta property="og:type" content="website"><meta property="og:site_name" content="Auren"><meta property="og:title" content="Auren"><meta property="og:description" content="Tu academia, tu comunidad y tu aprendizaje conectado.">'
        .'<meta property="og:url" content="'.esc_url($app_url).'"><meta property="og:image" content="'.esc_url($share_icon).'"><meta property="og:image:secure_url" content="'.esc_url($share_icon).'"><meta property="og:image:type" content="image/png"><meta property="og:image:width" content="512"><meta property="og:image:height" content="512"><meta property="og:image:alt" content="Icono del toro de Auren">'
        .'<meta name="twitter:card" content="summary"><meta name="twitter:title" content="Auren"><meta name="twitter:image" content="'.esc_url($share_icon).'">'
        .$viewport_style.'<script>window.__AUREN_BOOTSTRAP__=' . wp_json_encode($bootstrap,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) . ';</script>';
    echo str_replace('</head>',$tag . '</head>',$html);
    exit;
},1);
