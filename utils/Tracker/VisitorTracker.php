<?php

namespace Utils\Tracker;

use WhichBrowser\Parser;
use DeviceDetector\DeviceDetector;

class VisitorTracker {

    public static $instance = null;
    public $table_logs;
    public $table_wc_events;
    public $wpdb;
    private $deviceDetector;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_logs = $wpdb->prefix . 'wha_visitor_logs';
        $this->table_wc_events = $wpdb->prefix . 'wha_visitor_events';

        // DeviceDetector sicher initialisieren
        $this->deviceDetector = new DeviceDetector();
        
        // Nur setzen wenn Klasse existiert
        // if (class_exists('\DeviceDetector\Parser\Device\DeviceParserAbstract')) {
        //     \DeviceDetector\Parser\Device\DeviceParserAbstract::setVersionTruncation(
        //         \DeviceDetector\Parser\Device\DeviceParserAbstract::VERSION_TRUNCATION_NONE
        //     );
        // }

        register_activation_hook(__FILE__, [$this, 'create_tables']);

        // Tracking Hooks
        add_action('wp_footer', [$this, 'track_visit']);
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
     * Erweiterte Tabellenstruktur
     */
    public function create_tables() {
        $charset_collate = $this->wpdb->get_charset_collate();

        $sql1 = "CREATE TABLE IF NOT EXISTS {$this->table_logs} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id VARCHAR(32) NOT NULL,
            ip VARCHAR(45) NOT NULL,
            user_agent TEXT NOT NULL,
            referrer TEXT NULL,
            url TEXT NOT NULL,
            visit_time DATETIME NOT NULL,
            
            -- Device & Browser Details
            device_type VARCHAR(20) NOT NULL,
            device_brand VARCHAR(50) NULL,
            device_model VARCHAR(100) NULL,
            browser_name VARCHAR(50) NOT NULL,
            browser_version VARCHAR(20) NULL,
            platform VARCHAR(50) NULL,
            platform_version VARCHAR(20) NULL,
            browser_engine VARCHAR(50) NULL,
            
            -- Location Data
            country_code VARCHAR(2) NULL,
            country_name VARCHAR(100) NULL,
            region VARCHAR(100) NULL,
            city VARCHAR(100) NULL,
            latitude DECIMAL(10,8) NULL,
            longitude DECIMAL(11,8) NULL,
            timezone VARCHAR(50) NULL,
            
            -- Traffic Source
            source_channel VARCHAR(50) NULL,
            source_name VARCHAR(100) NULL,
            medium VARCHAR(50) NULL,
            campaign VARCHAR(100) NULL,
            content VARCHAR(100) NULL,
            keywords TEXT NULL,
            
            -- Page Details
            page_title VARCHAR(255) NULL,
            time_on_page INT DEFAULT 0,
            page_load_time INT NULL,
            screen_resolution VARCHAR(20) NULL,
            language VARCHAR(50) NULL,
            
            -- Technical Details
            java_enabled TINYINT(1) DEFAULT 0,
            cookies_enabled TINYINT(1) DEFAULT 1,
            do_not_track TINYINT(1) DEFAULT 0,
            is_bot TINYINT(1) DEFAULT 0,
            
            PRIMARY KEY (id),
            INDEX idx_session_id (session_id),
            INDEX idx_visit_time (visit_time),
            INDEX idx_ip (ip),
            INDEX idx_country (country_code),
            INDEX idx_device (device_type),
            INDEX idx_browser (browser_name),
            INDEX idx_source (source_channel)
        ) $charset_collate;";

