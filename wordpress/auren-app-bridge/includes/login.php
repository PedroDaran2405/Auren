<?php
defined('ABSPATH') || exit;

/** Only the app's fixed, same-origin return URLs participate in this flow. */
function auren_app_is_return_url($url) {
    if(!is_string($url) || !$url){return false;}
    $parts=wp_parse_url($url);$home=wp_parse_url(home_url('/'));
    if(!$parts || (!empty($parts['host']) && strtolower($parts['host'])!==strtolower($home['host'])) || !empty($parts['user']) || !empty($parts['pass'])){return false;}
    if(!empty($parts['scheme']) && $parts['scheme']!==($home['scheme']??'https')){return false;}
    parse_str($parts['query']??'',$query);
    $path=$parts['path']??'/';
    return ($path===($home['path']??'/') && ($query['auren_app']??null)==='1') ||
        ($path===wp_parse_url(admin_url('admin-post.php'),PHP_URL_PATH) && ($query['action']??null)==='auren_app_authorize');
}

function auren_app_login_context() {
    return (isset($_REQUEST['auren_app_auth']) && $_REQUEST['auren_app_auth']==='1') || auren_app_is_return_url(wp_unslash($_REQUEST['redirect_to']??''));
}

add_filter('login_url',function($url,$redirect){
    return auren_app_is_return_url($redirect)?add_query_arg('auren_app_auth','1',$url):$url;
},10,2);

// BuddyBoss may redirect members to their dashboard instead of requested redirect_to.
// Restore only app return URLs; other WordPress logins keep their existing behavior.
function auren_app_login_redirect($redirect,$requested,$user){
    if($user instanceof WP_User && auren_app_is_return_url($requested)){return $requested;}
    return $redirect;
}
// BuddyBoss registers its redirect at PHP_INT_MAX. Attach after plugin initialization
// on wp-login.php, so its member-dashboard redirect cannot overwrite the app return.
add_action('login_init',function(){
    add_filter('login_redirect','auren_app_login_redirect',PHP_INT_MAX,3);
    add_filter('logout_redirect','auren_app_login_redirect',PHP_INT_MAX,3);
});

add_action('login_form',function(){
    if(auren_app_login_context()){echo '<input type="hidden" name="auren_app_auth" value="1">';}
});
add_filter('login_body_class',function($classes){if(auren_app_login_context()){$classes[]='auren-app-login';}return $classes;});
// Core's delayed autofocus clears an already-filled password after an error.
// Keep password-manager / fast retry input intact in the app login flow.
add_filter('enable_login_autofocus',function($enabled){return auren_app_login_context()?false:$enabled;});
add_action('login_enqueue_scripts',function(){
    if(auren_app_login_context()){wp_enqueue_style('auren-app-login',plugins_url('assets/login.css',dirname(__DIR__).'/auren-app-bridge.php'),array(),(string)filemtime(dirname(__DIR__).'/assets/login.css'));}
});
add_filter('login_headerurl',function($url){return auren_app_login_context()?add_query_arg('auren_app','1',home_url('/')):$url;});
add_filter('login_headertext',function($text){return auren_app_login_context()?'Auren FX':$text;});
add_filter('lostpassword_url',function($url){return auren_app_login_context()?add_query_arg(array('auren_app_auth'=>'1','redirect_to'=>add_query_arg('auren_app','1',home_url('/'))),$url):$url;});
add_filter('login_message',function($message){
    if(!auren_app_login_context()){return $message;}
    $action=isset($_REQUEST['action']) && is_string($_REQUEST['action'])?$_REQUEST['action']:'login';
    $logo=auren_app_branding()['logo_url']?:'https://www.aurenfx.com/wp-content/uploads/2026/07/ChatGPT-Image-9-jul-2026-10_53_16-p.m-e1783659259666.png';
    $brand='<div class="auren-login-brand"><a href="'.esc_url(add_query_arg('auren_app','1',home_url('/'))).'"><img src="'.esc_url($logo).'" width="224" height="32" alt="Auren FX"></a></div>';
    if(!in_array($action,array('login',''),true)){return $brand.$message;}
    return $brand.'<div class="auren-login-intro"><p class="auren-login-kicker">TU ACADEMIA, CONTIGO</p><h2>Qué gusto verte<br>de nuevo.</h2><p>Entra con tu cuenta de Auren y continúa donde te quedaste.</p></div>'.$message;
});
add_filter('login_site_html_link',function($link){
    return auren_app_login_context()?'<a href="'.esc_url(add_query_arg('auren_app','1',home_url('/'))).'">← Volver a Auren App</a>':$link;
});
add_action('login_footer',function(){
    if(!auren_app_login_context()){return;}
    echo '<script>(function(){function prepare(){var form=document.getElementById("loginform"),login=document.getElementById("user_login"),password=document.getElementById("user_pass");if(login){if(login.placeholder!=="Tu correo o usuario")login.placeholder="Tu correo o usuario";var label=document.querySelector("label[for=user_login]");if(label&&(label.textContent!=="Correo o usuario"||label.children.length))label.textContent="Correo o usuario";}if(form&&password){if(password.placeholder!=="Tu contraseña")password.placeholder="Tu contraseña";var label=document.querySelector("label[for=user_pass]");if(label&&(label.textContent!=="Contraseña"||label.children.length))label.textContent="Contraseña";var submit=document.getElementById("wp-submit");if(submit)submit.value="Entrar a mi academia";}document.querySelectorAll("a[href*=lostpassword]").forEach(function(a){if(a.textContent!=="¿Olvidaste tu contraseña?")a.textContent="¿Olvidaste tu contraseña?";});}prepare();document.addEventListener("DOMContentLoaded",prepare,{once:true});var target=document.getElementById("login");if(target)new MutationObserver(prepare).observe(target,{subtree:true,childList:true,attributes:true,attributeFilter:["placeholder"]});})();</script>';
},1000);
