<?php

namespace Utils\Tracker;

class VisitorAnalytics extends VisitorTracker 
{
    private static $analytics_instance = null;

    public static function getAnalyticsInstance(): self {
        if (self::$analytics_instance === null) {
            self::$analytics_instance = new self();
        }
        return self::$analytics_instance;
    }

    public function __construct() {
        parent::__construct(); 
    }

    // --- Grundlegende Besucherstatistiken ---

    private function query_count($where_sql = '', $params = []) {
        $sql = "SELECT COUNT(DISTINCT session_id) FROM {$this->table_logs} WHERE 1=1 $where_sql";
        return (int) $this->wpdb->get_var($this->wpdb->prepare($sql, $params));
    }

    public function visitors_today() {
        $date = current_time('Y-m-d');
        return $this->query_count(' AND DATE(visit_time) = %s', [$date]);
    }

    public function visitors_yesterday() {
        $date = date('Y-m-d', strtotime('-1 day', current_time('timestamp')));
        return $this->query_count(' AND DATE(visit_time) = %s', [$date]);
    }

    public function visitors_this_week() {
        $start = date('Y-m-d', strtotime('monday this week', current_time('timestamp')));
        $end = date('Y-m-d', strtotime('sunday this week', current_time('timestamp')));
        return $this->query_count(' AND DATE(visit_time) BETWEEN %s AND %s', [$start, $end]);
    }

    public function visitors_this_month() {
        $month = date('Y-m', current_time('timestamp'));
        return $this->query_count(' AND DATE_FORMAT(visit_time, "%Y-%m") = %s', [$month]);
    }

    public function visitors_this_year() {
        $year = date('Y', current_time('timestamp'));
        return $this->query_count(' AND YEAR(visit_time) = %s', [$year]);
    }

    public function visitors_by_period($start_date, $end_date) {
        return $this->query_count(' AND DATE(visit_time) BETWEEN %s AND %s', [$start_date, $end_date]);
    }

    // --- Erweiterte Analytik ---

