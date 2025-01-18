<?php

namespace Utils\Stats;

/**
 * WordPress Stats - Besucher- und Käufer-Statistiken
 */
class WPStats
{
    public function __construct()
    {
        // Konstruktor, falls später etwas hinzugefügt werden muss
    }

    /**
     * Hauptfunktion für die Besucher- und Käufer-Statistiken
     */
    public function getStats()
    {
        return [
            'today' => $this->getStatsPeriod('today'),
            'yesterday' => $this->getStatsPeriod('yesterday'),
            'week' => $this->getStatsPeriod('week'),
            'month' => $this->getStatsPeriod('month'),
            'last_7_days' => $this->getLast7DaysStats(),
        ];
    }

    /**
     * Helper-Funktion: Statistiken für einen bestimmten Zeitraum
     */
    public function getStatsPeriod($time_period)
    {
        $start_date = '';
        $end_date = '';

        switch ($time_period) {
            case 'today':
                $start_date = date('Y-m-d 00:00:00');
                $end_date = date('Y-m-d 23:59:59');
                break;
            case 'yesterday':
                $start_date = date('Y-m-d 00:00:00', strtotime('yesterday'));
                $end_date = date('Y-m-d 23:59:59', strtotime('yesterday'));
                break;
            case 'week':
                $start_date = date('Y-m-d 00:00:00', strtotime('last sunday'));
                $end_date = date('Y-m-d 23:59:59');
                break;
            case 'month':
                $start_date = date('Y-m-01 00:00:00');
                $end_date = date('Y-m-t 23:59:59');
                break;
        }

        $total_visitors = $this->getVisitors($time_period);
        $total_buyers = $this->getBuyers($start_date, $end_date);
        $conversion_rate = $total_visitors > 0
            ? round(($total_buyers / $total_visitors) * 100, 2)
            : '0%';

        return [
            'visitors' => $total_visitors,
            'buyers' => $total_buyers,
            'conversion_rate' => $conversion_rate,
        ];
    }

    /**
     * Statistiken für die letzten 7 Tage
     */
    public function getLast7DaysStats()
    {
        $total_visitors = 0;
        $total_buyers = 0;
        $days = [];

        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $start_date = $date . ' 00:00:00';
            $end_date = $date . ' 23:59:59';

            $daily_visitors = wp_statistics_visitor($date, true, true);
            $daily_buyers = $this->getBuyers($start_date, $end_date);

            $days[] = [
                'date' => $date,
                'visitors' => $daily_visitors,
                'buyers' => $daily_buyers,
                'conversion_rate' => $daily_visitors > 0
                    ? round(($daily_buyers / $daily_visitors) * 100, 2)
                    : '0%',
            ];

            $total_visitors += $daily_visitors;
            $total_buyers += $daily_buyers;
        }

        $conversion_rate = $total_visitors > 0
            ? round(($total_buyers / $total_visitors) * 100, 2)
            : '0%';

        return [
            'visitors' => $total_visitors,
            'buyers' => $total_buyers,
            'conversion_rate' => $conversion_rate,
            'days' => $days,
        ];
    }

    /**
     * Besucher im Zeitraum abrufen
     */
    private function getVisitors($time_period)
    {
        return wp_statistics_visitor($time_period);
    }

    /**
     * Käufer im Zeitraum abrufen
     */
    private function getBuyers($start_date, $end_date)
    {
        $orders = wc_get_orders([
            'limit' => -1,
            'date_created' => $start_date . '...' . $end_date,
        ]);

        $total_buyers = 0;
        foreach ($orders as $order) {
            $order_data = $order->get_data();
            if (isset($order_data['billing'])) {
                $total_buyers++;
            }
        }

        return $total_buyers;
    }
}
