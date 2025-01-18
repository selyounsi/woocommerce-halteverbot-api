<?php

use Utils\Stats\WPStats;

add_action('rest_api_init', function () {
    register_rest_route('wps', '/stats', [
        'methods' => 'GET',
        'callback' => 'get_visitor_purchase_stats',
        'permission_callback' => '__return_true', // Offener Zugriff
    ]);
});

/**
 * Hauptfunktion für die Besucher- und Käufer-Statistiken
 */
function get_visitor_purchase_stats() 
{

    $wp_stats = new WPStats(); 

    // Daten für verschiedene Zeiträume abrufen
    return new WP_REST_Response($wp_stats->getStats(), 200);
}