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

    private function query_count($where_sql = '', $params = []) {
        $sql = "SELECT COUNT(DISTINCT ip) FROM {$this->table_logs} WHERE 1=1 $where_sql";
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

    public function visitors_by_referrer() {
        $sql = "SELECT 
            CASE 
                WHEN referrer LIKE '%google.%' THEN 'Google'
                WHEN referrer LIKE '%bing.%' THEN 'Bing'
                WHEN referrer LIKE '%yahoo.%' THEN 'Yahoo'
                WHEN referrer = '' OR referrer IS NULL THEN 'Direkt'
                ELSE 'Andere'
            END as source,
            COUNT(*) as count
            FROM {$this->table_logs}
            GROUP BY source
            ORDER BY count DESC";
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    public function visitors_by_device() {
        $sql = "SELECT device_type, COUNT(*) as count FROM {$this->table_logs} GROUP BY device_type ORDER BY count DESC";
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    public function visitors_by_browser() {
        $sql = "SELECT browser_name, COUNT(*) as count FROM {$this->table_logs} GROUP BY browser_name ORDER BY count DESC";
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    public function page_views() {
        $sql = "SELECT url, COUNT(*) as count FROM {$this->table_logs} GROUP BY url ORDER BY count DESC LIMIT 20";
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    public function popular_keywords() {
        $sql = "SELECT keywords, COUNT(*) as count FROM {$this->table_logs} WHERE keywords != '' GROUP BY keywords ORDER BY count DESC LIMIT 20";
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    // WooCommerce Event Auswertungen

    /**
     * Anzahl Events nach Typ (z.B. Produkt-Views, Add-to-Cart, Orders)
     */
    public function wc_events_count_by_type() {
        $sql = "SELECT event_type, COUNT(*) as count FROM {$this->table_wc_events} GROUP BY event_type ORDER BY count DESC";
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * Meist betrachtete Produkte (Produkt-Views)
     */
    public function wc_top_viewed_products($limit = 10) {
        $sql = $this->wpdb->prepare("SELECT product_id, COUNT(*) as views FROM {$this->table_wc_events} WHERE event_type = %s GROUP BY product_id ORDER BY views DESC LIMIT %d", 'product_view', $limit);
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * Meist zum Warenkorb hinzugefügte Produkte
     */
    public function wc_top_added_to_cart_products($limit = 10) {
        $sql = $this->wpdb->prepare("SELECT product_id, SUM(quantity) as qty_added FROM {$this->table_wc_events} WHERE event_type = %s GROUP BY product_id ORDER BY qty_added DESC LIMIT %d", 'add_to_cart', $limit);
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * Anzahl Bestellungen
     */
    public function wc_order_count() {
        $sql = $this->wpdb->prepare("SELECT COUNT(DISTINCT order_id) FROM {$this->table_wc_events} WHERE event_type = %s", 'order_complete');
        return (int) $this->wpdb->get_var($sql);
    }

    /**
     * Durchschnittliche Menge pro Bestellung (über alle add_to_cart Events)
     */
    public function wc_avg_quantity_per_order() {
        $sql = "SELECT AVG(qty_per_order) FROM (
            SELECT SUM(quantity) as qty_per_order, order_id FROM {$this->table_wc_events}
            WHERE event_type = 'add_to_cart'
            GROUP BY order_id
        ) as t";
        return $this->wpdb->get_var($sql);
    }


    public function get_report($start_date, $end_date): array {
        // Alle Besuche (einmalig)
        $total_visits = $this->visitors_by_period($start_date, $end_date);

        // Besuche nach Device
        $devices = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT device_type, COUNT(*) as count 
                FROM {$this->table_logs} 
                WHERE DATE(visit_time) BETWEEN %s AND %s 
                GROUP BY device_type 
                ORDER BY count DESC",
                $start_date, $end_date
            ),
            ARRAY_A
        );

        // Besuche nach Browser
        $browsers = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT browser_name, COUNT(*) as count 
                FROM {$this->table_logs} 
                WHERE DATE(visit_time) BETWEEN %s AND %s 
                GROUP BY browser_name 
                ORDER BY count DESC",
                $start_date, $end_date
            ),
            ARRAY_A
        );

        // Referrer (Herkunft)
        $referrers = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT 
                    CASE 
                        WHEN referrer LIKE '%%google.%%' THEN 'Google'
                        WHEN referrer LIKE '%%bing.%%' THEN 'Bing'
                        WHEN referrer LIKE '%%yahoo.%%' THEN 'Yahoo'
                        WHEN referrer = '' OR referrer IS NULL THEN 'Direkt'
                        ELSE 'Andere'
                    END as source,
                    COUNT(*) as count
                FROM {$this->table_logs}
                WHERE DATE(visit_time) BETWEEN %s AND %s
                GROUP BY source
                ORDER BY count DESC",
                $start_date, $end_date
            ),
            ARRAY_A
        );

        // Beliebte Seiten
        $pages = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT url, COUNT(*) as count 
                FROM {$this->table_logs} 
                WHERE DATE(visit_time) BETWEEN %s AND %s 
                GROUP BY url 
                ORDER BY count DESC 
                LIMIT 20",
                $start_date, $end_date
            ),
            ARRAY_A
        );

        // Keywords
        $keywords = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT keywords, COUNT(*) as count 
                FROM {$this->table_logs} 
                WHERE keywords != '' AND DATE(visit_time) BETWEEN %s AND %s 
                GROUP BY keywords 
                ORDER BY count DESC 
                LIMIT 20",
                $start_date, $end_date
            ),
            ARRAY_A
        );

        // WC-Events in Zeitraum
        $wc_events = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT event_type, COUNT(*) as count 
                FROM {$this->table_wc_events} 
                WHERE DATE(event_time) BETWEEN %s AND %s 
                GROUP BY event_type 
                ORDER BY count DESC",
                $start_date, $end_date
            ),
            ARRAY_A
        );

        return [
            'total_visits' => $total_visits,
            'devices' => $devices,
            'browsers' => $browsers,
            'referrers' => $referrers,
            'pages' => $pages,
            'keywords' => $keywords,
            'wc_events' => $wc_events,
        ];
    }
}