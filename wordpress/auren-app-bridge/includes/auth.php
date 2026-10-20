<?php
defined('ABSPATH') || exit;

// BuddyBoss private REST mode must allow the two public authorization steps.
// Exact routes only: consent still requires WordPress login and exchange requires PKCE.
add_filter('bb_exclude_endpoints_from_restriction', function ($routes) {
    return array_values(array_unique(array_merge((array)$routes, array('/auren/v1/auth/start','/auren/v1/auth/exchange'))));
});

function auren_app_secure_transport() {
    return is_ssl() || ('local' === wp_get_environment_type());
}

function auren_app_is_route() {
    $route = isset($_GET['rest_route']) ? (string) $_GET['rest_route'] : '';
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    return str_starts_with($route, '/auren/v1/') || str_contains((string) $path, '/wp-json/auren/v1/');
}

function auren_app_bearer() {
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/^Bearer ([1-9][0-9]*)\.([A-Za-z0-9]{43})$/D', $header, $m)) { return null; }
    return array((int) $m[1], $m[2]);
}

function auren_app_validate_session() {
    $pair = auren_app_bearer();
    if (!$pair || !auren_app_secure_transport()) { return false; }
    $session = WP_Session_Tokens::get_instance($pair[0])->get($pair[1]);
    return $session && !empty($session['auren_app']) && get_user_by('id', $pair[0]) ? $pair[0] : false;
}

add_filter('determine_current_user', function ($user_id) {
    if (!auren_app_is_route() || empty($_SERVER['HTTP_AUTHORIZATION'])) { return $user_id; }
    return auren_app_validate_session() ?: 0;
}, 30);

// Runs before core cookie authentication, which otherwise resets users without a REST nonce.
add_filter('rest_authentication_errors', function ($result) {
    if ($result || !auren_app_is_route() || empty($_SERVER['HTTP_AUTHORIZATION'])) { return $result; }
    $uid = auren_app_validate_session();
    if (!$uid) { return auren_app_error('session_expired', 'Tu sesión terminó. Vuelve a entrar.', 401); }
    wp_set_current_user($uid);
    return true;
}, 90);

function auren_app_permission() {
    if (!auren_app_secure_transport()) { return auren_app_error('https_required', 'Se requiere una conexión segura.', 503); }
    return auren_app_user_access(get_current_user_id());
}

function auren_app_user_access($uid) {
    if(!$uid){return auren_app_error('login_required', 'Inicia sesión para continuar.', 401);}
    if(!get_userdata($uid) || !user_can($uid,'read')){return auren_app_error('account_unavailable','Tu cuenta no está disponible.',403);}
    if((function_exists('bp_is_user_spammer') && bp_is_user_spammer($uid)) || (function_exists('bp_is_user_deleted') && bp_is_user_deleted($uid))){return auren_app_error('account_unavailable','Tu cuenta no está disponible.',403);}
    $pilot=array_map('absint',explode(',',(string)get_option('auren_app_pilot_users','')));
    if(get_option('auren_app_pilot_restricted',false) && !user_can($uid,'manage_options') && !in_array($uid,$pilot,true)){return auren_app_error('pilot_only','Tu cuenta todavía no está habilitada para esta versión de la app.',403);}
    return true;
}

function auren_app_auth_start(WP_REST_Request $request) {
    if (!auren_app_secure_transport()) { return auren_app_error('https_required', 'Se requiere HTTPS.', 503); }
    $challenge = (string) $request->get_param('challenge');
    $state = (string) $request->get_param('state');
    if (!preg_match('/^[A-Za-z0-9_-]{43}$/D', $challenge) || !preg_match('/^[a-f0-9]{64}$/D', $state)) {
        return auren_app_error('invalid_request', 'No se pudo iniciar el acceso. Intenta otra vez.');
    }
    // Do not trust X-Forwarded-For supplied by a client. Shared egress may share this limit.
    $key = 'auren_auth_' . hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $count = (int) get_transient($key);
    if ($count >= 30) { return auren_app_error('rate_limited', 'Espera un minuto antes de volver a intentar.', 429); }
    set_transient($key, $count + 1, MINUTE_IN_SECONDS);
    global $wpdb;
    $table = $wpdb->prefix . 'auren_app_codes';
    $wpdb->query($wpdb->prepare("DELETE FROM $table WHERE expires < %d", time()));
    $id = bin2hex(random_bytes(32));
    $saved = $wpdb->insert($table, array('request_id'=>$id, 'challenge'=>$challenge, 'state'=>$state, 'expires'=>time()+300));
    if (!$saved) { return auren_app_error('auth_unavailable', 'El acceso no está disponible por ahora.', 503); }
    return array('request_id'=>$id, 'authorize_url'=>add_query_arg(array('action'=>'auren_app_authorize','request_id'=>$id), admin_url('admin-post.php')));
}