        $sql2 = "CREATE TABLE IF NOT EXISTS {$this->table_wc_events} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id VARCHAR(32) NOT NULL,
            event_type VARCHAR(50) NOT NULL,
            user_ip VARCHAR(45) NOT NULL,
            user_agent TEXT NOT NULL,
            event_time DATETIME NOT NULL,
            product_id BIGINT(20) NULL,
            order_id BIGINT(20) NULL,
            quantity INT NULL,
            product_price DECIMAL(10,2) NULL,
            product_category VARCHAR(100) NULL,
            cart_total DECIMAL(10,2) NULL,
            user_status ENUM('guest', 'logged_in') DEFAULT 'guest',
            PRIMARY KEY (id),
            INDEX idx_session_id (session_id),
            INDEX idx_event_time (event_time),
            INDEX idx_event_type (event_type),
            INDEX idx_product_id (product_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql1);
        dbDelta($sql2);
    }

    /**
     * Haupt-Tracking Methode
     */
    public function track_visit($data = []) {

        // if (wp_doing_cron()) return;
        // var_dump("test2");
        // if (is_admin()) return;
        // var_dump("test3");

        // Basis-Daten
        $user_agent = $data['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? '');
        $referrer = $data['referrer'] ?? ($_SERVER['HTTP_REFERER'] ?? '');
        $url = $data['url'] ?? $this->get_current_url();
        $page_title = $data['page_title'] ?? $this->get_page_title();
        $ip = $data['ip'] ?? $this->get_client_ip();
        
        // Validierungen
        if (!$this->is_real_browser($user_agent)) return;
        if (!$this->is_valid_ip($ip)) return;

        // Session handling
        $session_id = $this->get_or_create_session_id();
        if ($this->is_duplicate_visit($session_id, $url)) return;

        // Umfassende Daten sammeln
        $comprehensive_data = $this->get_comprehensive_visitor_data($user_agent, $ip, $referrer, $url);

        // Visit speichern
        $this->save_visit(array_merge($comprehensive_data, [
            'session_id' => $session_id,
            'url' => $url,
            'page_title' => $page_title,
            'time_on_page' => $this->calculate_time_on_previous_page($session_id)
        ]));
    }

    /**
     * Umfassende Visitor-Daten sammeln
     */
    private function get_comprehensive_visitor_data($user_agent, $ip, $referrer, $current_url) {
        return [
            'device' => $this->get_device_data($user_agent),
            'location' => $this->get_location_data($ip),
            'traffic' => $this->get_traffic_source_data($referrer, $current_url),
            'technical' => $this->get_technical_data(),
            'performance' => $this->get_performance_data()
        ];
    }

    /**
     * Detaillierte Device-Erkennung mit DeviceDetector
     */
    private function get_device_data($user_agent) {
        $this->deviceDetector->setUserAgent($user_agent);
        $this->deviceDetector->parse();

        // Zusätzlich WhichBrowser für Engine-Info
        $whichBrowser = new Parser($user_agent);

        return [
            'user_agent' => $user_agent,
            'device_type' => $this->get_device_type(),
            'device_brand' => $this->deviceDetector->getBrandName() ?: 'Unknown',
            'device_model' => $this->deviceDetector->getModel() ?: '',
            'browser_name' => $this->deviceDetector->getClient('name') ?: 'Unknown',
            'browser_version' => $this->deviceDetector->getClient('version') ?: '',
            'platform' => $this->deviceDetector->getOs('name') ?: 'Unknown',
            'platform_version' => $this->deviceDetector->getOs('version') ?: '',
            'browser_engine' => $whichBrowser->engine->getName() ?: '',
            'is_bot' => $this->deviceDetector->isBot() ? 1 : 0
        ];
    }

    private function get_device_type() {
        if ($this->deviceDetector->isMobile()) return 'mobile';
        if ($this->deviceDetector->isTablet()) return 'tablet';
        if ($this->deviceDetector->isDesktop()) return 'desktop';
        return 'unknown';
    }

    /**
     * Location-Daten mit mehreren Fallbacks
     */
    private function get_location_data($ip) {
        // Versuche externe API
        $external_data = $this->get_location_from_external_api($ip);
        if ($external_data) return $external_data;

        // Fallback: IP-based detection
        return $this->get_basic_location_data($ip);
    }

    private function get_location_from_external_api($ip) {
        $apis = [
            "http://ip-api.com/json/{$ip}",
            "https://api.ipbase.com/v2/info?ip={$ip}&apikey=YOUR_API_KEY"
        ];

        foreach ($apis as $api_url) {
            try {
                $response = wp_remote_get($api_url, ['timeout' => 2]);
                if (is_wp_error($response)) continue;

                $data = json_decode(wp_remote_retrieve_body($response), true);

                if (strpos($api_url, 'ip-api.com') !== false && isset($data['countryCode'])) {
                    return [
                        'country_code' => $data['countryCode'],
                        'country_name' => $data['country'],
                        'region' => $data['regionName'] ?? '',
                        'city' => $data['city'] ?? '',
                        'latitude' => $data['lat'] ?? null,
                        'longitude' => $data['lon'] ?? null,
                        'timezone' => $data['timezone'] ?? ''
                    ];
                }
            } catch (Exception $e) {
                continue;
            }
        }

        return null;
    }

    private function get_basic_location_data($ip) {
        $country_code = $this->ip_to_country($ip);
        return [
            'country_code' => $country_code,
            'country_name' => $this->country_code_to_name($country_code),
            'region' => '',
            'city' => '',
            'latitude' => null,
            'longitude' => null,
            'timezone' => date_default_timezone_get()
        ];
    }

    /**
     * Traffic Source Analysis
     */
    private function get_traffic_source_data($referrer, $current_url) {
        $utm_source = $_GET['utm_source'] ?? '';
        $utm_medium = $_GET['utm_medium'] ?? '';
        $utm_campaign = $_GET['utm_campaign'] ?? '';
        $utm_content = $_GET['utm_content'] ?? '';

        if (!empty($utm_source)) {
            return [
                'source_channel' => 'campaign',
                'source_name' => $utm_source,
                'medium' => $utm_medium,
                'campaign' => $utm_campaign,
                'content' => $utm_content,
                'keywords' => $this->extract_keywords($referrer),
                'referrer' => $referrer
            ];
        }

        if (!empty($referrer)) {
            $ref_data = $this->parse_referrer($referrer);
            return [
                'source_channel' => $ref_data['channel'],
                'source_name' => $ref_data['source'],
                'medium' => 'referral',
                'campaign' => '',
                'content' => '',
                'keywords' => $ref_data['keywords'],
                'referrer' => $referrer
            ];
        }

        return [
            'source_channel' => 'direct',
            'source_name' => 'direct',
            'medium' => 'none',
            'campaign' => '',
            'content' => '',
            'keywords' => '',
            'referrer' => $referrer
        ];
    }

    private function parse_referrer($referrer) {
        $host = parse_url($referrer, PHP_URL_HOST);
        $query = parse_url($referrer, PHP_URL_QUERY);

        $keywords = '';
        if ($host && $query) {
            parse_str($query, $params);
            if (strpos($host, 'google.') !== false) {
                $keywords = $params['q'] ?? '';
            } elseif (strpos($host, 'bing.') !== false) {
                $keywords = $params['q'] ?? '';
            }
        }

        if (strpos($host, 'google.') !== false) return ['channel' => 'organic', 'source' => 'Google', 'keywords' => $keywords];
        if (strpos($host, 'bing.') !== false) return ['channel' => 'organic', 'source' => 'Bing', 'keywords' => $keywords];
        if (strpos($host, 'facebook.') !== false) return ['channel' => 'social', 'source' => 'Facebook', 'keywords' => $keywords];
        if (strpos($host, 'twitter.') !== false) return ['channel' => 'social', 'source' => 'Twitter', 'keywords' => $keywords];

        return ['channel' => 'referral', 'source' => $host, 'keywords' => $keywords];
    }

    /**
     * Keywords aus Referrer-URL extrahieren
     */
    private function extract_keywords($referrer) {
        if (empty($referrer)) return '';

        $host = parse_url($referrer, PHP_URL_HOST);
        $query = parse_url($referrer, PHP_URL_QUERY);

        if (empty($host) || empty($query)) return '';

        parse_str($query, $params);

        // Suchmaschinen Keywords erkennen
        if (strpos($host, 'google.') !== false) {
            return $params['q'] ?? '';
        }
        if (strpos($host, 'bing.') !== false) {
            return $params['q'] ?? '';
        }
        if (strpos($host, 'yahoo.') !== false) {
            return $params['p'] ?? '';
        }
        if (strpos($host, 'duckduckgo.') !== false) {
            return $params['q'] ?? '';
        }
        if (strpos($host, 'ecosia.') !== false) {
            return $params['q'] ?? '';
        }

        return '';
    }

    /**
     * Technische Daten
     */
    private function get_technical_data() {
        return [
            'screen_resolution' => $_POST['techData']['resolution'] ?? '',
            'language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
            'java_enabled' => $_POST['techData']['java_enabled'] ?? 0,
            'cookies_enabled' => $_POST['techData']['cookies_enabled'] ?? 1,
            'do_not_track' => isset($_SERVER['HTTP_DNT']) && $_SERVER['HTTP_DNT'] == 1 ? 1 : 0
        ];
    }

    /**
     * Performance-Daten
     */
    private function get_performance_data() {
        $load_time = $_POST['techData']['page_load_time'] ?? $this->calculate_page_load_time();
        return ['page_load_time' => (int) $load_time];
    }

    /**
     * Visit in Datenbank speichern
     */
    private function save_visit($data) 
    {
        $visit_time = current_time('mysql');

        $result = $this->wpdb->insert(
            $this->table_logs,
            [
                'session_id' => $data['session_id'],
                'ip' => $data['location']['ip'] ?? $this->get_client_ip(),
                'user_agent' => $data['device']['user_agent'] ?? '',
                'referrer' => $data['traffic']['referrer'] ?? '',
                'url' => $data['url'],
                'visit_time' => $visit_time,
                
                // Device
                'device_type' => $data['device']['device_type'],
                'device_brand' => $data['device']['device_brand'],
                'device_model' => $data['device']['device_model'],
                'browser_name' => $data['device']['browser_name'],
                'browser_version' => $data['device']['browser_version'],
                'platform' => $data['device']['platform'],
                'platform_version' => $data['device']['platform_version'],
                'browser_engine' => $data['device']['browser_engine'],
                'is_bot' => $data['device']['is_bot'],
                
                // Location
                'country_code' => $data['location']['country_code'],
                'country_name' => $data['location']['country_name'],
                'region' => $data['location']['region'],
                'city' => $data['location']['city'],
                'latitude' => $data['location']['latitude'],
                'longitude' => $data['location']['longitude'],
                'timezone' => $data['location']['timezone'],
                
                // Traffic
                'source_channel' => $data['traffic']['source_channel'],
                'source_name' => $data['traffic']['source_name'],
                'medium' => $data['traffic']['medium'],
                'campaign' => $data['traffic']['campaign'],
                'content' => $data['traffic']['content'],
                'keywords' => $data['traffic']['keywords'],
                
                // Page & Technical
                'page_title' => $data['page_title'],
                'time_on_page' => $data['time_on_page'],
                'page_load_time' => $data['performance']['page_load_time'],
                'screen_resolution' => $data['technical']['screen_resolution'],
                'language' => $data['technical']['language'],
                'java_enabled' => $data['technical']['java_enabled'],
                'cookies_enabled' => $data['technical']['cookies_enabled'],
                'do_not_track' => $data['technical']['do_not_track']
            ],
            $this->get_data_types()
        );

        if ($result === false) {
            var_dump($data);
            error_log("TRACKER: INSERT fehlgeschlagen: " . $this->wpdb->last_error);
        }
    }

    private function get_data_types() {
        return [
            '%s','%s','%s','%s','%s','%s',  // Basis
            '%s','%s','%s','%s','%s','%s','%s','%s','%d',  // Device
            '%s','%s','%s','%s','%f','%f','%s',  // Location
            '%s','%s','%s','%s','%s','%s',  // Traffic
            '%s','%d','%d','%s','%s','%d','%d','%d'  // Technical
        ];
    }

    // --- Hilfsmethoden ---

    private function get_current_url() {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") 
               . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    }

    private function get_page_title() {
        return function_exists('wp_get_document_title') ? wp_get_document_title() : '';
    }

    private function get_client_ip() {
        $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = explode(',', $_SERVER[$key])[0];
                if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    private function is_valid_ip($ip) {
        return !empty($ip) && !in_array($ip, ['127.0.0.1', '::1', 'localhost']);
    }

    private function is_real_browser($user_agent) {
        if (empty($user_agent)) return false;
        
        $bots = ['bot', 'crawler', 'spider', 'scraper', 'curl', 'wget'];
        $ua_lower = strtolower($user_agent);
        
        foreach ($bots as $bot) {
            if (strpos($ua_lower, $bot) !== false) return false;
        }
        
        return stripos($user_agent, 'Mozilla') === 0 || 
               preg_match('/(chrome|safari|firefox|edge|opera)\//i', $user_agent);
    }

    private function get_or_create_session_id() {
        $cookie_name = 'wha_session_id';
        
        if (isset($_COOKIE[$cookie_name]) && preg_match('/^[a-f0-9]{32}$/', $_COOKIE[$cookie_name])) {
            return $_COOKIE[$cookie_name];
        }
        
        $session_id = md5(uniqid('', true) . mt_rand() . time());
        setcookie($cookie_name, $session_id, time() + (86400 * 30), '/');
        return $session_id;
    }

    private function is_duplicate_visit($session_id, $url) {
        $recent_visit = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT id FROM {$this->table_logs} 
             WHERE session_id = %s AND url = %s AND visit_time > DATE_SUB(NOW(), INTERVAL 2 MINUTE) 
             LIMIT 1",
            $session_id, $url
        ));
        
        if ($recent_visit) {
            $this->wpdb->update(
                $this->table_logs,
                ['visit_time' => current_time('mysql')],
                ['id' => $recent_visit],
                ['%s'],
                ['%d']
            );
            return true;
        }
        
        return false;
    }

    private function calculate_time_on_previous_page($session_id) {
        $last_visit = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT visit_time FROM {$this->table_logs} 
             WHERE session_id = %s 
             ORDER BY visit_time DESC 
             LIMIT 1, 1",
            $session_id
        ));
        
        if ($last_visit) {
            $time_spent = current_time('timestamp') - strtotime($last_visit->visit_time);
            return ($time_spent > 0 && $time_spent < 7200) ? $time_spent : 0;
        }
        
        return 0;
    }

    private function calculate_page_load_time() {
        if (defined('WP_START_TIMESTAMP')) {
            return (int) ((microtime(true) - WP_START_TIMESTAMP) * 1000);
        }
        if (isset($_SERVER['REQUEST_TIME_FLOAT'])) {
            return (int) ((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000);
        }
        return 0;
    }

    private function ip_to_country($ip) {
        // Vereinfachte IP-to-Country Erkennung
        $ranges = [
            'DE' => [['5.10.0.0', '5.10.255.255'], ['31.10.0.0', '31.10.255.255']],
            'AT' => [['77.80.0.0', '77.80.255.255']],
            'CH' => [['85.0.0.0', '85.0.255.255']],
            'US' => [['8.0.0.0', '8.255.255.255']],
            'GB' => [['25.0.0.0', '25.255.255.255']]
        ];
        
        $ip_long = ip2long($ip);
        if ($ip_long === false) return 'UN';
        
        foreach ($ranges as $country => $range_list) {
            foreach ($range_list as $range) {
                if ($ip_long >= ip2long($range[0]) && $ip_long <= ip2long($range[1])) {
                    return $country;
                }
            }
        }
        
        return 'UN';
    }

    private function country_code_to_name($country_code) {
        $countries = [
            'DE' => 'Germany', 'AT' => 'Austria', 'CH' => 'Switzerland',
            'FR' => 'France', 'NL' => 'Netherlands', 'US' => 'United States',
            'GB' => 'United Kingdom', 'UN' => 'Unknown'
        ];
        return $countries[$country_code] ?? 'Unknown';
    }

    // --- WooCommerce Tracking ---

    public function track_product_view() {
        if (!is_product()) return;
        global $product;
        $this->insert_wc_event('product_view', $product->get_id());
    }

    public function track_add_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
        $this->insert_wc_event('add_to_cart', $product_id, null, $quantity);
    }

    public function track_order_complete($order_id) {
        $this->insert_wc_event('order_complete', null, $order_id);
    }

    private function insert_wc_event($event_type, $product_id = null, $order_id = null, $quantity = null) {
        $product_price = $product_id ? $this->get_product_price($product_id) : null;
        $product_category = $product_id ? $this->get_product_category($product_id) : null;
        
        $this->wpdb->insert(
            $this->table_wc_events,
            [
                'session_id' => $this->get_or_create_session_id(),
                'event_type' => $event_type,
                'user_ip' => $this->get_client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'event_time' => current_time('mysql'),
                'product_id' => $product_id,
                'order_id' => $order_id,
                'quantity' => $quantity,
                'product_price' => $product_price,
                'product_category' => $product_category,
                'cart_total' => WC()->cart ? WC()->cart->get_total('edit') : null,
                'user_status' => is_user_logged_in() ? 'logged_in' : 'guest'
            ],
            ['%s','%s','%s','%s','%s','%d','%d','%d','%f','%s','%f','%s']
        );
    }

    private function get_product_price($product_id) {
        $product = wc_get_product($product_id);
        return $product ? $product->get_price() : null;
    }

    private function get_product_category($product_id) {
        $categories = wp_get_post_terms($product_id, 'product_cat');
        return !empty($categories) ? $categories[0]->name : '';
    }

    /**
     * Tabellen existieren?
     */
    public function table_exists(): bool {
        return $this->wpdb->get_var("SHOW TABLES LIKE '{$this->table_logs}'") === $this->table_logs;
    }

    /**
     * Besucherdaten abrufen
     */
    public function get_all_visits(?int $limit = null): array {
        $sql = "SELECT * FROM {$this->table_logs} ORDER BY visit_time DESC";
        if ($limit !== null) $sql .= $this->wpdb->prepare(" LIMIT %d", $limit);
        return $this->wpdb->get_results($sql, ARRAY_A);
    }
}