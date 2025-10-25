<?php

use Utils\Tracker\VisitorAnalytics;


add_action('rest_api_init', function () {
    register_rest_route('wc/v3', '/stats/all', [
        'methods'  => 'POST',
        'callback' => 'get_report',
        'permission_callback' => function () {
            return current_user_can('manage_woocommerce');
        }
    ]);
});

function get_report(WP_REST_Request $request) 
{
    $analyticsInstance = VisitorAnalytics::getAnalyticsInstance();

    // POST DATA
    $start_date = $request->get_param('start_date') ?? date('Y-m-d'); 
    $end_date = $request->get_param('end_date') ?? date('Y-m-d', strtotime('-29 days'));     
    $device_type = $request->get_param('device_type') ?? null;

    // RETURN DATA
    return new WP_REST_Response($analyticsInstance->get_report($start_date, $end_date, $device_type), 200);
}