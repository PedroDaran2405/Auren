<?php
/**
 * Plugin Name: Auren App Bridge
 * Description: API privada y acceso móvil para la app propia de Auren. No modifica el tema.
 * Version: 0.1.2
 * Requires PHP: 8.1
 * License: GPL-2.0-or-later
 */
defined('ABSPATH') || exit;
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/academy.php';
require_once __DIR__ . '/includes/rest.php';
require_once __DIR__ . '/includes/web.php';
require_once __DIR__ . '/includes/admin.php';
register_activation_hook(__FILE__, 'auren_app_install');

function auren_app_install() {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $table = $wpdb->prefix . 'auren_app_codes';
    dbDelta("CREATE TABLE $table (
        request_id varchar(64) NOT NULL,
        challenge varchar(43) NOT NULL,
        state varchar(64) NOT NULL,
        code_hash varchar(64) NOT NULL DEFAULT '',
        user_id bigint(20) unsigned NOT NULL DEFAULT 0,
        expires bigint(20) unsigned NOT NULL,
        PRIMARY KEY  (request_id),
        KEY expires (expires)
    ) {$wpdb->get_charset_collate()};");
}

function auren_app_error($code, $message, $status = 400) {
    return new WP_Error($code, $message, array('status' => $status));
}

function auren_app_private_headers() {
    nocache_headers();
    header('Cache-Control: private, no-store, max-age=0');
    header('Referrer-Policy: no-referrer');
    header('X-Robots-Tag: noindex, nofollow');
}
