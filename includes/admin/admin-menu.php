<?php
// Sicherheit: Direktes Aufrufen von PHP-Dateien verhindern
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Hauptmenü und Submenüs hinzufügen
function halteverbot_app_add_menu() {
    // Hauptmenü
    add_menu_page(
        'Halteverbot App',
        'Halteverbot App',
        'manage_options',
        'halteverbot-app',
        'halteverbot_app_page_main',
        'dashicons-admin-generic',
        6                         
    );

    // Submenü: Direkt auf die interne Seite 'order_status' verweisen
    add_submenu_page(
        'halteverbot-app',
        'Order Status',
        'Order Status',
        'manage_options',
        'halteverbot-app-order-status',
        'halteverbot_app_page_order_status'
    );

    // Submenü 1: Halteverbotszonen verwalten
    add_submenu_page(
        'halteverbot-app',
        'Einstellungen',
        'Einstellungen',
        'manage_options',
        'halteverbot-app-settings',
        'halteverbot_app_page_settings'
    );
}
add_action('admin_menu', 'halteverbot_app_add_menu');

/**
 * MAIN PAGE
 */
function halteverbot_app_page_main() 
{
    include('page-main.php');
}

/**
 * ORDER STATUS PAGE
 */
function halteverbot_app_page_order_status() 
{
    $order_status_url = admin_url( 'edit.php?post_type=order_status' );
    wp_redirect( $order_status_url );
    exit;
}

/**
 * SETTINGS PAGE
 */
function halteverbot_app_page_settings() {
    include('page-settings.php');
}