function auren_app_authorize() {
    auren_app_private_headers();
    header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");
    if (!auren_app_secure_transport()) { wp_die('Se requiere HTTPS.', '', array('response'=>503)); }
    auth_redirect(); // WordPress/BuddyBoss owns passwords, verification and 2FA.
    $access=auren_app_user_access(get_current_user_id());
    if(is_wp_error($access)){wp_die(esc_html($access->get_error_message()),'Auren',array('response'=>403));}
    global $wpdb;
    $table = $wpdb->prefix . 'auren_app_codes';
    $id = sanitize_text_field(wp_unslash($_GET['request_id'] ?? ''));
    $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE request_id=%s AND expires>=%d AND user_id=0", $id, time()));
    if (!$row) { wp_die('Este acceso venció. Vuelve a la app e intenta de nuevo.', '', array('response'=>400)); }
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        check_admin_referer('auren_app_consent_' . $id);
        $code = bin2hex(random_bytes(32));
        $updated = $wpdb->query($wpdb->prepare("UPDATE $table SET code_hash=%s,user_id=%d,expires=%d WHERE request_id=%s AND user_id=0 AND expires>=%d", hash('sha256',$code),get_current_user_id(),time()+60,$id,time()));
        if (1 !== $updated) { wp_die('Este acceso ya fue utilizado.', '', array('response'=>409)); }
        // Fixed registered redirect. No caller-controlled destinations, tokens or passwords in URL.
        wp_redirect('auren://auth/callback?' . http_build_query(array('request_id'=>$id,'code'=>$code,'state'=>$row->state)), 302, 'Auren');
        exit;
    }
    echo '<!doctype html><html lang="es"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Conectar Auren</title><body style="background:#000918;color:#f6f8ff;font:18px system-ui;padding:32px;max-width:540px;margin:auto"><h1>Conecta tu academia</h1><p>La app Auren podrá consultar tus cursos, guardar tu aprendizaje, gestionar tu perfil y avisos y participar en la comunidad con los permisos de esta cuenta. La sesión dura una hora.</p><p>Cuenta: <strong>' . esc_html(wp_get_current_user()->display_name) . '</strong></p><form method="post">';
    wp_nonce_field('auren_app_consent_' . $id);
    $switch_url=wp_logout_url(add_query_arg(array('action'=>'auren_app_authorize','request_id'=>$id),admin_url('admin-post.php')));
    echo '<button style="padding:16px;background:#3d5cff;color:white;border:0;border-radius:12px;font:inherit">Continuar en Auren</button></form><p><a style="color:#aeb4ff" href="'.esc_url($switch_url).'">Usar otra cuenta</a></p><p>Puedes cerrar esta ventana para cancelar.</p></body></html>';
    exit;
}
add_action('admin_post_auren_app_authorize', 'auren_app_authorize');
add_action('admin_post_nopriv_auren_app_authorize', 'auren_app_authorize');

function auren_app_auth_exchange(WP_REST_Request $request) {
    if (!auren_app_secure_transport()) { return auren_app_error('https_required', 'Se requiere HTTPS.', 503); }
    $verifier = (string) $request->get_param('verifier');
    $code = (string) $request->get_param('code');
    if (!preg_match('/^[A-Za-z0-9._~-]{43,128}$/D', $verifier) || !preg_match('/^[a-f0-9]{64}$/D', $code)) { return auren_app_error('invalid_grant', 'El acceso no es válido.', 401); }
    global $wpdb;
    $table = $wpdb->prefix . 'auren_app_codes';
    $id = (string) $request->get_param('request_id');
    $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE request_id=%s AND expires>=%d AND user_id>0", $id, time()));
    $challenge = rtrim(strtr(base64_encode(hash('sha256',$verifier,true)), '+/', '-_'), '=');
    if (!$row || !hash_equals($row->challenge,$challenge) || !hash_equals($row->code_hash,hash('sha256',$code))) { return auren_app_error('invalid_grant', 'El acceso venció o no es válido.', 401); }
    // Atomic consumption makes simultaneous/replayed exchanges fail.
    $deleted = $wpdb->query($wpdb->prepare("DELETE FROM $table WHERE request_id=%s AND code_hash=%s AND expires>=%d", $id,$row->code_hash,time()));
    if (1 !== $deleted || !get_user_by('id',$row->user_id)) { return auren_app_error('invalid_grant', 'El acceso ya fue utilizado.', 401); }
    $access=auren_app_user_access((int)$row->user_id);
    if(is_wp_error($access)){return $access;}
    $manager = WP_Session_Tokens::get_instance((int)$row->user_id);
    $expires = time() + HOUR_IN_SECONDS;
    $token = $manager->create($expires);
    $session = $manager->get($token);
    $session['auren_app'] = true;
    $manager->update($token,$session);
    return array('access_token'=>$row->user_id . '.' . $token,'expires_at'=>$expires,'token_type'=>'Bearer');
}

function auren_app_logout() {
    $pair = auren_app_bearer();
    if ($pair) { WP_Session_Tokens::get_instance($pair[0])->destroy($pair[1]); }
    else { wp_logout(); }
    return array('logged_out'=>true);
}
