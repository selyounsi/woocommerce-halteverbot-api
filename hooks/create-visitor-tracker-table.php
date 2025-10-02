<?php

use Utils\Tracker\VisitorTracker;

if (!defined('ABSPATH')) {
    exit;
}

// Tabellen nur im Admin
add_action('admin_init', function () {
    VisitorTracker::getInstance()->create_tables();
});

// Besucher nur tracken, wenn nicht eingeloggt
add_action('init', function () {
    if (!is_user_logged_in()) {
        // Script einbinden
        wp_enqueue_script(
            'visitor-tracker',
            WHA_PLUGIN_ASSETS_URL . '/js/visitor-tracker.js',
            ['jquery'],
            '1.0',
            true
        );

        wp_localize_script('visitor-tracker', 'VisitorTrackerData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('visitor_tracker_nonce'),
            'current_url' => esc_url(home_url($_SERVER['REQUEST_URI']))
        ]);
    }
}, 20);


// Ajax-Handler für WooCommerce Events
add_action('wp_ajax_nopriv_track_wc_event', function () {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'visitor_tracker_nonce')) {
        wp_send_json_error('Invalid nonce');
    }

    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
    $ip = explode(',', $ip)[0];
    
    VisitorTracker::getInstance()->track_wc_event([
        'event_type' => sanitize_text_field($_POST['event_type'] ?? ''),
        'extra_value' => sanitize_text_field($_POST['extra_value'] ?? ''),
        'product_id' => absint($_POST['product_id'] ?? 0),
        'quantity' => absint($_POST['quantity'] ?? 1),
        'order_id' => absint($_POST['order_id'] ?? 0),
        'url' => sanitize_text_field($_POST['url'] ?? ''),
        'user_agent' => sanitize_text_field($_POST['userAgent'] ?? ''),
        'ip' => $ip
    ]);

    wp_send_json_success();
});

// Ajax-Handler
add_action('wp_ajax_nopriv_track_visitor', function () {
    
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'visitor_tracker_nonce')) {
        wp_send_json_error('Invalid nonce');
    }

    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
    $ip = explode(',', $ip)[0];
    
    VisitorTracker::getInstance()->track_visit([
        'referrer' => sanitize_text_field($_POST['referrer'] ?? ''),
        'url' => sanitize_text_field($_POST['url'] ?? ''),
        'user_agent' => sanitize_text_field($_POST['userAgent'] ?? ''),
        'page_title' => sanitize_text_field($_POST['pageTitle'] ?? ''),
        'ip' => $ip
    ]);

    wp_send_json_success();
});