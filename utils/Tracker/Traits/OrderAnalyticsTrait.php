<?php

namespace Utils\Tracker\Traits;

trait OrderAnalyticsTrait 
{
    private $table_orders;
    private $table_order_items;

    public function init_order_tables() {
        $this->table_orders = $this->wpdb->prefix . 'wc_orders';
        $this->table_order_items = $this->wpdb->prefix . 'wc_order_items';
    }

    /**
     * Holt alle verfügbaren WooCommerce Bestellstatus
     */
    private function get_wc_order_statuses() {
        return wc_get_order_statuses(); // Gibt Array zurück: ['wc-pending' => 'Pending', 'wc-processing' => 'Processing', ...]
    }

    /**
     * Erstellt dynamische CASE-Statements für alle Status
     */
    private function get_status_case_statements() {
        $statuses = $this->get_wc_order_statuses();
        $cases = [];
        
        foreach ($statuses as $status_key => $status_label) {
            $clean_status = str_replace('wc-', '', $status_key);
            $cases[] = "COUNT(CASE WHEN status = '{$status_key}' THEN 1 END) as {$clean_status}_orders";
            $cases[] = "SUM(CASE WHEN status = '{$status_key}' THEN total_amount ELSE 0 END) as {$clean_status}_revenue";
        }
        
        return implode(",\n                ", $cases);
    }

    /**
     * Bestell-Statistiken für Zeitraum
     */
    public function get_order_stats($start_date = null, $end_date = null) {
        $this->init_order_tables();
        
        $where_clause = '';
        $params = [];
        
        if ($start_date && $end_date) {
            $where_clause = ' WHERE DATE(date_created_gmt) BETWEEN %s AND %s';
            $params = [$start_date, $end_date];
        }
        
        $status_cases = $this->get_status_case_statements();
        
        $sql = $this->wpdb->prepare(
            "SELECT 
                COUNT(*) as total_orders,
                SUM(total_amount) as total_revenue,
                AVG(total_amount) as avg_order_value,
                COUNT(DISTINCT customer_id) as unique_customers,
                {$status_cases}
             FROM {$this->table_orders}
             {$where_clause}",
            $params
        );
        
        return $this->wpdb->get_row($sql, ARRAY_A);
    }

    /**
     * Bestellungen der letzten 7 Tage
     */
    public function get_order_stats_7d() {
        $end_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-6 days'));
        return $this->get_order_stats($start_date, $end_date);
    }

    /**
     * Bestellungen der letzten 30 Tage
     */
    public function get_order_stats_30d() {
        $end_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-29 days'));
        return $this->get_order_stats($start_date, $end_date);
    }

    /**
     * Status-Verteilung für Zeitraum
     */
    public function get_order_status_distribution($start_date = null, $end_date = null) {
        $this->init_order_tables();
        
        $where_clause = '';
        $params = [];
        
        if ($start_date && $end_date) {
            $where_clause = ' WHERE DATE(date_created_gmt) BETWEEN %s AND %s';
            $params = [$start_date, $end_date];
        }
        
        $sql = $this->wpdb->prepare(
            "SELECT 
                status,
                COUNT(*) as count,
                SUM(total_amount) as revenue,
                ROUND((COUNT(*) * 100.0 / SUM(COUNT(*)) OVER()), 1) as percentage
             FROM {$this->table_orders}
             {$where_clause}
             GROUP BY status
             ORDER BY count DESC",
            $params
        );
        
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * Tägliche Bestellungen für Chart (30 Tage)
     */
    public function get_daily_orders_30d() {
        $end_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-29 days'));
        
        // Holen des korrekten completed Status
        $completed_status = 'wc-completed';
        
        $daily_data = [];
        for ($i = 29; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-$i days"));
            $daily_data[$day] = [
                'orders' => 0,
                'revenue' => 0,
                'completed_orders' => 0,
                'completed_revenue' => 0
            ];
        }

        $results = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT 
                DATE(date_created_gmt) as date,
                COUNT(*) as orders,
                SUM(total_amount) as revenue,
                COUNT(CASE WHEN status = %s THEN 1 END) as completed_orders,
                SUM(CASE WHEN status = %s THEN total_amount ELSE 0 END) as completed_revenue
             FROM {$this->table_orders}
             WHERE DATE(date_created_gmt) BETWEEN %s AND %s
             GROUP BY DATE(date_created_gmt)
             ORDER BY date ASC",
            $completed_status, $completed_status, $start_date, $end_date
        ), ARRAY_A);

