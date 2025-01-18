<?php

use Utils\Stats\OrderStats;
use Utils\Stats\WPStats;

add_action('rest_api_init', function () {
    register_rest_route('wc/v3', '/orders/stats', [
        'methods'  => 'GET',
        'callback' => 'get_orders_count',
        'permission_callback' => function () {
            return current_user_can('manage_woocommerce');
        }
    ]);
});

function get_orders_count() 
{
    $wp_stats = new WPStats(); 
    $orderStats = new OrderStats();

    return new WP_REST_Response([
        "orders" => $orderStats->getOrderStats() ?? null,
        "visitors" => $wp_stats->getStats() ?? null 
    ], 200);
}