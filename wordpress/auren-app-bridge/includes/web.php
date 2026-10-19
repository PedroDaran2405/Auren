<?php
defined('ABSPATH') || exit;

add_action('template_redirect',function() {
    if (!isset($_GET['auren_app']) || $_GET['auren_app'] !== '1') { return; }
    auren_app_private_headers();
    $file = dirname(__DIR__) . '/web/index.html';
    if (!is_readable($file)) { wp_die('La app está en preparación. Vuelve a la academia para continuar.', 'Auren', array('response'=>503)); }
    $bootstrap = array('api_url'=>rest_url('auren/v1/'),'site_url'=>home_url('/'),
        'nonce'=>is_user_logged_in() ? wp_create_nonce('wp_rest') : null,
        'login_url'=>wp_login_url(add_query_arg('auren_app','1',home_url('/'))),
        'register_url'=>wp_registration_url(),'lost_password_url'=>add_query_arg(array('auren_app_auth'=>'1','redirect_to'=>add_query_arg('auren_app','1',home_url('/'))),wp_lostpassword_url()),
        'environment'=>wp_get_environment_type());
    $html = file_get_contents($file);
    $asset_base = plugins_url('web/',dirname(__DIR__) . '/auren-app-bridge.php');
    $html = str_replace('"/_expo/','"' . esc_url($asset_base) . '_expo/',$html);
    $tag = '<meta name="robots" content="noindex,nofollow"><script>window.__AUREN_BOOTSTRAP__=' . wp_json_encode($bootstrap,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) . ';</script>';
    echo str_replace('</head>',$tag . '</head>',$html);
    exit;
},1);
