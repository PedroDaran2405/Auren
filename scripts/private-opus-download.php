<?php
require_once dirname(__DIR__, 3) . '/wp-load.php';
nocache_headers();
header('X-Robots-Tag: noindex, nofollow, noarchive');
header('X-Content-Type-Options: nosniff');
header('Cross-Origin-Resource-Policy: same-origin');
if (!is_user_logged_in()) { wp_safe_redirect(wp_login_url(plugins_url('auren-handoff-download/opus.php'))); exit; }
if (!current_user_can('manage_options')) { wp_die('Esta descarga requiere la cuenta administradora de Auren.', 'Auren', array('response'=>403)); }
$manifest = require __DIR__ . '/manifest.php';
if (time()>$manifest['expires_at']) { status_header(410); exit; }
$raw=file_get_contents(__DIR__.'/opus-content.php');
$prefix="<?php http_response_code(404); exit; __halt_compiler();\n";
if($raw===false || substr($raw,0,strlen($prefix))!==$prefix){status_header(503);exit;}
$data=base64_decode(substr($raw,strlen($prefix)),true);
if($data===false || !hash_equals('e43836e971a8ea58801eff01e4fbe02412a9ab4e2701a9df0b3afb245ff44dc4',hash('sha256',$data))){status_header(503);exit;}
while(ob_get_level()>0){if(!ob_end_clean()){break;}}
header('Content-Type: text/plain; charset=utf-8');
header('Content-Disposition: attachment; filename="AUREN-PARA-OPUS.txt"');
header('Content-Length: '.strlen($data));
header('Cache-Control: private, no-store, max-age=0');
if(($_SERVER['REQUEST_METHOD']??'')!=='HEAD'){echo $data;}
exit;
