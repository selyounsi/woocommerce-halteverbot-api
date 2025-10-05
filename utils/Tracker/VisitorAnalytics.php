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

    public function visitors_last_month() {
        $last_month = date('Y-m', strtotime('-1 month', current_time('timestamp')));
        return $this->query_count(' AND DATE_FORMAT(visit_time, "%Y-%m") = %s', [$last_month]);
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
            'visitors' => [
                'today' => $this->visitors_today(),
                'yesterday' => $this->visitors_yesterday(),
                'this_week' => $this->visitors_this_week(),
                'this_month' => $this->visitors_this_month(),
                'last_month' => $this->visitors_last_month(),
                'this_year' => $this->visitors_this_year()
            ],
            'session_metrics' => [
                'avg_duration' => $this->get_avg_session_duration_by_period($start_date, $end_date),
                'avg_pages' => $this->get_avg_pages_per_session_by_period($start_date, $end_date),
                'bounce_rate' => $this->get_bounce_rate_by_period($start_date, $end_date),
                'avg_time_on_page' => $this->get_avg_time_on_page_by_period($start_date, $end_date)
            ],
            'chart_data' => [
                'daily_visitors_30d' => $this->get_daily_visitors_chart_data(30),
                'daily_visitors_7d' => $this->get_daily_visitors_chart_data(7),
                'device_distribution' => $this->get_device_distribution_chart($start_date, $end_date),
                'browser_distribution' => $this->get_browser_distribution_chart($start_date, $end_date),
                'traffic_sources' => $this->get_traffic_sources_chart($start_date, $end_date),
                'visit_heatmap' => $this->get_visit_heatmap_data($start_date, $end_date),
                'german_cities' => $this->get_german_cities_chart($start_date, $end_date),
                'page_performance' => $this->get_page_performance_chart($start_date, $end_date)
            ],            
            'entry_pages' => $this->entry_pages_by_period($start_date, $end_date, 10),
            'exit_pages' => $this->exit_pages_by_period($start_date, $end_date, 10),
            'exit_rates' => $this->exit_rates_by_period($start_date, $end_date, 10),
            'devices' => $this->get_devices_by_period($start_date, $end_date),
            'device_types' => $this->get_device_types_by_period($start_date, $end_date),
            'device_brands' => $this->get_device_brands_by_period($start_date, $end_date),
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
            'gsc_keywords' => $this->get_gsc_keywords_16_months(), 
            'wc_metrics' => [
                'events' => $this->get_wc_events_by_period($start_date, $end_date),
                
                // Aktuelle Periode
                'current_period' => [
                    'conversion_rate' => $this->get_wc_conversion_rate_by_period($start_date, $end_date),
                    'revenue' => $this->wc_revenue_by_period($start_date, $end_date),
                    'confirmed_revenue' => $this->wc_confirmed_revenue_by_period($start_date, $end_date),
                    'average_order_value' => $this->get_average_order_value($start_date, $end_date),
                    'total_orders' => $this->get_total_orders($start_date, $end_date),
                    'unique_customers' => $this->get_unique_customers($start_date, $end_date),
                    'contact_conversions' => $this->get_contact_conversions($start_date, $end_date) // NEU
                ],
                
                // Letzte 7 Tage
                'last_7_days' => [
                    'conversion_rate' => $this->get_wc_conversion_rate_by_period(date('Y-m-d', strtotime('-7 days')), $end_date),
                    'revenue' => $this->wc_revenue_by_period(date('Y-m-d', strtotime('-7 days')), $end_date),
                    'confirmed_revenue' => $this->wc_confirmed_revenue_by_period(date('Y-m-d', strtotime('-7 days')), $end_date),
                    'average_order_value' => $this->get_average_order_value(date('Y-m-d', strtotime('-7 days')), $end_date),
                    'total_orders' => $this->get_total_orders(date('Y-m-d', strtotime('-7 days')), $end_date),
                    'contact_conversions' => $this->get_contact_conversions(date('Y-m-d', strtotime('-7 days')), $end_date), // NEU
                    'daily_data' => $this->get_daily_wc_events_for_days(7)
                ],
                
                // Letzte 30 Tage
                'last_30_days' => [
                    'conversion_rate' => $this->get_wc_conversion_rate_by_period(date('Y-m-d', strtotime('-30 days')), $end_date),
                    'revenue' => $this->wc_revenue_by_period(date('Y-m-d', strtotime('-30 days')), $end_date),
                    'confirmed_revenue' => $this->wc_confirmed_revenue_by_period(date('Y-m-d', strtotime('-30 days')), $end_date),
                    'average_order_value' => $this->get_average_order_value(date('Y-m-d', strtotime('-30 days')), $end_date),
                    'total_orders' => $this->get_total_orders(date('Y-m-d', strtotime('-30 days')), $end_date),
                    'contact_conversions' => $this->get_contact_conversions(date('Y-m-d', strtotime('-30 days')), $end_date), // NEU
                    'daily_data' => $this->get_daily_wc_events_for_days(30)
                ],
                
                // Erweiterter Funnel
                'funnel' => [
                    'view_to_cart' => $this->get_funnel_rate('product_view', 'add_to_cart', $start_date, $end_date),
                    'cart_to_checkout' => $this->get_funnel_rate('add_to_cart', 'checkout_start', $start_date, $end_date),
                    'checkout_to_order' => $this->get_funnel_rate('checkout_start', 'order_complete', $start_date, $end_date),
                    'cart_abandonment_rate' => $this->get_cart_abandonment_rate($start_date, $end_date),
                    'contact_engagement' => $this->get_contact_engagement_rate($start_date, $end_date) // NEU
                ],
                
                // Kunden Metriken
                'customer_metrics' => [
                    'session_analysis' => $this->get_session_analysis($start_date, $end_date),
                    'conversion_breakdown' => [
                        'online_orders' => $this->get_online_orders($start_date, $end_date),
                        'contact_leads' => $this->get_contact_leads($start_date, $end_date),
                        'total_conversions' => $this->get_online_orders($start_date, $end_date) + $this->get_contact_leads($start_date, $end_date),
                        'high_value_sessions' => $this->get_high_value_sessions($start_date, $end_date)
                    ]
                ],
                
                // Geräte Performance
                'device_performance' => [
                    'desktop' => $this->get_conversion_rate_by_device('desktop', $start_date, $end_date),
                    'mobile' => $this->get_conversion_rate_by_device('mobile', $start_date, $end_date),
                    'tablet' => $this->get_conversion_rate_by_device('tablet', $start_date, $end_date)
                ],
                
                // Top Produkte
                'top_products' => $this->get_top_products_by_revenue($start_date, $end_date, 10),
                
                // NEUE: Engagement Metriken
                'engagement_metrics' => [
                    'contact_events' => $this->get_contact_events_by_period($start_date, $end_date),
                    'search_analytics' => $this->get_gsc_keywords_by_period($start_date, $end_date, 10),
                    'category_engagement' => $this->get_category_engagement($start_date, $end_date),
                    'wishlist_activity' => $this->get_wishlist_activity($start_date, $end_date)
                ],
                
                // NEUE: Conversion Quellen
                'conversion_sources' => [
                    'online_orders' => $this->get_conversion_source_rate('order_complete', $start_date, $end_date),
                    'contact_leads' => $this->get_conversion_source_rate('phone_click,email_click', $start_date, $end_date),
                    'search_leads' => $this->get_conversion_source_rate('product_search', $start_date, $end_date)
                ]
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
            "SELECT 
                device_type,
                device_brand,
                device_model,
                COUNT(*) as count,
                ROUND((COUNT(*) * 100.0 / SUM(COUNT(*)) OVER()), 1) as percentage
            FROM (
                SELECT 
                    session_id, 
                    device_type,
                    COALESCE(NULLIF(device_brand, ''), 'Unknown') as device_brand,
                    COALESCE(NULLIF(device_model, ''), 'Unknown') as device_model
                FROM {$this->table_logs}
                WHERE DATE(visit_time) BETWEEN %s AND %s
                GROUP BY session_id, device_type, device_brand, device_model
            ) as sessions
            GROUP BY device_type, device_brand, device_model 
            ORDER BY device_type, count DESC",
            $start_date, $end_date
        ), ARRAY_A);
    }

    private function get_device_types_by_period($start_date, $end_date) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT device_type, COUNT(*) as count,
                    ROUND((COUNT(*) * 100.0 / SUM(COUNT(*)) OVER()), 1) as percentage
            FROM (
                SELECT session_id, device_type
                FROM {$this->table_logs}
                WHERE DATE(visit_time) BETWEEN %s AND %s
                GROUP BY session_id, device_type
            ) as sessions
            GROUP BY device_type 
            ORDER BY count DESC",
            $start_date, $end_date
        ), ARRAY_A);
    }

    private function get_device_brands_by_period($start_date, $end_date) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT 
                COALESCE(NULLIF(device_brand, ''), 'Unknown') as brand,
                COUNT(*) as count,
                ROUND((COUNT(*) * 100.0 / SUM(COUNT(*)) OVER()), 1) as percentage
            FROM (
                SELECT session_id, device_brand
                FROM {$this->table_logs}
                WHERE DATE(visit_time) BETWEEN %s AND %s
                GROUP BY session_id, device_brand
            ) as sessions
            GROUP BY brand 
            ORDER BY count DESC
            LIMIT 10",
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
            "SELECT 
                CASE 
                    WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(language, ',', 1), '-', 1) = 'de' THEN 'Deutsch'
                    WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(language, ',', 1), '-', 1) = 'en' THEN 'Englisch'
                    WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(language, ',', 1), '-', 1) = 'fr' THEN 'Französisch'
                    WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(language, ',', 1), '-', 1) = 'es' THEN 'Spanisch'
                    WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(language, ',', 1), '-', 1) = 'it' THEN 'Italienisch'
                    WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(language, ',', 1), '-', 1) = 'ru' THEN 'Russisch'
                    WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(language, ',', 1), '-', 1) = 'uk' THEN 'Ukrainisch'
                    WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(language, ',', 1), '-', 1) = 'pl' THEN 'Polnisch'
                    WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(language, ',', 1), '-', 1) = 'tr' THEN 'Türkisch'
                    WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(language, ',', 1), '-', 1) = 'ar' THEN 'Arabisch'
                    WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(language, ',', 1), '-', 1) = 'zh' THEN 'Chinesisch'
                    WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(language, ',', 1), '-', 1) = 'ja' THEN 'Japanisch'
                    WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(language, ',', 1), '-', 1) = 'ko' THEN 'Koreanisch'
                    ELSE 'Andere'
                END as language_clean,
                COUNT(*) as count,
                ROUND((COUNT(*) * 100.0 / SUM(COUNT(*)) OVER()), 1) as percentage
            FROM (
                SELECT session_id, language
                FROM {$this->table_logs}
                WHERE language != '' AND DATE(visit_time) BETWEEN %s AND %s
                GROUP BY session_id, language
            ) as sessions
            GROUP BY language_clean 
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

    private function get_gsc_keywords_16_months() {
        $end_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-16 months'));
        
        return $this->get_gsc_keywords_by_period($start_date, $end_date);
    }

    private function get_gsc_keywords_by_period($start_date, $end_date, $limit = 30) {
        try {
            if (!$this->gsc->isAuthenticated() || !$this->gsc->getPrimaryDomain()) {
                return [];
            }
            
            $payload = [
                'startDate' => $start_date,
                'endDate' => $end_date,
                'dimensions' => ['query'],
                'rowLimit' => $limit,
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
        $product_views = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) 
            FROM {$this->table_wc_events} 
            WHERE event_type = 'product_view' 
            AND DATE(event_time) BETWEEN %s AND %s",
            $start_date, $end_date
        ));

        $orders = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) 
            FROM {$this->table_wc_events} 
            WHERE event_type = 'order_complete' 
            AND DATE(event_time) BETWEEN %s AND %s",
            $start_date, $end_date
        ));

        return $product_views > 0 ? round(($orders / $product_views) * 100, 2) : 0;
    }

    // Aktueller Umsatz (alle order_complete Events)
    public function wc_revenue_by_period($start_date, $end_date) {
        $orders_table = $this->wpdb->prefix . 'wc_orders';
        
        $sql = $this->wpdb->prepare(
            "SELECT SUM(o.total_amount) as revenue
            FROM {$this->table_wc_events} e
            LEFT JOIN {$orders_table} o ON e.order_id = o.id
            WHERE e.event_type = 'order_complete' 
            AND DATE(e.event_time) BETWEEN %s AND %s",
            $start_date, $end_date
        );
        return round($this->wpdb->get_var($sql) ?? 0, 2);
    }

    // Bestätigter Umsatz (nur completed Orders)
    public function wc_confirmed_revenue_by_period($start_date, $end_date) {
        $orders_table = $this->wpdb->prefix . 'wc_orders';
        
        $sql = $this->wpdb->prepare(
            "SELECT SUM(o.total_amount) as revenue
            FROM {$this->table_wc_events} e
            LEFT JOIN {$orders_table} o ON e.order_id = o.id
            WHERE e.event_type = 'order_complete' 
            AND DATE(e.event_time) BETWEEN %s AND %s
            AND o.status = 'completed'",
            $start_date, $end_date
        );
        return round($this->wpdb->get_var($sql) ?? 0, 2);
    }


    /**
     * Holt tägliche Events für X Tage (immer vollständige Anzahl Einträge)
     */
    private function get_daily_wc_events_for_days($days) {
        $end_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-'.($days-1).' days'));
        
        // Erstelle alle Tage
        $daily_data = [];
        for ($i = $days-1; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-$i days"));
            $daily_data[$day] = [
                'conversion_rate' => 0,
                'product_view' => 0,        // Korrigiert: product_view statt product_views
                'add_to_cart' => 0,
                'checkout_start' => 0,
                'order_complete' => 0,
                'phone_click' => 0,
                'email_click' => 0
            ];
        }

        // Hole echte Daten aus der Datenbank
        $results = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT DATE(event_time) as day, event_type, COUNT(*) as count
            FROM {$this->table_wc_events} 
            WHERE DATE(event_time) BETWEEN %s AND %s 
            GROUP BY day, event_type 
            ORDER BY day",
            $start_date, $end_date
        ), ARRAY_A);

        // Fülle die täglichen Daten
        foreach ($results as $row) {
            $day = $row['day'];
            if (isset($daily_data[$day])) {
                $daily_data[$day][$row['event_type']] = (int)$row['count'];
            }
        }

        // Berechne Conversion Rate für jeden Tag
        foreach ($daily_data as $day => &$data) {
            $product_views = $data['product_view'];  // Korrigiert: product_view statt product_views
            $orders = $data['order_complete'];
            $data['conversion_rate'] = $product_views > 0 ? round(($orders / $product_views) * 100, 2) : 0;
        }

        return $daily_data;
    }

    /**
     * Gibt Gesamtzahl der Bestellungen zurück
     */
    private function get_total_orders($start_date, $end_date) {
        return $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) 
            FROM {$this->table_wc_events} 
            WHERE event_type = 'order_complete' 
            AND DATE(event_time) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
    }

    /**
     * Gibt Anzahl einmaliger Kunden zurück
     */
    private function get_unique_customers($start_date, $end_date) {
        return $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(DISTINCT customer_id) 
            FROM {$this->table_wc_events} 
            WHERE event_type = 'order_complete' 
            AND customer_id IS NOT NULL
            AND DATE(event_time) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
    }

    /**
     * Berechnet durchschnittlichen Bestellwert
     */
    private function get_average_order_value($start_date, $end_date) {
        $orders_table = $this->wpdb->prefix . 'wc_orders';
        
        $revenue = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT SUM(o.total_amount) 
            FROM {$this->table_wc_events} e
            LEFT JOIN {$orders_table} o ON e.order_id = o.id
            WHERE e.event_type = 'order_complete' 
            AND DATE(e.event_time) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
        
        $order_count = $this->get_total_orders($start_date, $end_date);
        
        return $order_count > 0 ? round($revenue / $order_count, 2) : 0;
    }

    /**
     * Berechnet Funnel Conversion Rate zwischen zwei Events
     */
    private function get_funnel_rate($from_event, $to_event, $start_date, $end_date) {
        $from_count = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(DISTINCT session_id) 
            FROM {$this->table_wc_events} 
            WHERE event_type = %s 
            AND DATE(event_time) BETWEEN %s AND %s",
            $from_event, $start_date, $end_date
        ));
        
        $to_count = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(DISTINCT session_id) 
            FROM {$this->table_wc_events} 
            WHERE event_type = %s 
            AND DATE(event_time) BETWEEN %s AND %s",
            $to_event, $start_date, $end_date
        ));
        
        return $from_count > 0 ? round(($to_count / $from_count) * 100, 2) : 0;
    }

    /**
     * Berechnet Warenkorb-Abbrecherquote
     */
    private function get_cart_abandonment_rate($start_date, $end_date) {
        $cart_sessions = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(DISTINCT session_id) 
            FROM {$this->table_wc_events} 
            WHERE event_type = 'add_to_cart' 
            AND DATE(event_time) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
        
        $order_sessions = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(DISTINCT session_id) 
            FROM {$this->table_wc_events} 
            WHERE event_type = 'order_complete' 
            AND DATE(event_time) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
        
        return $cart_sessions > 0 ? round((($cart_sessions - $order_sessions) / $cart_sessions) * 100, 2) : 0;
    }

    /**
     * Einfache Session Analyse mit session_id
     */
    private function get_session_analysis($start_date, $end_date) {
        $sessions_per_visitor = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT ip, user_agent, COUNT(DISTINCT session_id) as session_count
            FROM {$this->table_logs} 
            WHERE DATE(visit_time) BETWEEN %s AND %s
            GROUP BY ip, user_agent",
            $start_date, $end_date
        ), ARRAY_A);
        
        $new_sessions = 0;
        $returning_sessions = 0;
        
        foreach ($sessions_per_visitor as $visitor) {
            if ($visitor['session_count'] == 1) {
                $new_sessions++;
            } else {
                $returning_sessions++;
            }
        }
        
        return [
            'new_sessions' => $new_sessions,
            'returning_sessions' => $returning_sessions,
            'total_visitors' => count($sessions_per_visitor)
        ];
    }

    /**
     * Zählt High-Value Sessions (mit Bestellung oder Kontakt)
     */
    private function get_high_value_sessions($start_date, $end_date) {
        return $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(DISTINCT e.session_id) 
            FROM {$this->table_wc_events} e
            INNER JOIN {$this->table_logs} l ON e.session_id = l.session_id
            WHERE e.event_type IN ('order_complete', 'phone_click', 'email_click')
            AND DATE(e.event_time) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
    }

    /**
     * Online Bestellungen
     */
    private function get_online_orders($start_date, $end_date) {
        return $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) 
            FROM {$this->table_wc_events} 
            WHERE event_type = 'order_complete' 
            AND DATE(event_time) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
    }

    /**
     * Kontakt Leads
     */
    private function get_contact_leads($start_date, $end_date) {
        return $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(DISTINCT session_id) 
            FROM {$this->table_wc_events} 
            WHERE event_type IN ('phone_click', 'email_click')
            AND DATE(event_time) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
    }

    /**
     * Berechnet Conversion Rate nach Gerätetyp
     */
    private function get_conversion_rate_by_device($device_type, $start_date, $end_date) {
        $product_views = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) 
            FROM {$this->table_wc_events} e
            INNER JOIN {$this->table_logs} l ON e.session_id = l.session_id
            WHERE e.event_type = 'product_view' 
            AND l.device_type = %s
            AND DATE(e.event_time) BETWEEN %s AND %s",
            $device_type, $start_date, $end_date
        ));
        
        $orders = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) 
            FROM {$this->table_wc_events} e
            INNER JOIN {$this->table_logs} l ON e.session_id = l.session_id
            WHERE e.event_type = 'order_complete' 
            AND l.device_type = %s
            AND DATE(e.event_time) BETWEEN %s AND %s",
            $device_type, $start_date, $end_date
        ));
        
        return $product_views > 0 ? round(($orders / $product_views) * 100, 2) : 0;
    }

    /**
     * Gibt Top-Produkte nach Umsatz zurück
     */
    private function get_top_products_by_revenue($start_date, $end_date, $limit = 10) {
        $orders_table = $this->wpdb->prefix . 'wc_orders';
        $order_items_table = $this->wpdb->prefix . 'wc_order_items';
        
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT oi.product_id, oi.product_name, 
                    SUM(oi.quantity) as total_quantity,
                    SUM(oi.total_price) as total_revenue,
                    COUNT(DISTINCT oi.order_id) as order_count
            FROM {$order_items_table} oi
            INNER JOIN {$orders_table} o ON oi.order_id = o.id
            INNER JOIN {$this->table_wc_events} e ON o.id = e.order_id
            WHERE e.event_type = 'order_complete' 
            AND DATE(e.event_time) BETWEEN %s AND %s
            GROUP BY oi.product_id, oi.product_name
            ORDER BY total_revenue DESC
            LIMIT %d",
            $start_date, $end_date, $limit
        ), ARRAY_A);
    }


    /**
     * Berechnet Kontakt-Conversions (Telefon/E-Mail)
     */
    private function get_contact_conversions($start_date, $end_date) {
        $phone_clicks = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_wc_events} 
            WHERE event_type = 'phone_click' 
            AND DATE(event_time) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
        
        $email_clicks = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_wc_events} 
            WHERE event_type = 'email_click' 
            AND DATE(event_time) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
        
        return [
            'phone_clicks' => $phone_clicks,
            'email_clicks' => $email_clicks,
            'total_contacts' => $phone_clicks + $email_clicks
        ];
    }

    /**
     * Berechnet Kontakt-Engagement Rate
     */
    private function get_contact_engagement_rate($start_date, $end_date) {
        $product_views = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_wc_events} 
            WHERE event_type = 'product_view' 
            AND DATE(event_time) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
        
        $contact_events = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_wc_events} 
            WHERE event_type IN ('phone_click', 'email_click') 
            AND DATE(event_time) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
        
        return $product_views > 0 ? round(($contact_events / $product_views) * 100, 2) : 0;
    }

    /**
     * Gibt Kontakt-Events zurück
     */
    private function get_contact_events_by_period($start_date, $end_date) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT event_type, COUNT(*) as count,
                    ROUND((COUNT(*) * 100.0 / SUM(COUNT(*)) OVER()), 1) as percentage
            FROM {$this->table_wc_events} 
            WHERE event_type IN ('phone_click', 'email_click')
            AND DATE(event_time) BETWEEN %s AND %s 
            GROUP BY event_type 
            ORDER BY count DESC",
            $start_date, $end_date
        ), ARRAY_A);
    }

    /**
     * Such-Analytics
     */
    private function get_search_analytics($start_date, $end_date) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT extra_value as search_term, COUNT(*) as search_count
            FROM {$this->table_wc_events} 
            WHERE event_type = 'product_search'
            AND DATE(event_time) BETWEEN %s AND %s 
            GROUP BY extra_value 
            ORDER BY search_count DESC 
            LIMIT 10",
            $start_date, $end_date
        ), ARRAY_A);
    }

    /**
     * Kategorie Engagement
     */
    private function get_category_engagement($start_date, $end_date) {
        return $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_wc_events} 
            WHERE event_type = 'category_view' 
            AND DATE(event_time) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
    }

    /**
     * Wunschliste Aktivität
     */
    private function get_wishlist_activity($start_date, $end_date) {
        return $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_wc_events} 
            WHERE event_type = 'add_to_wishlist' 
            AND DATE(event_time) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
    }

    /**
     * Conversion nach Quelle
     */
    private function get_conversion_source_rate($event_types, $start_date, $end_date) {
        return $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_wc_events} 
            WHERE event_type IN (%s) 
            AND DATE(event_time) BETWEEN %s AND %s",
            $event_types, $start_date, $end_date
        ));
    }

    /**
     * Tägliche Besucher der letzten 30 Tage für Charts
     */
    public function get_daily_visitors_chart_data($days = 30) {
        $end_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-'.($days-1).' days'));
        
        // Erstelle alle Tage
        $daily_data = [];
        for ($i = $days-1; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-$i days"));
            $daily_data[$day] = [
                'visitors' => 0,
                'sessions' => 0,
                'page_views' => 0
            ];
        }

        // Besucher pro Tag
        $visitors_data = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT DATE(visit_time) as date, COUNT(DISTINCT session_id) as visitors
            FROM {$this->table_logs} 
            WHERE DATE(visit_time) BETWEEN %s AND %s
            GROUP BY DATE(visit_time)",
            $start_date, $end_date
        ), ARRAY_A);

        // Page Views pro Tag
        $pageviews_data = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT DATE(visit_time) as date, COUNT(*) as page_views
            FROM {$this->table_logs} 
            WHERE DATE(visit_time) BETWEEN %s AND %s
            GROUP BY DATE(visit_time)",
            $start_date, $end_date
        ), ARRAY_A);

        // Fülle die Daten
        foreach ($visitors_data as $row) {
            if (isset($daily_data[$row['date']])) {
                $daily_data[$row['date']]['visitors'] = (int)$row['visitors'];
            }
        }

        foreach ($pageviews_data as $row) {
            if (isset($daily_data[$row['date']])) {
                $daily_data[$row['date']]['page_views'] = (int)$row['page_views'];
            }
        }

        return $daily_data;
    }

    /**
     * Geräteverteilung für Donut Chart mit Zeitraum
     */
    public function get_device_distribution_chart($start_date, $end_date) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT 
                device_type,
                COUNT(DISTINCT session_id) as count,
                ROUND((COUNT(DISTINCT session_id) * 100.0 / 
                    (SELECT COUNT(DISTINCT session_id) FROM {$this->table_logs} WHERE DATE(visit_time) BETWEEN %s AND %s)), 1) as percentage
            FROM {$this->table_logs} 
            WHERE device_type != '' AND DATE(visit_time) BETWEEN %s AND %s
            GROUP BY device_type
            ORDER BY count DESC",
            $start_date, $end_date, $start_date, $end_date
        ), ARRAY_A);
    }

    /**
     * Browser-Verteilung für Pie Chart mit Zeitraum
     */
    public function get_browser_distribution_chart($start_date, $end_date) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT 
                browser_name,
                COUNT(DISTINCT session_id) as count,
                ROUND((COUNT(DISTINCT session_id) * 100.0 / 
                    (SELECT COUNT(DISTINCT session_id) FROM {$this->table_logs} WHERE DATE(visit_time) BETWEEN %s AND %s)), 1) as percentage
            FROM {$this->table_logs} 
            WHERE browser_name != '' AND DATE(visit_time) BETWEEN %s AND %s
            GROUP BY browser_name
            ORDER BY count DESC
            LIMIT 8",
            $start_date, $end_date, $start_date, $end_date
        ), ARRAY_A);
    }

    /**
     * Traffic-Quellen für Donut Chart mit Zeitraum
     */
    public function get_traffic_sources_chart($start_date, $end_date) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT 
                CASE 
                    WHEN source_channel = 'organic' THEN 'Organische Suche'
                    WHEN source_channel = 'direct' THEN 'Direkt'
                    WHEN source_channel = 'social' THEN 'Social Media'
                    WHEN source_channel = 'campaign' THEN 'Kampagnen'
                    WHEN source_channel = 'referral' THEN 'Referral'
                    ELSE 'Andere'
                END as source,
                COUNT(DISTINCT session_id) as count,
                ROUND((COUNT(DISTINCT session_id) * 100.0 / 
                    (SELECT COUNT(DISTINCT session_id) FROM {$this->table_logs} WHERE DATE(visit_time) BETWEEN %s AND %s)), 1) as percentage
            FROM {$this->table_logs} 
            WHERE source_channel != '' AND DATE(visit_time) BETWEEN %s AND %s
            GROUP BY source_channel
            ORDER BY count DESC",
            $start_date, $end_date, $start_date, $end_date
        ), ARRAY_A);
    }

    /**
     * Besuchszeiten Heatmap Daten mit Zeitraum
     */
    public function get_visit_heatmap_data($start_date, $end_date) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT 
                HOUR(visit_time) as hour,
                DAYNAME(visit_time) as day,
                COUNT(DISTINCT session_id) as visits
            FROM {$this->table_logs} 
            WHERE DATE(visit_time) BETWEEN %s AND %s
            GROUP BY HOUR(visit_time), DAYNAME(visit_time)
            ORDER BY FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), hour",
            $start_date, $end_date
        ), ARRAY_A);
    }

    /**
     * Top Städte in Deutschland für Karte/Chart mit Zeitraum
     */
    public function get_german_cities_chart($start_date, $end_date) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT 
                city,
                country_name,
                COUNT(DISTINCT session_id) as count
            FROM {$this->table_logs} 
            WHERE country_code = 'DE' AND city != '' AND DATE(visit_time) BETWEEN %s AND %s
            GROUP BY city, country_name
            ORDER BY count DESC
            LIMIT 15",
            $start_date, $end_date
        ), ARRAY_A);
    }

    /**
     * Seiten-Performance (Aufrufe vs. Verweildauer) mit Zeitraum
     */
    public function get_page_performance_chart($start_date, $end_date) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT 
                page_title,
                url,
                COUNT(*) as views,
                AVG(time_on_page) as avg_time_on_page
            FROM {$this->table_logs} 
            WHERE page_title != '' AND time_on_page > 0 AND DATE(visit_time) BETWEEN %s AND %s
            GROUP BY page_title, url
            HAVING views > 10
            ORDER BY views DESC
            LIMIT 10",
            $start_date, $end_date
        ), ARRAY_A);
    }
}