    public function visitors_by_referrer() {
        $sql = "SELECT 
            CASE 
                WHEN source_channel = 'organic' THEN 'Organische Suche'
                WHEN source_channel = 'campaign' THEN 'Kampagne'
                WHEN source_channel = 'social' THEN 'Social Media'
                WHEN source_channel = 'direct' THEN 'Direkt'
                ELSE source_channel
            END as source,
            COUNT(*) as count
            FROM {$this->table_logs}
            GROUP BY source_channel
            ORDER BY count DESC";
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    public function visitors_by_device() {
        $sql = "SELECT device_type, device_brand, device_model, COUNT(*) as count 
                FROM {$this->table_logs} 
                GROUP BY device_type, device_brand, device_model 
                ORDER BY count DESC";
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    public function visitors_by_browser() {
        $sql = "SELECT browser_name, browser_version, platform, COUNT(*) as count 
                FROM {$this->table_logs} 
                GROUP BY browser_name, browser_version, platform 
                ORDER BY count DESC";
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    public function visitors_by_country() {
        $sql = "SELECT country_code, country_name, COUNT(*) as count 
                FROM {$this->table_logs} 
                WHERE country_code != '' 
                GROUP BY country_code, country_name 
                ORDER BY count DESC";
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    public function page_views() {
        $sql = "SELECT url, page_title, COUNT(*) as count 
                FROM {$this->table_logs} 
                GROUP BY url, page_title 
                ORDER BY count DESC 
                LIMIT 20";
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    public function popular_keywords() {
        $sql = "SELECT keywords, COUNT(*) as count 
                FROM {$this->table_logs} 
                WHERE keywords != '' 
                GROUP BY keywords 
                ORDER BY count DESC 
                LIMIT 20";
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    // --- Session-basierte Analytik ---

    public function average_session_duration() {
        $sql = "SELECT AVG(session_duration) as avg_duration FROM (
            SELECT session_id, 
                   TIMESTAMPDIFF(SECOND, MIN(visit_time), MAX(visit_time)) as session_duration
            FROM {$this->table_logs}
            GROUP BY session_id
            HAVING COUNT(*) > 1
        ) as sessions";
        return (int) $this->wpdb->get_var($sql);
    }

    public function pages_per_session() {
        $sql = "SELECT AVG(page_count) as avg_pages FROM (
            SELECT session_id, COUNT(*) as page_count
            FROM {$this->table_logs}
            GROUP BY session_id
        ) as sessions";
        return round($this->wpdb->get_var($sql), 1);
    }

    public function bounce_rate() {
        $total_sessions = $this->wpdb->get_var("SELECT COUNT(DISTINCT session_id) FROM {$this->table_logs}");
        $single_page_sessions = $this->wpdb->get_var("
            SELECT COUNT(*) FROM (
                SELECT session_id FROM {$this->table_logs} 
                GROUP BY session_id 
                HAVING COUNT(*) = 1
            ) as single_page
        ");
        
        if ($total_sessions > 0) {
            return round(($single_page_sessions / $total_sessions) * 100, 1);
        }
        return 0;
    }

    public function average_time_on_page() {
        $sql = "SELECT AVG(time_on_page) as avg_time 
                FROM {$this->table_logs} 
                WHERE time_on_page > 0";
        return round($this->wpdb->get_var($sql), 1);
    }

    // --- WooCommerce Event Auswertungen ---

    public function wc_events_count_by_type() {
        $sql = "SELECT event_type, COUNT(*) as count 
                FROM {$this->table_wc_events} 
                GROUP BY event_type 
                ORDER BY count DESC";
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    public function wc_top_viewed_products($limit = 10) {
        $sql = $this->wpdb->prepare(
            "SELECT product_id, COUNT(*) as views 
             FROM {$this->table_wc_events} 
             WHERE event_type = %s 
             GROUP BY product_id 
             ORDER BY views DESC 
             LIMIT %d", 
            'product_view', 
            $limit
        );
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    public function wc_top_added_to_cart_products($limit = 10) {
        $sql = $this->wpdb->prepare(
            "SELECT product_id, SUM(quantity) as qty_added 
             FROM {$this->table_wc_events} 
             WHERE event_type = %s 
             GROUP BY product_id 
             ORDER BY qty_added DESC 
             LIMIT %d", 
            'add_to_cart', 
            $limit
        );
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    public function wc_order_count() {
        $sql = $this->wpdb->prepare(
            "SELECT COUNT(DISTINCT order_id) 
             FROM {$this->table_wc_events} 
             WHERE event_type = %s", 
            'order_complete'
        );
        return (int) $this->wpdb->get_var($sql);
    }

    public function wc_avg_quantity_per_order() {
        $sql = "SELECT AVG(qty_per_order) FROM (
            SELECT SUM(quantity) as qty_per_order, order_id 
            FROM {$this->table_wc_events}
            WHERE event_type = 'add_to_cart'
            GROUP BY order_id
        ) as t";
        return round($this->wpdb->get_var($sql), 2);
    }

    public function wc_conversion_rate() {
        $total_visitors = $this->wpdb->get_var("SELECT COUNT(DISTINCT session_id) FROM {$this->table_logs}");
        $total_orders = $this->wc_order_count();
        
        if ($total_visitors > 0) {
            return round(($total_orders / $total_visitors) * 100, 2);
        }
        return 0;
    }


    // --- Kompletter Report ---

    public function get_report($start_date, $end_date): array {
        return [
            'total_visits' => $this->visitors_by_period($start_date, $end_date),
            'session_metrics' => [
                'avg_duration' => $this->get_avg_session_duration_by_period($start_date, $end_date),
                'avg_pages' => $this->get_avg_pages_per_session_by_period($start_date, $end_date),
                'bounce_rate' => $this->get_bounce_rate_by_period($start_date, $end_date),
                'avg_time_on_page' => $this->get_avg_time_on_page_by_period($start_date, $end_date)
            ],
            'entry_pages' => $this->entry_pages_by_period($start_date, $end_date, 10),
            'exit_pages' => $this->exit_pages_by_period($start_date, $end_date, 10),
            'exit_rates' => $this->exit_rates_by_period($start_date, $end_date, 10),
            'devices' => $this->get_devices_by_period($start_date, $end_date),
            'browsers' => $this->get_browsers_by_period($start_date, $end_date),
            'countries' => $this->get_countries_by_period($start_date, $end_date),
            'traffic_sources' => $this->get_traffic_sources_by_period($start_date, $end_date),
            'pages' => $this->get_pages_by_period($start_date, $end_date),
            'keywords' => $this->get_keywords_by_period($start_date, $end_date),
            'wc_metrics' => [
                'events' => $this->get_wc_events_by_period($start_date, $end_date),
                'conversion_rate' => $this->get_wc_conversion_rate_by_period($start_date, $end_date),
                'revenue' => $this->wc_revenue_by_period($start_date, $end_date)
            ]
        ];
    }

    // --- Hilfsmethoden für Perioden-Filter ---

    private function get_avg_session_duration_by_period($start_date, $end_date) {
        $sql = $this->wpdb->prepare(
            "SELECT AVG(session_duration) FROM (
                SELECT session_id, TIMESTAMPDIFF(SECOND, MIN(visit_time), MAX(visit_time)) as session_duration
                FROM {$this->table_logs}
                WHERE DATE(visit_time) BETWEEN %s AND %s
                GROUP BY session_id
                HAVING COUNT(*) > 1
            ) as sessions",
            $start_date, $end_date
        );
        return (int) $this->wpdb->get_var($sql);
    }

    private function get_avg_pages_per_session_by_period($start_date, $end_date) {
        $sql = $this->wpdb->prepare(
            "SELECT AVG(page_count) FROM (
                SELECT session_id, COUNT(*) as page_count
                FROM {$this->table_logs}
                WHERE DATE(visit_time) BETWEEN %s AND %s
                GROUP BY session_id
            ) as sessions",
            $start_date, $end_date
        );
        return round($this->wpdb->get_var($sql), 1);
    }

    private function get_bounce_rate_by_period($start_date, $end_date) {
        $total = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(DISTINCT session_id) FROM {$this->table_logs} WHERE DATE(visit_time) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
        
        $bounced = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM (
                SELECT session_id FROM {$this->table_logs} 
                WHERE DATE(visit_time) BETWEEN %s AND %s
                GROUP BY session_id 
                HAVING COUNT(*) = 1
            ) as single_page",
            $start_date, $end_date
        ));
        
        return $total > 0 ? round(($bounced / $total) * 100, 1) : 0;
    }

    private function get_avg_time_on_page_by_period($start_date, $end_date) {
        $sql = $this->wpdb->prepare(
            "SELECT AVG(time_on_page) FROM {$this->table_logs} 
             WHERE time_on_page > 0 AND DATE(visit_time) BETWEEN %s AND %s",
            $start_date, $end_date
        );
        return round($this->wpdb->get_var($sql), 1);
    }

    private function get_devices_by_period($start_date, $end_date) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT device_type, device_brand, device_model, COUNT(*) as count 
             FROM {$this->table_logs} 
             WHERE DATE(visit_time) BETWEEN %s AND %s 
             GROUP BY device_type, device_brand, device_model 
             ORDER BY count DESC",
            $start_date, $end_date
        ), ARRAY_A);
    }