        foreach ($results as $row) {
            $date = $row['date'];
            if (isset($daily_data[$date])) {
                $daily_data[$date] = [
                    'orders' => (int)$row['orders'],
                    'revenue' => round($row['revenue'], 2),
                    'completed_orders' => (int)$row['completed_orders'],
                    'completed_revenue' => round($row['completed_revenue'], 2)
                ];
            }
        }

        return $daily_data;
    }

    /**
     * Tägliche Bestellungen für Chart (7 Tage)
     */
    public function get_daily_orders_7d() {
        $end_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-6 days'));
        
        // Holen des korrekten completed Status
        $completed_status = 'wc-completed';
        
        $daily_data = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-$i days"));
            $daily_data[$day] = [
                'orders' => 0,
                'revenue' => 0,
                'completed_orders' => 0,
                'completed_revenue' => 0
            ];
        }

        $results = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT 
                DATE(date_created_gmt) as date,
                COUNT(*) as orders,
                SUM(total_amount) as revenue,
                COUNT(CASE WHEN status = %s THEN 1 END) as completed_orders,
                SUM(CASE WHEN status = %s THEN total_amount ELSE 0 END) as completed_revenue
             FROM {$this->table_orders}
             WHERE DATE(date_created_gmt) BETWEEN %s AND %s
             GROUP BY DATE(date_created_gmt)
             ORDER BY date ASC",
            $completed_status, $completed_status, $start_date, $end_date
        ), ARRAY_A);

        foreach ($results as $row) {
            $date = $row['date'];
            if (isset($daily_data[$date])) {
                $daily_data[$date] = [
                    'orders' => (int)$row['orders'],
                    'revenue' => round($row['revenue'], 2),
                    'completed_orders' => (int)$row['completed_orders'],
                    'completed_revenue' => round($row['completed_revenue'], 2)
                ];
            }
        }

        return $daily_data;
    }

    /**
     * Monatliche Bestellungen für Chart (12 Monate)
     */
    public function get_monthly_orders_12m() {
        $end_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-11 months'));
        
        // Holen des korrekten completed Status
        $completed_status = 'wc-completed';
        
        $sql = $this->wpdb->prepare(
            "SELECT 
                DATE_FORMAT(date_created_gmt, '%Y-%m') as month,
                COUNT(*) as total_orders,
                SUM(total_amount) as total_revenue,
                AVG(total_amount) as avg_order_value,
                COUNT(CASE WHEN status = %s THEN 1 END) as completed_orders,
                SUM(CASE WHEN status = %s THEN total_amount ELSE 0 END) as completed_revenue
             FROM {$this->table_orders}
             WHERE DATE(date_created_gmt) BETWEEN %s AND %s
             GROUP BY DATE_FORMAT(date_created_gmt, '%Y-%m')
             ORDER BY month ASC",
            $completed_status, $completed_status, $start_date, $end_date
        );
        
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * Top Produkte nach Umsatz für Zeitraum
     */
    public function get_top_products_by_revenue($start_date = null, $end_date = null, $limit = 10) {
        $this->init_order_tables();
        
        $where_clause = '';
        $params = [];
        $completed_status = 'wc-completed';
        
        if ($start_date && $end_date) {
            $where_clause = ' WHERE o.status = %s AND DATE(o.date_created_gmt) BETWEEN %s AND %s';
            $params = [$completed_status, $start_date, $end_date];
        } else {
            $where_clause = ' WHERE o.status = %s';
            $params = [$completed_status];
        }
        
        $params[] = $limit;
        
        $sql = $this->wpdb->prepare(
            "SELECT 
                oi.product_id,
                oi.product_name,
                SUM(oi.quantity) as total_quantity,
                SUM(oi.total_price) as total_revenue,
                COUNT(DISTINCT oi.order_id) as order_count,
                ROUND(AVG(oi.total_price / oi.quantity), 2) as avg_product_price
             FROM {$this->table_order_items} oi
             INNER JOIN {$this->table_orders} o ON oi.order_id = o.id
             {$where_clause}
             GROUP BY oi.product_id, oi.product_name
             ORDER BY total_revenue DESC
             LIMIT %d",
            $params
        );
        
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * Herkunft der Bestellungen (Payment Methoden)
     */
    public function get_order_sources($start_date = null, $end_date = null) {
        $this->init_order_tables();
        
        $where_clause = '';
        $params = [];
        
        if ($start_date && $end_date) {
            $where_clause = ' WHERE DATE(date_created_gmt) BETWEEN %s AND %s';
            $params = [$start_date, $end_date];
        }
        
        $sql = $this->wpdb->prepare(
            "SELECT 
                payment_method,
                COUNT(*) as count,
                SUM(total_amount) as revenue,
                AVG(total_amount) as avg_order_value,
                ROUND((COUNT(*) * 100.0 / SUM(COUNT(*)) OVER()), 1) as percentage
             FROM {$this->table_orders}
             {$where_clause}
             GROUP BY payment_method
             ORDER BY count DESC",
            $params
        );
        
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * Kunden-Wiederholungsrate
     */
    public function get_customer_repeat_rate($start_date = null, $end_date = null) {
        $this->init_order_tables();
        
        $where_clause = '';
        $params = [];
        
        if ($start_date && $end_date) {
            $where_clause = ' WHERE DATE(date_created_gmt) BETWEEN %s AND %s';
            $params = [$start_date, $end_date];
        }
        
        $sql = $this->wpdb->prepare(
            "SELECT 
                COUNT(DISTINCT customer_id) as total_customers,
                COUNT(CASE WHEN order_count > 1 THEN 1 END) as repeat_customers,
                ROUND((COUNT(CASE WHEN order_count > 1 THEN 1 END) * 100.0 / COUNT(DISTINCT customer_id)), 1) as repeat_rate
             FROM (
                 SELECT 
                     customer_id,
                     COUNT(*) as order_count
                 FROM {$this->table_orders}
                 {$where_clause}
                 AND customer_id IS NOT NULL
                 GROUP BY customer_id
             ) as customer_orders",
            $params
        );
        
        return $this->wpdb->get_row($sql, ARRAY_A);
    }

    /**
     * Durchschnittliche Bestellwerte nach Status
     */
    public function get_avg_order_value_by_status($start_date = null, $end_date = null) {
        $this->init_order_tables();
        
        $where_clause = '';
        $params = [];
        
        if ($start_date && $end_date) {
            $where_clause = ' WHERE DATE(date_created_gmt) BETWEEN %s AND %s';
            $params = [$start_date, $end_date];
        }
        
        $sql = $this->wpdb->prepare(
            "SELECT 
                status,
                COUNT(*) as order_count,
                AVG(total_amount) as avg_order_value,
                MIN(total_amount) as min_order_value,
                MAX(total_amount) as max_order_value
             FROM {$this->table_orders}
             {$where_clause}
             GROUP BY status
             ORDER BY avg_order_value DESC",
            $params
        );
        
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * Stündliche Bestellungs-Verteilung für Heatmap
     */
    public function get_order_time_heatmap($start_date = null, $end_date = null) {
        $this->init_order_tables();
        
        $where_clause = '';
        $params = [];
        
        if ($start_date && $end_date) {
            $where_clause = ' WHERE DATE(date_created_gmt) BETWEEN %s AND %s';
            $params = [$start_date, $end_date];
        }
        
        $sql = $this->wpdb->prepare(
            "SELECT 
                HOUR(date_created_gmt) as hour,
                DAYNAME(date_created_gmt) as day,
                COUNT(*) as orders,
                AVG(total_amount) as avg_order_value
             FROM {$this->table_orders}
             {$where_clause}
             GROUP BY HOUR(date_created_gmt), DAYNAME(date_created_gmt)
             ORDER BY FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), hour",
            $params
        );
        
        return $this->wpdb->get_results($sql, ARRAY_A);
    }
}