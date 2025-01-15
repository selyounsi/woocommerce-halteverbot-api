<?php
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
    $statuses = wc_get_order_statuses();

    $data = [
        'total_orders' => 0,
        'status_counts' => [],
        'orders_today' => 0,
        'orders_this_week' => 0,
        'orders_this_month' => 0,
        'orders_this_year' => 0,
        'orders_last_7_days' => [
            'total' => 0,
            'days' => [],
        ],
    ];

    $all_orders = wc_get_orders(['limit' => -1, 'status' => array_keys($statuses)]);
    $total_orders = count($all_orders);
    $data['total_orders'] = $total_orders;

    // Zeitstempel für die Filterung von Bestellungen nach Datum
    $today = strtotime('today midnight');
    $this_week_start = strtotime('last sunday midnight');
    $this_month_start = strtotime('first day of this month midnight');
    $this_year_start = strtotime('first day of January this year midnight');
    $last_7_days_start = strtotime('-7 days'); // Zeitstempel für die letzten 7 Tage

    // Initialisierung des Arrays für die letzten 7 Tage
    for ($i = 0; $i < 7; $i++) {
        $day = strtotime("-$i days");
        $data['orders_last_7_days']['days'][date('Y-m-d', $day)] = 0;
    }

    foreach ($statuses as $status_key => $status_label) {
        $orders = wc_get_orders(['limit' => -1, 'status' => str_replace('wc-', '', $status_key)]);
        $count = count($orders);

        $total_revenue = 0;
        $customer_ids = [];
        $last_order_date = null;

        foreach ($orders as $order) {
            $total_revenue += $order->get_total();

            // Überprüfen, ob die Methode `get_customer_id` existiert
            if (method_exists($order, 'get_customer_id')) {
                $customer_ids[] = $order->get_customer_id();
            }

            $order_date = $order->get_date_created();
            if ($order_date && (!$last_order_date || $order_date > $last_order_date)) {
                $last_order_date = $order_date;
            }

            // Berechnung der Bestellungen für heute, diese Woche, diesen Monat, dieses Jahr und die letzten 7 Tage
            if ($order_date && $order_date->getTimestamp() >= $today) {
                $data['orders_today']++;
            }
            if ($order_date && $order_date->getTimestamp() >= $this_week_start) {
                $data['orders_this_week']++;
            }
            if ($order_date && $order_date->getTimestamp() >= $this_month_start) {
                $data['orders_this_month']++;
            }
            if ($order_date && $order_date->getTimestamp() >= $this_year_start) {
                $data['orders_this_year']++;
            }
            if ($order_date && $order_date->getTimestamp() >= $last_7_days_start) {
                $data['orders_last_7_days']['total']++;

                // Erhöht den Zähler für den jeweiligen Tag innerhalb der letzten 7 Tage
                $order_day = date('Y-m-d', $order_date->getTimestamp());
                if (isset($data['orders_last_7_days']['days'][$order_day])) {
                    $data['orders_last_7_days']['days'][$order_day]++;
                }
            }
        }

        $average_order_value = $count > 0 ? round($total_revenue / $count, 2) : 0;
        $percentage = $total_orders > 0 ? round(($count / $total_orders) * 100, 2) : 0;

        $data['status_counts'][] = [
            'status_key'   => str_replace('wc-', '', $status_key),
            'status_label' => $status_label,
            'count'        => $count,
            'percentage'   => $percentage,
            'total_revenue' => round($total_revenue, 2),
            'average_order_value' => $average_order_value,
            'last_order_date' => $last_order_date ? $last_order_date->date('Y-m-d H:i:s') : null,
        ];
    }

    // Berechne den prozentualen Anteil der Bestellungen für jeden Tag der letzten 7 Tage
    $total_last_7_days = $data['orders_last_7_days']['total'];
    if ($total_last_7_days > 0) {
        foreach ($data['orders_last_7_days']['days'] as $date => $count) {
            $percentage = round(($count / $total_last_7_days) * 100, 2);
            $data['orders_last_7_days']['days'][$date] = [
                'count' => $count,
                'percentage' => $percentage,
            ];
        }
    }

    return new WP_REST_Response($data, 200);
}