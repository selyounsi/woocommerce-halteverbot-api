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

        // The overview KPIs ("Angegebener Zeitraum") otherwise keep the 30-day
        // fallback. Rebuild them all-time: counts come from the same source as the
        // order list (so they match the distribution), revenue is summed all-time.
        $counts = [];
        foreach ($all_time as $row) {
            $counts[$row['status']] = $row['count'];
        }
        $total_orders = array_sum($counts);
        $completed_orders = isset($counts['wc-completed']) ? $counts['wc-completed'] : 0;
        $revenue = $analyticsInstance->get_order_revenue_all_time();

        $range = isset($report['order_metrics']['range']) && is_array($report['order_metrics']['range'])
            ? $report['order_metrics']['range']
            : [];
        $range['total_orders'] = $total_orders;
        $range['completed_orders'] = $completed_orders;
        $range['completed_percentage'] = $total_orders > 0 ? round(($completed_orders / $total_orders) * 100, 1) : 0;
        $range['pending_orders'] = isset($counts['wc-pending']) ? $counts['wc-pending'] : 0;
        $range['cancelled_orders'] = isset($counts['wc-cancelled']) ? $counts['wc-cancelled'] : 0;
        $range['refunded_orders'] = isset($counts['wc-refunded']) ? $counts['wc-refunded'] : 0;
        $range['total_revenue'] = $revenue['total'];
        $range['completed_revenue'] = $revenue['completed'];
        $report['order_metrics']['range'] = $range;

        // Payment-method distribution is also period-bound; rebuild it all-time.
        $sources = $analyticsInstance->get_order_sources_all_time();
        if (!empty($sources)) {
            $report['order_metrics']['chart_data']['order_sources'] = $sources;
            $report['order_metrics']['current_period']['sources'] = $sources;
        }
    }

    return new WP_REST_Response($report, 200);
}