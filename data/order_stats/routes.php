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
    $raw_start = $request->get_param('start_date');
    $raw_end = $request->get_param('end_date');
    $end_date = $raw_end ?: date('Y-m-d');
    $start_date = $raw_start ?: date('Y-m-d', strtotime('-29 days'));
    $device_type = $request->get_param('device_type') ?: null;

    $report = $analyticsInstance->get_report($start_date, $end_date, $device_type);

    // "Gesamter Zeitraum" sends no dates. The bounded 30-day window above keeps the
    // per-day charts fast, but the status distribution must then reflect ALL orders
    // by current status (matching the order list) instead of the 30-day subset.
    if (!$raw_start && !$raw_end) {
        $all_time = $analyticsInstance->get_order_status_distribution_all_time();
        $report['order_metrics']['chart_data']['order_status_distribution'] = $all_time;
        $report['order_metrics']['current_period']['status_distribution'] = $all_time;
    }

    return new WP_REST_Response($report, 200);
}