<?php

namespace Utils\Stats;

/**
 * Order Stats
 * @method public getOrderStats()
 */
class OrderStats 
{
    private $statuses;
    private $today;
    private $thisWeekStart;
    private $thisMonthStart;
    private $thisYearStart;
    private $last7DaysStart;
    private $data;

    public function __construct()
    {
        $this->statuses = wc_get_order_statuses();
        $this->today = strtotime('today midnight');
        $this->thisWeekStart = strtotime('last sunday midnight');
        $this->thisMonthStart = strtotime('first day of this month midnight');
        $this->thisYearStart = strtotime('first day of January this year midnight');
        $this->last7DaysStart = strtotime('-7 days');

        $this->initializeData();
    }

    /**
     * Initialize Data
     */
    private function initializeData()
    {
        $this->data = [
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

        for ($i = 0; $i < 7; $i++) {
            $day = strtotime("-$i days");
            $this->data['orders_last_7_days']['days'][date('Y-m-d', $day)] = 0;
        }
    }

    /**
     * Get Order Stats
     */
    public function getOrderStats()
    {
        $allOrders = wc_get_orders(['limit' => -1, 'status' => array_keys($this->statuses)]);
        $this->data['total_orders'] = count($allOrders);

        foreach ($this->statuses as $statusKey => $statusLabel) {
            $this->processOrdersByStatus($statusKey, $statusLabel);
        }

        $this->calculateLast7DaysPercentages();

        return $this->data;
    }

    /**
     * Process Orders By Status
     */
    private function processOrdersByStatus($statusKey, $statusLabel)
    {
        $orders = wc_get_orders(['limit' => -1, 'status' => str_replace('wc-', '', $statusKey)]);
        $count = count($orders);

        $totalRevenue = 0;
        $lastOrderDate = null;

        foreach ($orders as $order) {
            $totalRevenue += $order->get_total();
            $orderDate = $order->get_date_created();

            if ($orderDate && (!$lastOrderDate || $orderDate > $lastOrderDate)) {
                $lastOrderDate = $orderDate;
            }

            $this->updateDateFilteredCounts($orderDate);
        }

        $averageOrderValue = $count > 0 ? round($totalRevenue / $count, 2) : 0;
        $percentage = $this->data['total_orders'] > 0 ? round(($count / $this->data['total_orders']) * 100, 2) : 0;

        $this->data['status_counts'][] = [
            'status_key' => str_replace('wc-', '', $statusKey),
            'status_label' => $statusLabel,
            'count' => $count,
            'percentage' => $percentage,
            'total_revenue' => round($totalRevenue, 2),
            'average_order_value' => $averageOrderValue,
            'last_order_date' => $lastOrderDate ? $lastOrderDate->date('Y-m-d H:i:s') : null,
        ];
    }

    /**
     * Update Date Filtered Counts
     */
    private function updateDateFilteredCounts($orderDate)
    {
        if (!$orderDate) {
            return;
        }

        $timestamp = $orderDate->getTimestamp();

        if ($timestamp >= $this->today) {
            $this->data['orders_today']++;
        }

        if ($timestamp >= $this->thisWeekStart) {
            $this->data['orders_this_week']++;
        }

        if ($timestamp >= $this->thisMonthStart) {
            $this->data['orders_this_month']++;
        }

        if ($timestamp >= $this->thisYearStart) {
            $this->data['orders_this_year']++;
        }

        if ($timestamp >= $this->last7DaysStart) {
            $this->data['orders_last_7_days']['total']++;

            $orderDay = date('Y-m-d', $timestamp);
            if (isset($this->data['orders_last_7_days']['days'][$orderDay])) {
                $this->data['orders_last_7_days']['days'][$orderDay]++;
            }
        }
    }

    /**
     * Calculate last 7 Days Percentages
     */
    private function calculateLast7DaysPercentages()
    {
        $totalLast7Days = $this->data['orders_last_7_days']['total'];

        if ($totalLast7Days > 0) {
            foreach ($this->data['orders_last_7_days']['days'] as $date => $count) {
                $percentage = round(($count / $totalLast7Days) * 100, 2);
                $this->data['orders_last_7_days']['days'][$date] = [
                    'count' => $count,
                    'percentage' => $percentage,
                ];
            }
        }
    }
}