    private function get_countries_by_period($start_date, $end_date) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT country_code, country_name, COUNT(*) as count 
             FROM {$this->table_logs} 
             WHERE country_code != '' AND DATE(visit_time) BETWEEN %s AND %s 
             GROUP BY country_code, country_name 
             ORDER BY count DESC",
            $start_date, $end_date
        ), ARRAY_A);
    }

    private function get_traffic_sources_by_period($start_date, $end_date) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT source_channel, source_name, medium, COUNT(*) as count 
             FROM {$this->table_logs} 
             WHERE DATE(visit_time) BETWEEN %s AND %s 
             GROUP BY source_channel, source_name, medium 
             ORDER BY count DESC",
            $start_date, $end_date
        ), ARRAY_A);
    }

    private function get_wc_conversion_rate_by_period($start_date, $end_date) {
        $visitors = $this->visitors_by_period($start_date, $end_date);
        $orders = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(DISTINCT order_id) FROM {$this->table_wc_events} 
             WHERE event_type = 'order_complete' AND DATE(event_time) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
        
        return $visitors > 0 ? round(($orders / $visitors) * 100, 2) : 0;
    }

    public function wc_revenue_by_period($start_date, $end_date) {
        $sql = $this->wpdb->prepare(
            "SELECT SUM(product_price * quantity) as revenue
             FROM {$this->table_wc_events} 
             WHERE event_type = 'add_to_cart' 
             AND DATE(event_time) BETWEEN %s AND %s",
            $start_date, $end_date
        );
        return round($this->wpdb->get_var($sql) ?? 0, 2);
    }

    private function get_browsers_by_period($start_date, $end_date) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT browser_name, browser_version, platform, COUNT(*) as count 
            FROM {$this->table_logs} 
            WHERE DATE(visit_time) BETWEEN %s AND %s 
            GROUP BY browser_name, browser_version, platform 
            ORDER BY count DESC",
            $start_date, $end_date
        ), ARRAY_A);
    }

    private function get_pages_by_period($start_date, $end_date) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT url, page_title, COUNT(*) as count 
            FROM {$this->table_logs} 
            WHERE DATE(visit_time) BETWEEN %s AND %s 
            GROUP BY url, page_title 
            ORDER BY count DESC 
            LIMIT 20",
            $start_date, $end_date
        ), ARRAY_A);
    }

    private function get_keywords_by_period($start_date, $end_date) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT keywords, COUNT(*) as count 
            FROM {$this->table_logs} 
            WHERE keywords != '' AND DATE(visit_time) BETWEEN %s AND %s 
            GROUP BY keywords 
            ORDER BY count DESC 
            LIMIT 20",
            $start_date, $end_date
        ), ARRAY_A);
    }

    private function get_wc_events_by_period($start_date, $end_date) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT event_type, COUNT(*) as count 
            FROM {$this->table_wc_events} 
            WHERE DATE(event_time) BETWEEN %s AND %s 
            GROUP BY event_type 
            ORDER BY count DESC",
            $start_date, $end_date
        ), ARRAY_A);
    }

    // --- Entry & Exit Pages ---

    /**
     * Einstiegsseiten (erste Seite pro Session)
     */
    public function entry_pages($limit = 20) {
        $sql = $this->wpdb->prepare(
            "SELECT url, page_title, COUNT(*) as entries 
            FROM (
                SELECT session_id, url, page_title 
                FROM {$this->table_logs} 
                WHERE visit_time = (
                    SELECT MIN(visit_time) 
                    FROM {$this->table_logs} as sub 
                    WHERE sub.session_id = {$this->table_logs}.session_id
                )
            ) as entry_pages 
            GROUP BY url, page_title 
            ORDER BY entries DESC 
            LIMIT %d",
            $limit
        );
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * Ausstiegsseiten (letzte Seite pro Session)
     */
    public function exit_pages($limit = 20) {
        $sql = $this->wpdb->prepare(
            "SELECT url, page_title, COUNT(*) as exits 
            FROM (
                SELECT session_id, url, page_title 
                FROM {$this->table_logs} 
                WHERE visit_time = (
                    SELECT MAX(visit_time) 
                    FROM {$this->table_logs} as sub 
                    WHERE sub.session_id = {$this->table_logs}.session_id
                )
            ) as exit_pages 
            GROUP BY url, page_title 
            ORDER BY exits DESC 
            LIMIT %d",
            $limit
        );
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * Einstiegsseiten für bestimmten Zeitraum
     */
    public function entry_pages_by_period($start_date, $end_date, $limit = 20) {
        $sql = $this->wpdb->prepare(
            "SELECT url, page_title, COUNT(*) as entries 
            FROM (
                SELECT session_id, url, page_title 
                FROM {$this->table_logs} 
                WHERE DATE(visit_time) BETWEEN %s AND %s 
                AND visit_time = (
                    SELECT MIN(visit_time) 
                    FROM {$this->table_logs} as sub 
                    WHERE sub.session_id = {$this->table_logs}.session_id
                    AND DATE(sub.visit_time) BETWEEN %s AND %s
                )
            ) as entry_pages 
            GROUP BY url, page_title 
            ORDER BY entries DESC 
            LIMIT %d",
            $start_date, $end_date, $start_date, $end_date, $limit
        );
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * Ausstiegsseiten für bestimmten Zeitraum
     */
    public function exit_pages_by_period($start_date, $end_date, $limit = 20) {
        $sql = $this->wpdb->prepare(
            "SELECT url, page_title, COUNT(*) as exits 
            FROM (
                SELECT session_id, url, page_title 
                FROM {$this->table_logs} 
                WHERE DATE(visit_time) BETWEEN %s AND %s 
                AND visit_time = (
                    SELECT MAX(visit_time) 
                    FROM {$this->table_logs} as sub 
                    WHERE sub.session_id = {$this->table_logs}.session_id
                    AND DATE(sub.visit_time) BETWEEN %s AND %s
                )
            ) as exit_pages 
            GROUP BY url, page_title 
            ORDER BY exits DESC 
            LIMIT %d",
            $start_date, $end_date, $start_date, $end_date, $limit
        );
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * Exit Rate pro Seite (wie oft war sie die letzte Seite)
     */
    public function exit_rates($limit = 20) {
        $sql = $this->wpdb->prepare(
            "SELECT 
                url, 
                page_title,
                COUNT(*) as total_views,
                SUM(is_exit) as exit_views,
                ROUND((SUM(is_exit) / COUNT(*)) * 100, 1) as exit_rate
            FROM (
                SELECT 
                    url, 
                    page_title,
                    CASE WHEN visit_time = (
                        SELECT MAX(visit_time) 
                        FROM {$this->table_logs} as sub 
                        WHERE sub.session_id = {$this->table_logs}.session_id
                    ) THEN 1 ELSE 0 END as is_exit
                FROM {$this->table_logs}
            ) as page_analysis
            GROUP BY url, page_title
            HAVING total_views > 5
            ORDER BY exit_rate DESC 
            LIMIT %d",
            $limit
        );
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * Exit Rates für Zeitraum
     */
    private function exit_rates_by_period($start_date, $end_date, $limit = 20) {
        $sql = $this->wpdb->prepare(
            "SELECT 
                url, 
                page_title,
                COUNT(*) as total_views,
                SUM(is_exit) as exit_views,
                ROUND((SUM(is_exit) / COUNT(*)) * 100, 1) as exit_rate
            FROM (
                SELECT 
                    url, 
                    page_title,
                    CASE WHEN visit_time = (
                        SELECT MAX(visit_time) 
                        FROM {$this->table_logs} as sub 
                        WHERE sub.session_id = {$this->table_logs}.session_id
                        AND DATE(sub.visit_time) BETWEEN %s AND %s
                    ) THEN 1 ELSE 0 END as is_exit
                FROM {$this->table_logs}
                WHERE DATE(visit_time) BETWEEN %s AND %s
            ) as page_analysis
            GROUP BY url, page_title
            HAVING total_views > 2
            ORDER BY exit_rate DESC 
            LIMIT %d",
            $start_date, $end_date, $start_date, $end_date, $limit
        );
        return $this->wpdb->get_results($sql, ARRAY_A);
    }
}