<?php

namespace Utils\Tracker;

class VisitorAnalytics extends VisitorTracker 
{
    private static $analytics_instance = null;
    private $gsc;

    public static function getAnalyticsInstance(): self {
        if (self::$analytics_instance === null) {
            self::$analytics_instance = new self();
        }
        return self::$analytics_instance;
    }

    public function __construct() {
        parent::__construct(); 
        $this->gsc = \Utils\Tracker\Google\GoogleSearchConsole::getInstance();
    }

    // --- Grundlegende Besucherstatistiken ---

    private function query_count($where_sql = '', $params = [], $min_requests = 1) {
        $sql = "
            SELECT COUNT(*) FROM (
                SELECT session_id, COUNT(*) as requests
                FROM {$this->table_logs}
                WHERE 1=1 $where_sql
                GROUP BY session_id
                HAVING requests >= %d
            ) t
        ";
        // %d für min_requests an die params anhängen
        $params[] = $min_requests;

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

    public function get_report_today(): array {
        $today = date('Y-m-d');
        return $this->get_report($today, $today);
    }

    public function get_report_yesterday(): array {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        return $this->get_report($yesterday, $yesterday);
    }

    public function get_report_this_week(): array {
        $monday = date('Y-m-d', strtotime('monday this week'));
        $sunday = date('Y-m-d', strtotime('sunday this week'));
        return $this->get_report($monday, $sunday);
    }

    public function get_report_this_month(): array {
        $firstDay = date('Y-m-01');
        $lastDay  = date('Y-m-t');
        return $this->get_report($firstDay, $lastDay);
    }

    public function get_report_this_year(): array {
        $firstDay = date('Y-01-01');
        $lastDay  = date('Y-12-31');
        return $this->get_report($firstDay, $lastDay);
    }

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
            'search_engines' => $this->get_search_engines_by_period($start_date, $end_date),
            'social_networks' => $this->get_social_networks_by_period($start_date, $end_date),
            'traffic_channels' => $this->get_traffic_channels_by_period($start_date, $end_date),
            'operating_systems' => $this->get_operating_systems_by_period($start_date, $end_date),
            'visitor_types' => $this->get_visitor_types_by_period($start_date, $end_date),
            'visit_times' => $this->get_visit_times_by_period($start_date, $end_date),
            'screen_resolutions' => $this->get_screen_resolutions_by_period($start_date, $end_date),
            'languages' => $this->get_languages_by_period($start_date, $end_date),
            'keywords' => $this->get_keywords_by_period($start_date, $end_date),
            'cities' => $this->get_cities_by_period($start_date, $end_date),
            'gsc_keywords' => $this->get_gsc_keywords_by_period($start_date, $end_date), 
            'wc_metrics' => [
                'events' => $this->get_wc_events_by_period($start_date, $end_date),
                'conversion_rate' => $this->get_wc_conversion_rate_by_period($start_date, $end_date),
                'revenue' => $this->wc_revenue_by_period($start_date, $end_date)
            ]
        ];
    }

    // --- Session KPIs ---
    private function get_avg_session_duration_by_period($start_date, $end_date) {
        // Diese Methode ist bereits korrekt (arbeitet auf Session-Ebene)
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
        // Diese Methode ist bereits korrekt (arbeitet auf Session-Ebene)
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
        // Diese Methode ist bereits korrekt (arbeitet auf Session-Ebene)
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

    // --- Entry & Exit Pages ---

    public function entry_pages($limit = 20) {
        $sql = $this->wpdb->prepare(
            "SELECT url, page_title, COUNT(*) as entries,
                    ROUND((COUNT(*) * 100.0 / SUM(COUNT(*)) OVER()), 1) as percentage
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

    public function exit_pages($limit = 20) {
        $sql = $this->wpdb->prepare(
            "SELECT url, page_title, COUNT(*) as exits,
                    ROUND((COUNT(*) * 100.0 / SUM(COUNT(*)) OVER()), 1) as percentage
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

    public function entry_pages_by_period($start_date, $end_date, $limit = 20) {
        $sql = $this->wpdb->prepare(
            "SELECT url, page_title, COUNT(*) as entries,
                    ROUND((COUNT(*) * 100.0 / SUM(COUNT(*)) OVER()), 1) as percentage
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

    public function exit_pages_by_period($start_date, $end_date, $limit = 20) {
        $sql = $this->wpdb->prepare(
            "SELECT url, page_title, COUNT(*) as exits,
                    ROUND((COUNT(*) * 100.0 / SUM(COUNT(*)) OVER()), 1) as percentage
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

    public function exit_rates_by_period($start_date, $end_date, $limit = 20) {
        $sql = $this->wpdb->prepare(
            "SELECT url, page_title,
                    COUNT(*) as total_views,
                    SUM(is_exit) as exit_views,
                    ROUND((SUM(is_exit) / COUNT(*)) * 100, 1) as exit_rate,
                    ROUND((SUM(is_exit) * 100.0 / SUM(SUM(is_exit)) OVER()), 1) as percentage
             FROM (
                SELECT url, page_title,
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

    // --- Breakdown Reports (Devices, Browser, Countries, Traffic, Pages, Keywords, Events) ---
    private function get_devices_by_period($start_date, $end_date) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT device_type, device_brand, device_model, COUNT(*) as count,
                    ROUND((COUNT(*) * 100.0 / SUM(COUNT(*)) OVER()), 1) as percentage
            FROM (
                SELECT session_id, device_type, device_brand, device_model
                FROM {$this->table_logs}
                WHERE DATE(visit_time) BETWEEN %s AND %s
                GROUP BY session_id, device_type, device_brand, device_model
            ) as sessions
            GROUP BY device_type, device_brand, device_model 
            ORDER BY count DESC",
            $start_date, $end_date
        ), ARRAY_A);
    }

    private function get_countries_by_period($start_date, $end_date) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT country_code, country_name, COUNT(*) as count,
                    ROUND((COUNT(*) * 100.0 / SUM(COUNT(*)) OVER()), 1) as percentage
            FROM (
                SELECT session_id, country_code, country_name
                FROM {$this->table_logs}
                WHERE country_code != '' AND DATE(visit_time) BETWEEN %s AND %s
                GROUP BY session_id, country_code, country_name
            ) as sessions
            GROUP BY country_code, country_name 
            ORDER BY count DESC",
            $start_date, $end_date
        ), ARRAY_A);
    }

    private function get_traffic_sources_by_period($start_date, $end_date) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT source_channel, source_name, medium, COUNT(*) as count,
                    ROUND((COUNT(*) * 100.0 / SUM(COUNT(*)) OVER()), 1) as percentage
            FROM (
                SELECT session_id, source_channel, source_name, medium
                FROM {$this->table_logs}
                WHERE DATE(visit_time) BETWEEN %s AND %s
                GROUP BY session_id, source_channel, source_name, medium
            ) as sessions
            GROUP BY source_channel, source_name, medium 
            ORDER BY count DESC",
            $start_date, $end_date
        ), ARRAY_A);
    }

    private function get_browsers_by_period($start_date, $end_date) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT browser_name, browser_version, platform, COUNT(*) as count,
                    ROUND((COUNT(*) * 100.0 / SUM(COUNT(*)) OVER()), 1) as percentage
            FROM (
                SELECT session_id, browser_name, browser_version, platform
                FROM {$this->table_logs}
                WHERE DATE(visit_time) BETWEEN %s AND %s
                GROUP BY session_id, browser_name, browser_version, platform
            ) as sessions
            GROUP BY browser_name
            ORDER BY count DESC",
            $start_date, $end_date
        ), ARRAY_A);
    }

    private function get_search_engines_by_period($start_date, $end_date) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT source_name, COUNT(*) as count,
                    ROUND((COUNT(*) * 100.0 / SUM(COUNT(*)) OVER()), 1) as percentage
            FROM (
                SELECT session_id, source_name
                FROM {$this->table_logs}
                WHERE source_channel = 'organic' 
                AND DATE(visit_time) BETWEEN %s AND %s
                GROUP BY session_id, source_name
            ) as sessions
            GROUP BY source_name 
            ORDER BY count DESC",
            $start_date, $end_date
        ), ARRAY_A);
    }

    private function get_social_networks_by_period($start_date, $end_date) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT source_name, COUNT(*) as count,
                    ROUND((COUNT(*) * 100.0 / SUM(COUNT(*)) OVER()), 1) as percentage
            FROM (
                SELECT session_id, source_name
                FROM {$this->table_logs}
                WHERE source_channel = 'social' 
                AND DATE(visit_time) BETWEEN %s AND %s
                GROUP BY session_id, source_name
            ) as sessions
            GROUP BY source_name 
            ORDER BY count DESC",
            $start_date, $end_date
        ), ARRAY_A);
    }

    private function get_traffic_channels_by_period($start_date, $end_date) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT source_channel, COUNT(*) as count,
                    ROUND((COUNT(*) * 100.0 / SUM(COUNT(*)) OVER()), 1) as percentage
            FROM (
                SELECT session_id, source_channel
                FROM {$this->table_logs}
                WHERE DATE(visit_time) BETWEEN %s AND %s
                GROUP BY session_id, source_channel
            ) as sessions
            GROUP BY source_channel 
            ORDER BY count DESC",
            $start_date, $end_date
        ), ARRAY_A);
    }

    private function get_operating_systems_by_period($start_date, $end_date) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT platform, COUNT(*) as count,
                    ROUND((COUNT(*) * 100.0 / SUM(COUNT(*)) OVER()), 1) as percentage
            FROM (
                SELECT session_id, platform
                FROM {$this->table_logs}
                WHERE DATE(visit_time) BETWEEN %s AND %s
                GROUP BY session_id, platform
            ) as sessions
            GROUP BY platform 
            ORDER BY count DESC",
            $start_date, $end_date
        ), ARRAY_A);
    }

    private function get_screen_resolutions_by_period($start_date, $end_date) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT screen_resolution, COUNT(*) as count,
                    ROUND((COUNT(*) * 100.0 / SUM(COUNT(*)) OVER()), 1) as percentage
            FROM (
                SELECT session_id, screen_resolution
                FROM {$this->table_logs}
                WHERE screen_resolution != '' AND DATE(visit_time) BETWEEN %s AND %s
                GROUP BY session_id, screen_resolution
            ) as sessions
            GROUP BY screen_resolution 
            ORDER BY count DESC
            LIMIT 15",
            $start_date, $end_date
        ), ARRAY_A);
    }

    private function get_languages_by_period($start_date, $end_date) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT language, COUNT(*) as count,
                    ROUND((COUNT(*) * 100.0 / SUM(COUNT(*)) OVER()), 1) as percentage
            FROM (
                SELECT session_id, language
                FROM {$this->table_logs}
                WHERE language != '' AND DATE(visit_time) BETWEEN %s AND %s
                GROUP BY session_id, language
            ) as sessions
            GROUP BY language 
            ORDER BY count DESC",
            $start_date, $end_date
        ), ARRAY_A);
    }

    private function get_visit_times_by_period($start_date, $end_date) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT HOUR(visit_time) as hour, COUNT(*) as count
            FROM (
                SELECT session_id, MIN(visit_time) as visit_time
                FROM {$this->table_logs}
                WHERE DATE(visit_time) BETWEEN %s AND %s
                GROUP BY session_id
            ) as sessions
            GROUP BY HOUR(visit_time)
            ORDER BY hour ASC",
            $start_date, $end_date
        ), ARRAY_A);
    }

    private function get_visitor_types_by_period($start_date, $end_date) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT 
                CASE 
                    WHEN visit_count = 1 THEN 'New Visitors'
                    ELSE 'Returning Visitors'
                END as visitor_type,
                COUNT(*) as count,
                ROUND((COUNT(*) * 100.0 / SUM(COUNT(*)) OVER()), 1) as percentage
            FROM (
                SELECT session_id, COUNT(DISTINCT DATE(visit_time)) as visit_count
                FROM {$this->table_logs}
                WHERE DATE(visit_time) BETWEEN %s AND %s
                GROUP BY session_id
            ) as visits
            GROUP BY visitor_type",
            $start_date, $end_date
        ), ARRAY_A);
    }

    private function get_cities_by_period($start_date, $end_date) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT city, country_name, COUNT(*) as count,
                    ROUND((COUNT(*) * 100.0 / SUM(COUNT(*)) OVER()), 1) as percentage
            FROM (
                SELECT session_id, city, country_name
                FROM {$this->table_logs}
                WHERE city != '' AND DATE(visit_time) BETWEEN %s AND %s
                GROUP BY session_id, city, country_name
            ) as sessions
            GROUP BY city, country_name 
            ORDER BY count DESC
            LIMIT 20",
            $start_date, $end_date
        ), ARRAY_A);
    }

    private function get_pages_by_period($start_date, $end_date) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT url, page_title, COUNT(*) as count,
                    ROUND((COUNT(*) * 100.0 / SUM(COUNT(*)) OVER()), 1) as percentage
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
            "SELECT keywords, COUNT(*) as count,
                    ROUND((COUNT(*) * 100.0 / SUM(COUNT(*)) OVER()), 1) as percentage
            FROM (
                SELECT session_id, keywords
                FROM {$this->table_logs}
                WHERE keywords != '' AND DATE(visit_time) BETWEEN %s AND %s
                GROUP BY session_id, keywords
            ) as sessions
            GROUP BY keywords 
            ORDER BY count DESC 
            LIMIT 20",
            $start_date, $end_date
        ), ARRAY_A);
    }

    private function get_gsc_keywords_by_period($start_date, $end_date) {
        try {
            if (!$this->gsc->isAuthenticated() || !$this->gsc->getPrimaryDomain()) {
                return [];
            }
            
            $payload = [
                'startDate' => $start_date,
                'endDate' => $end_date,
                'dimensions' => ['query'],
                'rowLimit' => 30,
                'orderBy' => [
                    [
                        'dimension' => 'CLICKS',
                        'sortOrder' => 'DESCENDING'
                    ]
                ]
            ];
            
            $result = $this->gsc->getSearchAnalyticsData($payload);
            
            if ($result['success'] && !empty($result['data'])) {
                $totalClicks = array_sum(array_column($result['data'], 'clicks'));
                
                return array_map(function($row) use ($totalClicks) {
                    $clicks = $row['clicks'] ?? 0;
                    $percentage = $totalClicks > 0 ? round(($clicks / $totalClicks) * 100, 1) : 0;
                    
                    return [
                        'keywords' => $row['keys'][0] ?? 'N/A',
                        'count' => $clicks,
                        'percentage' => $percentage
                    ];
                }, $result['data']);
            }
            
            return [];
            
        } catch (\Exception $e) {
            error_log('GSC Keywords Error: ' . $e->getMessage());
            return [];
        }
    }

    private function get_wc_events_by_period($start_date, $end_date) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT event_type, COUNT(*) as count,
                    ROUND((COUNT(*) * 100.0 / SUM(COUNT(*)) OVER()), 1) as percentage
             FROM {$this->table_wc_events} 
             WHERE DATE(event_time) BETWEEN %s AND %s 
             GROUP BY event_type 
             ORDER BY count DESC",
            $start_date, $end_date
        ), ARRAY_A);
    }

    private function get_wc_conversion_rate_by_period($start_date, $end_date) {
        $product_viewers = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(DISTINCT visitor_id) 
             FROM {$this->table_wc_events} 
             WHERE event_type = 'product_view' 
             AND DATE(event_time) BETWEEN %s AND %s",
            $start_date, $end_date
        ));

        $orders = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(DISTINCT order_id) 
             FROM {$this->table_wc_events} 
             WHERE event_type = 'order_complete' 
             AND DATE(event_time) BETWEEN %s AND %s",
            $start_date, $end_date
        ));

        return $product_viewers > 0 ? round(($orders / $product_viewers) * 100, 2) : 0;
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
}