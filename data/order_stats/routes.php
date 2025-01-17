<?php

add_action('rest_api_init', function () {
    register_rest_route('wc/v3', '/orders/stats', [
        'methods'  => 'GET',
        'callback' => [new \Utils\Stats\OrderStats(), 'getOrderStats'],
        'permission_callback' => function () {
            return current_user_can('manage_woocommerce');
        }
    ]);
});