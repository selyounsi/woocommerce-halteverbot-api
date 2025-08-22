<?php

namespace Utils;

class VisitorTracker {

    private static $instance = null;

    private $table_logs;
    private $table_wc_events;
    private $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_logs = $wpdb->prefix . 'wha_visitor_logs';
        $this->table_wc_events = $wpdb->prefix . 'wha_visitor_events';

        register_activation_hook(__FILE__, [$this, 'create_tables']);

        // Besucher tracking
        add_action('wp_footer', [$this, 'track_visit']);

        // WooCommerce Hooks
        add_action('woocommerce_after_single_product', [$this, 'track_product_view']);
        add_action('woocommerce_add_to_cart', [$this, 'track_add_to_cart'], 10, 6);
        add_action('woocommerce_thankyou', [$this, 'track_order_complete']);
    }


    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }


    /**
     * Tabellen erstellen
     */
    public function create_tables() {
        $charset_collate = $this->wpdb->get_charset_collate();

        // Tabelle für Besucherlogs
        $sql1 = "CREATE TABLE IF NOT EXISTS {$this->table_logs} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            ip VARCHAR(45) NOT NULL,
            user_agent TEXT NOT NULL,
            referrer TEXT NULL,
            url TEXT NOT NULL,
            visit_time DATETIME NOT NULL,
            device_type VARCHAR(10) NOT NULL,
            browser_name VARCHAR(50) NOT NULL,
            keywords TEXT NULL,
            PRIMARY KEY (id),
            INDEX (visit_time),
            INDEX (ip)
        ) $charset_collate;";

        // Tabelle für WooCommerce Events
        $sql2 = "CREATE TABLE IF NOT EXISTS {$this->table_wc_events} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            event_type VARCHAR(50) NOT NULL,
            user_ip VARCHAR(45) NOT NULL,
            user_agent TEXT NOT NULL,
            event_time DATETIME NOT NULL,
            product_id BIGINT(20) NULL,
            order_id BIGINT(20) NULL,
            quantity INT NULL,
            PRIMARY KEY (id),
            INDEX (event_time)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql1);
        dbDelta($sql2);
    }

    /**
     * Prüft, ob die Besuchertabelle existiert
     */
    public function table_exists(): bool {
        $table = $this->table_logs;
        $result = $this->wpdb->get_var("SHOW TABLES LIKE '{$table}'");
        return ($result === $table);
    }

    /**
     * Holt alle Besucherdaten
     *
     * @param int|null $limit Maximale Anzahl (optional)
     * @return array
     */
    public function get_all_visits(?int $limit = null): array{
        $sql = "SELECT * FROM {$this->table_logs} ORDER BY visit_time DESC";

        if ($limit !== null) {
            $sql .= $this->wpdb->prepare(" LIMIT %d", $limit);
        }

        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * Besucher erfassen
     */
    public function track_visit($data = []) {
        if (wp_doing_cron()) {
            error_log("TRACKER: Visit skipped: doing cron");
            return;
        }

        // Prüfen, ob Werte übergeben wurden (AJAX), sonst Fallback auf $_SERVER
        $user_agent = $data['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? '');
        $referrer   = $data['referrer'] ?? ($_SERVER['HTTP_REFERER'] ?? '');
        $url        = $data['url'] ?? (
            (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http")
            . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"
        );

        if (stripos($user_agent, 'Mozilla') !== 0) {
            error_log("TRACKER: Visit skipped: kein Browser User-Agent");
            return;
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $visit_time = current_time('mysql');

        $device_type  = $this->detect_device($user_agent);
        $browser_name = $this->detect_browser($user_agent);
        $keywords     = $this->extract_keywords($referrer);

        $result = $this->wpdb->insert(
            $this->table_logs,
            [
                'ip'           => $ip,
                'user_agent'   => $user_agent,
                'referrer'     => $referrer,
                'url'          => $url,
                'visit_time'   => $visit_time,
                'device_type'  => $device_type,
                'browser_name' => $browser_name,
                'keywords'     => $keywords,
            ],
            ['%s','%s','%s','%s','%s','%s','%s','%s']
        );

        if ($result === false) {
            error_log("TRACKER: INSERT fehlgeschlagen: " . $this->wpdb->last_error);
        } else {
            error_log("TRACKER: INSERT erfolgreich");
        }
    }

    /**
     * WooCommerce Produkt-Views tracken
     */
    public function track_product_view() {
        if (!is_product()) return;

        global $product;

        $this->insert_wc_event('product_view', $product->get_id(), null, null);
    }

    /**
     * WooCommerce Add to Cart tracken
     * $product_id, $quantity etc. kommen von Hook
     */
    public function track_add_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
        $this->insert_wc_event('add_to_cart', $product_id, null, $quantity);
    }

    /**
     * WooCommerce Bestellabschluss tracken
     */
    public function track_order_complete($order_id) {
        $this->insert_wc_event('order_complete', null, $order_id, null);
    }

    /**
     * WC Event in DB speichern
     */
    private function insert_wc_event($event_type, $product_id = null, $order_id = null, $quantity = null) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $event_time = current_time('mysql');

        $this->wpdb->insert(
            $this->table_wc_events,
            [
                'event_type' => $event_type,
                'user_ip' => $ip,
                'user_agent' => $user_agent,
                'event_time' => $event_time,
                'product_id' => $product_id,
                'order_id' => $order_id,
                'quantity' => $quantity,
            ],
            ['%s', '%s', '%s', '%s', '%d', '%d', '%d']
        );
    }

    // --- Hilfsfunktionen ---

    private function detect_device($user_agent) {
        $user_agent = strtolower($user_agent);
        if (preg_match('/mobile|android|touch|silk|kindle|blackberry|phone|opera mini|opera mobi/', $user_agent)) {
            return 'mobile';
        }
        return 'desktop';
    }

    function is_real_browser(string $userAgent): bool {
        return stripos($userAgent, 'mozilla/') === 0;
    }

    private function detect_browser($user_agent) {
        $ua = strtolower($user_agent);

        if (strpos($ua, 'edg/') !== false) {
            return 'Edge'; // Edge Chromium basiert auf Chrome, aber "Edg/" im UA-String ist der Key
        }
        if (strpos($ua, 'opr/') !== false || strpos($ua, 'opera') !== false) {
            return 'Opera';
        }
        if (strpos($ua, 'chrome') !== false) {
            return 'Chrome';
        }
        if (strpos($ua, 'safari') !== false && strpos($ua, 'chrome') === false) {
            return 'Safari';
        }
        if (strpos($ua, 'firefox') !== false) {
            return 'Firefox';
        }
        if (strpos($ua, 'msie') !== false || strpos($ua, 'trident/7') !== false) {
            return 'Internet Explorer';
        }

        return 'Unknown';
    }

    private function extract_keywords($referrer) {
        if (empty($referrer)) return '';

        $host = parse_url($referrer, PHP_URL_HOST);
        $query = parse_url($referrer, PHP_URL_QUERY);

        if (empty($host) || empty($query)) return '';

        if (strpos($host, 'google.') !== false) {
            parse_str($query, $params);
            return $params['q'] ?? '';
        }
        // Andere Suchmaschinen können hier ergänzt werden

        return '';
    }

    // --- Auswertung Methoden ---

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
