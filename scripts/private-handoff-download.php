<?php
/** Standalone, unactivated download helper. Install in plugins/auren-handoff-download/. */
require_once dirname(__DIR__, 3) . '/wp-load.php';

nocache_headers();
header('X-Robots-Tag: noindex, nofollow, noarchive');
header('X-Content-Type-Options: nosniff');
header('Cross-Origin-Resource-Policy: same-origin');

if (!is_user_logged_in()) {
    wp_safe_redirect(wp_login_url(plugins_url('auren-handoff-download/download.php')));
    exit;
}
if (!current_user_can('manage_options')) {
    wp_die('Esta descarga requiere la cuenta administradora de Auren.', 'Auren', array('response' => 403));
}
if (!is_readable(__DIR__ . '/manifest.php')) {
    wp_die('El archivo todavía se está preparando.', 'Auren', array('response' => 503));
}
$manifest = require __DIR__ . '/manifest.php';
if (!is_array($manifest) || time() > ($manifest['expires_at'] ?? 0)) {
    wp_die('Esta entrega temporal caducó. Solicita una nueva descarga.', 'Auren', array('response' => 410));
}
if (!in_array($_SERVER['REQUEST_METHOD'] ?? '', array('GET', 'HEAD'), true)) {
    header('Allow: GET, HEAD');
    status_header(405);
    exit;
}

$prefix = "<?php http_response_code(404); exit; __halt_compiler();\n";
$read_part = static function ($part) use ($prefix) {
    if (!preg_match('/^part-[0-9]{3}\.php$/D', $part['name'] ?? '')) {
        return false;
    }
    $file = __DIR__ . '/parts/' . $part['name'];
    if (!is_readable($file)) {
        return false;
    }
    $encoded = file_get_contents($file);
    if ($encoded === false || substr($encoded, 0, strlen($prefix)) !== $prefix) {
        return false;
    }
    $data = base64_decode(substr($encoded, strlen($prefix)), true);
    if ($data === false || strlen($data) !== $part['bytes'] || !hash_equals($part['sha256'], hash('sha256', $data))) {
        return false;
    }
    return $data;
};

// Validate the entire delivery before sending any archive bytes. Never include parts as PHP.
$digest = hash_init('sha256');
$bytes = 0;
foreach ($manifest['parts'] as $part) {
    $data = $read_part($part);
    if ($data === false) {
        wp_die('La entrega no está completa. Solicita una nueva descarga.', 'Auren', array('response' => 503));
    }
    hash_update($digest, $data);
    $bytes += strlen($data);
}
unset($data);
if ($bytes !== $manifest['bytes'] || !hash_equals($manifest['sha256'], hash_final($digest))) {
    wp_die('No se pudo verificar la entrega.', 'Auren', array('response' => 503));
}

while (ob_get_level() > 0) {
    if (!ob_end_clean()) {
        break;
    }
}
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="Auren-proyecto-completo-0.3.0.zip"');
header('Content-Length: ' . $manifest['bytes']);
header('Cache-Control: private, no-store, max-age=0');
header('Accept-Ranges: none');
if ($_SERVER['REQUEST_METHOD'] === 'HEAD') {
    exit;
}
foreach ($manifest['parts'] as $part) {
    $data = $read_part($part);
    if ($data === false) {
        exit;
    }
    echo $data;
    unset($data);
    flush();
}
exit;
