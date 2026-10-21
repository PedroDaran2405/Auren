<?php
defined('ABSPATH') || exit;

// Integrate with the existing theme without replacing its templates or controls.
add_action('wp_enqueue_scripts',function(){
    if(!is_user_logged_in()||!auren_app_gami_type('points')||isset($_GET['auren_app'])){return;}
    $base=plugins_url('../assets/',__FILE__);
    wp_enqueue_style('auren-gamification',$base.'gamification.css',array(),AUREN_APP_VERSION);
    wp_enqueue_script('auren-gamification',$base.'gamification.js',array(),AUREN_APP_VERSION,true);
    wp_add_inline_script('auren-gamification','window.aurenGamification='.wp_json_encode(array(
        'endpoint'=>rest_url('auren/v1/gamification'),'nonce'=>wp_create_nonce('wp_rest'),
    )).';','before');
});
