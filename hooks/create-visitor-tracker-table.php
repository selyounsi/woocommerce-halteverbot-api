<?php

use Utils\VisitorTracker;

if (!defined('ABSPATH')) {
    exit;
}

// // Tabellen nur im Admin
// add_action('admin_init', function () {
//     VisitorTracker::getInstance()->create_tables();
// });

// // Besucher nur tracken, wenn nicht eingeloggt
// add_action('init', function () {
//     if (!is_user_logged_in()) {
//         // Script einbinden
//         wp_enqueue_script(
//             'visitor-tracker',
//             WHA_PLUGIN_ASSETS_URL . '/js/visitor-tracker.js',
//             ['jquery'],
//             null,
//             true
//         );

//         wp_localize_script('visitor-tracker', 'VisitorTrackerData', [
//             'ajax_url' => admin_url('admin-ajax.php'),
//             'nonce'    => wp_create_nonce('visitor_tracker_nonce'),
//             'current_url' => esc_url(home_url($_SERVER['REQUEST_URI']))
//         ]);
//     }
// }, 20);

// // Ajax-Handler
// add_action('wp_ajax_nopriv_track_visitor', function () {
//     check_ajax_referer('visitor_tracker_nonce', 'nonce');

//     $referrer = sanitize_text_field($_POST['referrer'] ?? '');
//     $url = sanitize_text_field($_POST['url'] ?? '');
//     $userAgent = sanitize_text_field($_POST['userAgent'] ?? '');

//     VisitorTracker::getInstance()->track_visit([
//         'referrer' => $referrer,
//         'url' => $url,
//         'user_agent' => $userAgent
//     ]);

//     wp_send_json_success();
// });
