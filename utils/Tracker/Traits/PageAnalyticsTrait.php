<?php

namespace Utils\Tracker\Traits;

trait PageAnalyticsTrait {

    // WordPress Startseiten-URL holen
    private function get_home_url() {
        return home_url('/');
    }

    // WooCommerce Seiten-URLs holen
    private function get_woocommerce_page_urls() {
        return [
            'shop' => wc_get_page_id('shop') ? get_permalink(wc_get_page_id('shop')) : '',
            'cart' => wc_get_page_id('cart') ? get_permalink(wc_get_page_id('cart')) : '',
            'checkout' => wc_get_page_id('checkout') ? get_permalink(wc_get_page_id('checkout')) : '',
            'myaccount' => wc_get_page_id('myaccount') ? get_permalink(wc_get_page_id('myaccount')) : '',
            'terms' => wc_get_page_id('terms') ? get_permalink(wc_get_page_id('terms')) : ''
        ];
    }

    // Alle Produkt-URLs aus der Datenbank holen
    private function get_product_urls() {
        $products = $this->wpdb->get_results(
            "SELECT ID, post_name FROM {$this->wpdb->posts} 
            WHERE post_type = 'product' AND post_status = 'publish'"
        );
        
        $urls = [];
        foreach ($products as $product) {
            // Verwende get_permalink für die korrekte Frontend-URL
            $urls[] = get_permalink($product->ID);
        }
        
        return $urls;
    }

    // Zusätzlich: Methode um Produkt-URLs anhand des Slugs zu erkennen
    private function get_product_slugs() {
        return $this->wpdb->get_col(
            "SELECT post_name FROM {$this->wpdb->posts} 
            WHERE post_type = 'product' AND post_status = 'publish'"
        );
    }

    // Alle Kategorie-URLs aus der Datenbank holen
    private function get_category_urls() {
        return $this->wpdb->get_col(
            "SELECT t.slug 
             FROM {$this->wpdb->terms} t 
             INNER JOIN {$this->wpdb->term_taxonomy} tt ON t.term_id = tt.term_id 
             WHERE tt.taxonomy = 'product_cat'"
        );
    }

    // Automatisierte Seiten-Kategorisierung (MIT KORREKTEN PRODUKT-URLs)
    public function page_performance_by_category($start_date, $end_date, $device_type = null) {
        $home_url = $this->get_home_url();
        $woo_urls = $this->get_woocommerce_page_urls();
        $product_urls = $this->get_product_urls(); // Jetzt mit korrekten URLs
        $product_slugs = $this->get_product_slugs();
        $category_slugs = $this->get_category_urls();
        
        // Baue die CASE WHEN Bedingungen dynamisch auf
        $case_conditions = [
            "WHEN url = '{$home_url}' THEN 'Startseite'"
        ];
        
        // WooCommerce Seiten
        if (!empty($woo_urls['shop'])) {
            $case_conditions[] = "WHEN url = '{$woo_urls['shop']}' THEN 'Shop'";
        }
        if (!empty($woo_urls['cart'])) {
            $case_conditions[] = "WHEN url = '{$woo_urls['cart']}' THEN 'Warenkorb'";
        }
        if (!empty($woo_urls['checkout'])) {
            $case_conditions[] = "WHEN url = '{$woo_urls['checkout']}' THEN 'Kasse'";
        }
        if (!empty($woo_urls['myaccount'])) {
            $case_conditions[] = "WHEN url = '{$woo_urls['myaccount']}' THEN 'Mein Konto'";
        }
        
        // Produktseiten - MEHRERE ERKENNUNGSMETHODEN
        $product_conditions = [];
        
        // 1. Direkte URL-Vergleiche
        if (!empty($product_urls)) {
            $product_urls_escaped = array_map(function($url) {
                return "'" . esc_sql($url) . "'";
            }, $product_urls);
            $product_conditions[] = "url IN (" . implode(',', $product_urls_escaped) . ")";
        }
        
        // 2. Produkt-Slugs in URLs erkennen
        if (!empty($product_slugs)) {
            foreach ($product_slugs as $slug) {
                if (!empty($slug)) {
                    $product_conditions[] = "url LIKE '%/{$slug}/%'";
                    $product_conditions[] = "url LIKE '%/{$slug}'";
                }
            }
        }
        
        // 3. Allgemeine Produkt-URL-Muster
        $product_conditions[] = "url LIKE '%/product/%'";
        $product_conditions[] = "url LIKE '%/produkt/%'";
        $product_conditions[] = "url LIKE '%/?product=%'";
        $product_conditions[] = "url LIKE '%/?add-to-cart=%'";
        
        if (!empty($product_conditions)) {
            $case_conditions[] = "WHEN " . implode(' OR ', $product_conditions) . " THEN 'Produktseiten'";
        }
        
        // Kategorieseiten
        if (!empty($category_slugs)) {
            $category_conditions = [];
            foreach ($category_slugs as $slug) {
                if (!empty($slug)) {
                    $category_conditions[] = "url LIKE '%/{$slug}/%'";
                    $category_conditions[] = "url LIKE '%/{$slug}'";
                }
            }
            if (!empty($category_conditions)) {
                $case_conditions[] = "WHEN " . implode(' OR ', $category_conditions) . " THEN 'Produktkategorien'";
            }
        }
        
        // Blog-Seiten dynamisch erkennen
        $blog_page_id = get_option('page_for_posts');
        if ($blog_page_id) {
            $blog_url = get_permalink($blog_page_id);
            $case_conditions[] = "WHEN url = '{$blog_url}' THEN 'Blog'";
        }
        
        // Blog-Beiträge (Posts) erkennen
        $case_conditions[] = "WHEN url LIKE '%/?p=%' OR url LIKE '%/blog/%' OR url LIKE '%/".date('Y')."/%' THEN 'Blog-Beiträge'";
        
        // Allgemeine WordPress Seiten erkennen
        $general_pages = $this->get_general_pages();
        if (!empty($general_pages)) {
            $page_urls_escaped = array_map(function($url) {
                return "'" . esc_sql($url) . "'";
            }, $general_pages);
            $case_conditions[] = "WHEN url IN (" . implode(',', $page_urls_escaped) . ") THEN 'Allgemeine Seiten'";
        }
        
        // Fallback für alle anderen Seiten
        $case_conditions[] = "ELSE 'Andere Seiten'";
        
        $case_sql = implode("\n                    ", $case_conditions);
        
        $where_device = '';
        $params = [$start_date, $end_date, $start_date, $end_date];
        
        if ($device_type !== null) {
            $where_device = ' AND device_type = %s';
            $params[] = $device_type;
        }
        
        $sql = $this->wpdb->prepare(
            "SELECT 
                CASE 
                    {$case_sql}
                END as page_category,
                COUNT(*) as pageviews,
                COUNT(DISTINCT session_id) as unique_sessions,
                AVG(time_on_page) as avg_time_seconds,
                ROUND(AVG(time_on_page)/60, 1) as avg_time_minutes,
                ROUND((SUM(CASE WHEN visit_time = (
                    SELECT MAX(visit_time) 
                    FROM {$this->table_logs} as sub 
                    WHERE sub.session_id = {$this->table_logs}.session_id
                    AND DATE(sub.visit_time) BETWEEN %s AND %s
                ) THEN 1 ELSE 0 END) / COUNT(*)) * 100, 1) as exit_rate
            FROM {$this->table_logs} 
            WHERE DATE(visit_time) BETWEEN %s AND %s
            AND time_on_page > 0
            {$where_device}
            GROUP BY page_category
            ORDER BY pageviews DESC",
            ...$params
        );
        
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    // Neue Methode: Allgemeine WordPress Seiten holen
    private function get_general_pages() {
        $pages = $this->wpdb->get_col(
            "SELECT guid 
            FROM {$this->wpdb->posts} 
            WHERE post_type = 'page' 
            AND post_status = 'publish'
            AND post_name NOT IN ('shop', 'cart', 'checkout', 'my-account', 'blog')"
        );
        
        // Entferne bereits kategorisierte Seiten
        $woo_urls = $this->get_woocommerce_page_urls();
        $blog_page_id = get_option('page_for_posts');
        $blog_url = $blog_page_id ? get_permalink($blog_page_id) : '';
        
        $filtered_pages = [];
        foreach ($pages as $page_url) {
            // Überspringe WooCommerce Seiten
            if (in_array($page_url, $woo_urls)) {
                continue;
            }
            // Überspringe Blog-Seite
            if ($page_url === $blog_url) {
                continue;
            }
            // Überspringe Startseite
            if ($page_url === $this->get_home_url()) {
                continue;
            }
            $filtered_pages[] = $page_url;
        }
        
        return $filtered_pages;
    }

    // Erweiterte Methode für spezifische Seiten-Typen
    private function get_specific_page_types() {
        $specific_pages = [];
        
        // Kontakt-Seite erkennen
        $contact_pages = $this->wpdb->get_col(
            "SELECT guid FROM {$this->wpdb->posts} 
            WHERE post_type = 'page' 
            AND post_status = 'publish'
            AND (post_title LIKE '%Kontakt%' OR post_name LIKE '%kontakt%')"
        );
        if (!empty($contact_pages)) {
            $specific_pages['Kontakt'] = $contact_pages;
        }
        
        // Impressum/Datenschutz erkennen
        $legal_pages = $this->wpdb->get_col(
            "SELECT guid FROM {$this->wpdb->posts} 
            WHERE post_type = 'page' 
            AND post_status = 'publish'
            AND (post_title LIKE '%Impressum%' OR post_title LIKE '%Datenschutz%' OR post_title LIKE '%AGB%' 
                OR post_name LIKE '%impressum%' OR post_name LIKE '%datenschutz%' OR post_name LIKE '%agb%')"
        );
        if (!empty($legal_pages)) {
            $specific_pages['Rechtliches'] = $legal_pages;
        }
        
        // Über uns/Leistungen erkennen
        $about_pages = $this->wpdb->get_col(
            "SELECT guid FROM {$this->wpdb->posts} 
            WHERE post_type = 'page' 
            AND post_status = 'publish'
            AND (post_title LIKE '%Über uns%' OR post_title LIKE '%Leistungen%' OR post_title LIKE '%Service%'
                OR post_name LIKE '%ueber-uns%' OR post_name LIKE '%leistungen%' OR post_name LIKE '%service%')"
        );
        if (!empty($about_pages)) {
            $specific_pages['Über uns/Leistungen'] = $about_pages;
        }
        
        return $specific_pages;
    }

    // Produktseiten Performance (DYNAMISCH)
    public function product_pages_performance($start_date, $end_date, $device_type = null) {
        $product_urls = $this->get_product_urls();
        
        if (empty($product_urls)) {
            return [];
        }
        
        $placeholders = implode(',', array_fill(0, count($product_urls), '%s'));
        
        $where_device = '';
        $params = [$start_date, $end_date, $start_date, $end_date];
        
        if ($device_type !== null) {
            $where_device = ' AND device_type = %s';
            $params[] = $device_type;
        }
        
        $params = array_merge($params, $product_urls);
        
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT 
                url,
                page_title,
                COUNT(*) as pageviews,
                COUNT(DISTINCT session_id) as unique_visitors,
                AVG(time_on_page) as avg_time_seconds,
                ROUND(AVG(time_on_page)/60, 1) as avg_time_minutes,
                AVG(page_load_time) as avg_load_time_ms,
                ROUND((SUM(CASE WHEN visit_time = (
                    SELECT MAX(visit_time) 
                    FROM {$this->table_logs} as sub 
                    WHERE sub.session_id = {$this->table_logs}.session_id
                    AND DATE(sub.visit_time) BETWEEN %s AND %s
                ) THEN 1 ELSE 0 END) / COUNT(*)) * 100, 1) as exit_rate,
                ROUND((COUNT(*) * 100.0 / SUM(COUNT(*)) OVER()), 1) as traffic_share
            FROM {$this->table_logs} 
            WHERE DATE(visit_time) BETWEEN %s AND %s
            AND url IN ({$placeholders})
            {$where_device}
            GROUP BY url, page_title
            HAVING pageviews > 1
            ORDER BY pageviews DESC 
            LIMIT 20",
            ...$params
        ), ARRAY_A);
    }

    // E-Commerce Funnel Analyse (DYNAMISCH)
    public function ecommerce_funnel_analysis($start_date, $end_date, $device_type = null) {
        $woo_urls = $this->get_woocommerce_page_urls();
        $product_urls = $this->get_product_urls();
        
        // Baue die Produkt-URL-Bedingung dynamisch auf
        $product_condition = "0";
        if (!empty($product_urls)) {
            $product_placeholders = implode(',', array_fill(0, count($product_urls), '%s'));
            $product_condition = "url IN ({$product_placeholders})";
        }
        
        $where_device = '';
        $params = [];
        
        if (!empty($product_urls)) {
            $params = array_merge($params, $product_urls);
        }
        
        $params = array_merge($params, [
            $woo_urls['shop'] ?? '',
            $woo_urls['cart'] ?? '',
            $woo_urls['checkout'] ?? '',
            $start_date, $end_date
        ]);
        
        if ($device_type !== null) {
            $where_device = ' AND device_type = %s';
            $params[] = $device_type;
        }
        
        $sql = "SELECT 
                COUNT(DISTINCT session_id) as total_sessions,
                SUM(CASE WHEN {$product_condition} OR url = %s THEN 1 ELSE 0 END) as product_views,
                SUM(CASE WHEN url = %s THEN 1 ELSE 0 END) as cart_views,
                SUM(CASE WHEN url = %s THEN 1 ELSE 0 END) as checkout_views,
                SUM(CASE WHEN url LIKE '%/order-received/%' OR url LIKE '%/bestellbestaetigung/%' THEN 1 ELSE 0 END) as order_confirmations
            FROM {$this->table_logs} 
            WHERE DATE(visit_time) BETWEEN %s AND %s
            {$where_device}";
        
        return $this->wpdb->get_results($this->wpdb->prepare($sql, ...$params), ARRAY_A);
    }

    // Kategorieseiten Performance (DYNAMISCH)
    public function category_pages_performance($start_date, $end_date, $device_type = null) 
    {
        $category_slugs = $this->get_category_urls();
        
        if (empty($category_slugs)) {
            return [];
        }
        
        $category_conditions = [];
        foreach ($category_slugs as $slug) {
            $category_conditions[] = "url LIKE '%/" . esc_sql($slug) . "/%'";
        }
        
        $where_condition = "(" . implode(' OR ', $category_conditions) . ")";
        
        $where_device = '';
        $params = [$start_date, $end_date, $start_date, $end_date];
        
        if ($device_type !== null) {
            $where_device = ' AND device_type = %s';
            $params[] = $device_type;
        }
        
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT 
                url,
                page_title,
                COUNT(*) as pageviews,
                COUNT(DISTINCT session_id) as unique_visitors,
                AVG(time_on_page) as avg_time_seconds,
                ROUND(AVG(time_on_page)/60, 1) as avg_time_minutes,
                ROUND((SUM(CASE WHEN visit_time = (
                    SELECT MAX(visit_time) 
                    FROM {$this->table_logs} as sub 
                    WHERE sub.session_id = {$this->table_logs}.session_id
                    AND DATE(sub.visit_time) BETWEEN %s AND %s
                ) THEN 1 ELSE 0 END) / COUNT(*)) * 100, 1) as exit_rate
            FROM {$this->table_logs} 
            WHERE DATE(visit_time) BETWEEN %s AND %s
            AND {$where_condition}
            {$where_device}
            GROUP BY url, page_title
            HAVING pageviews > 1
            ORDER BY pageviews DESC 
            LIMIT 15",
            ...$params
        ), ARRAY_A);
    }

    // Detaillierte WooCommerce Seiten-Performance
    public function woo_commerce_pages_performance($start_date, $end_date, $device_type = null) {
        $woo_urls = $this->get_woocommerce_page_urls();
        
        // Basis-URLs für die Abfrage vorbereiten
        $placeholders = array_fill(0, count($woo_urls), '%s');
        $url_list = array_values($woo_urls);
        
        // Alle Parameter in der richtigen Reihenfolge sammeln
        $params = array_merge(
            $url_list, // Für die CASE WHEN URLs
            [$start_date, $end_date], // Für die subquery
            [$start_date, $end_date], // Für die Hauptquery
            $url_list  // Für die IN clause
        );
        
        $where_device = '';
        if ($device_type !== null) {
            $where_device = ' AND device_type = %s';
            $params[] = $device_type;
        }
        
        $sql = $this->wpdb->prepare(
            "SELECT 
                CASE 
                    WHEN url = %s THEN 'WooCommerce Shop'
                    WHEN url = %s THEN 'Warenkorb'
                    WHEN url = %s THEN 'Kasse'
                    WHEN url = %s THEN 'Mein Konto'
                    WHEN url = %s THEN 'AGB/Impressum'
                    ELSE 'Andere WooCommerce Seiten'
                END as page_type,
                url,
                page_title,
                COUNT(*) as pageviews,
                COUNT(DISTINCT session_id) as unique_visitors,
                AVG(time_on_page) as avg_time_seconds,
                ROUND(AVG(time_on_page)/60, 1) as avg_time_minutes,
                AVG(page_load_time) as avg_load_time_ms,
                ROUND((SUM(CASE WHEN visit_time = (
                    SELECT MAX(visit_time) 
                    FROM {$this->table_logs} as sub 
                    WHERE sub.session_id = {$this->table_logs}.session_id
                    AND DATE(sub.visit_time) BETWEEN %s AND %s
                ) THEN 1 ELSE 0 END) / COUNT(*)) * 100, 1) as exit_rate,
                ROUND((COUNT(*) * 100.0 / SUM(COUNT(*)) OVER()), 1) as traffic_share
            FROM {$this->table_logs} 
            WHERE DATE(visit_time) BETWEEN %s AND %s
            AND url IN (" . implode(',', $placeholders) . ")
            {$where_device}
            GROUP BY page_type, url, page_title
            ORDER BY pageviews DESC",
            ...$params
        );
        
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    // Detaillierte Seiten-Performance
    public function detailed_page_performance($start_date, $end_date, $limit = 15, $device_type = null) {
        $where_device = '';
        $params = [$start_date, $end_date, $start_date, $end_date, $limit];
        
        if ($device_type !== null) {
            $where_device = ' AND device_type = %s';
            array_splice($params, 4, 0, [$device_type]); // Device vor Limit einfügen
        }
        
        $sql = "SELECT 
                url,
                page_title,
                COUNT(*) as pageviews,
                COUNT(DISTINCT session_id) as unique_visitors,
                AVG(time_on_page) as avg_time_seconds,
                ROUND(AVG(time_on_page)/60, 1) as avg_time_minutes,
                AVG(page_load_time) as avg_load_time_ms,
                ROUND((SUM(CASE WHEN visit_time = (
                    SELECT MAX(visit_time) 
                    FROM {$this->table_logs} as sub 
                    WHERE sub.session_id = {$this->table_logs}.session_id
                    AND DATE(sub.visit_time) BETWEEN %s AND %s
                ) THEN 1 ELSE 0 END) / COUNT(*)) * 100, 1) as exit_rate,
                ROUND((COUNT(*) * 100.0 / SUM(COUNT(*)) OVER()), 1) as traffic_share
            FROM {$this->table_logs} 
            WHERE DATE(visit_time) BETWEEN %s AND %s
            {$where_device}
            GROUP BY url, page_title
            HAVING pageviews > 1
            ORDER BY pageviews DESC 
            LIMIT %d";
            
        $results = $this->wpdb->get_results($this->wpdb->prepare($sql, ...$params), ARRAY_A);
        return $this->enhance_page_titles($results);
    }

    // Seitenfluss-Analyse für Top-Seiten
    public function page_flow_analysis($start_date, $end_date, $limit = 10, $device_type = null) {
        $where_device = '';
        $params = [$start_date, $end_date, $limit];
        
        if ($device_type !== null) {
            $where_device = ' AND t1.device_type = %s';
            array_splice($params, 1, 0, [$device_type]); // Device vor Limit einfügen
        }
        
        $sql = "SELECT 
                t1.url as current_page,
                COALESCE(t2.url, 'Ausstieg') as next_page,
                COUNT(*) as transitions
            FROM {$this->table_logs} t1
            LEFT JOIN {$this->table_logs} t2 ON t1.session_id = t2.session_id 
                AND t2.visit_time = (
                    SELECT MIN(visit_time) 
                    FROM {$this->table_logs} 
                    WHERE session_id = t1.session_id 
                    AND visit_time > t1.visit_time
                )
            WHERE DATE(t1.visit_time) BETWEEN %s AND %s
            {$where_device}
            GROUP BY t1.url, COALESCE(t2.url, 'Ausstieg')
            ORDER BY transitions DESC 
            LIMIT %d";
            
        return $this->wpdb->get_results($this->wpdb->prepare($sql, ...$params), ARRAY_A);
    }

    // Engagement-Metriken nach Seite
    public function page_engagement_metrics($start_date, $end_date, $device_type = null) 
    {
        $where_device = '';
        $params = [$start_date, $end_date];
        
        if ($device_type !== null) {
            $where_device = ' AND device_type = %s';
            $params[] = $device_type;
        }
        
        $results = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT 
                url,
                page_title,
                COUNT(*) as total_views,
                SUM(CASE WHEN time_on_page > 30 THEN 1 ELSE 0 END) as engaged_views,
                ROUND((SUM(CASE WHEN time_on_page > 30 THEN 1 ELSE 0 END) / COUNT(*)) * 100, 1) as engagement_rate,
                AVG(time_on_page) as avg_time_seconds,
                MAX(time_on_page) as max_time_seconds,
                COUNT(DISTINCT session_id) as unique_visitors
            FROM {$this->table_logs} 
            WHERE DATE(visit_time) BETWEEN %s AND %s
            AND time_on_page > 0
            {$where_device}
            GROUP BY url, page_title
            HAVING total_views > 5
            ORDER BY engagement_rate DESC 
            LIMIT 15",
            ...$params
        ), ARRAY_A);
        return $this->enhance_page_titles($results);
    }

    // Chart-Daten: Seiten-Performance
    private function get_page_performance_chart_data($start_date, $end_date, $device_type = null) {
        $categories = $this->page_performance_by_category($start_date, $end_date, $device_type);
        $detailed = $this->detailed_page_performance($start_date, $end_date, 8, $device_type);
        
        return [
            'categories' => [
                'labels' => array_column($categories, 'page_category'),
                'pageviews' => array_column($categories, 'pageviews'),
                'avg_times' => array_column($categories, 'avg_time_seconds'),
                'exit_rates' => array_column($categories, 'exit_rate')
            ],
            'top_pages' => [
                'labels' => array_map(function($page) {
                    return $page['page_title'] ?: basename($page['url']);
                }, $detailed),
                'pageviews' => array_column($detailed, 'pageviews'),
                'avg_times' => array_column($detailed, 'avg_time_seconds'),
                'exit_rates' => array_column($detailed, 'exit_rate'),
                'load_times' => array_map(function($time) {
                    return round($time / 1000, 1);
                }, array_column($detailed, 'avg_load_time_ms'))
            ]
        ];
    }

    // Chart-Daten: Engagement
    private function get_engagement_chart_data($start_date, $end_date, $device_type = null) {
        $engagement = $this->page_engagement_metrics($start_date, $end_date, $device_type);
        
        return [
            'labels' => array_map(function($page) {
                return $page['page_title'] ?: basename($page['url']);
            }, $engagement),
            'total_views' => array_column($engagement, 'total_views'),
            'engaged_views' => array_column($engagement, 'engaged_views'),
            'engagement_rates' => array_column($engagement, 'engagement_rate'),
            'avg_times' => array_column($engagement, 'avg_time_seconds')
        ];
    }

    // Chart-Daten: Traffic-Flow
    private function get_traffic_flow_chart_data($start_date, $end_date, $device_type = null) {
        $entry_pages = $this->entry_pages_by_period($start_date, $end_date, 8, $device_type);
        $exit_pages = $this->exit_pages_by_period($start_date, $end_date, 8, $device_type);
        
        return [
            'entry_pages' => [
                'labels' => array_map(function($page) {
                    return $page['page_title'] ?: basename($page['url']);
                }, $entry_pages),
                'entries' => array_column($entry_pages, 'entries'),
                'percentages' => array_column($entry_pages, 'percentage')
            ],
            'exit_pages' => [
                'labels' => array_map(function($page) {
                    return $page['page_title'] ?: basename($page['url']);
                }, $exit_pages),
                'exits' => array_column($exit_pages, 'exits'),
                'percentages' => array_column($exit_pages, 'percentage')
            ]
        ];
    }

    // Chart-Daten: Exit-Rates
    private function get_exit_rates_chart_data($start_date, $end_date, $device_type = null) {
        $exit_rates = $this->exit_rates_by_period($start_date, $end_date, 10, $device_type);
        
        return [
            'labels' => array_map(function($page) {
                return $page['page_title'] ?: basename($page['url']);
            }, $exit_rates),
            'exit_rates' => array_column($exit_rates, 'exit_rate'),
            'total_views' => array_column($exit_rates, 'total_views'),
            'exit_views' => array_column($exit_rates, 'exit_views')
        ];
    }

    // Chart-Daten: Seiten-Vergleich
    private function get_page_comparison_chart_data($start_date, $end_date, $device_type = null) {
        $pages = $this->get_pages_by_period($start_date, $end_date, $device_type);
        $detailed = $this->detailed_page_performance($start_date, $end_date, 12, $device_type);
        
        return [
            'traffic' => [
                'labels' => array_map(function($page) {
                    return $page['page_title'] ?: basename($page['url']);
                }, $pages),
                'pageviews' => array_column($pages, 'count'),
                'percentages' => array_column($pages, 'percentage')
            ],
            'performance' => [
                'labels' => array_map(function($page) {
                    return $page['page_title'] ?: basename($page['url']);
                }, $detailed),
                'avg_times' => array_column($detailed, 'avg_time_seconds'),
                'exit_rates' => array_column($detailed, 'exit_rate'),
                'load_times' => array_map(function($time) {
                    return round($time / 1000, 1);
                }, array_column($detailed, 'avg_load_time_ms'))
            ]
        ];
    }

    // Chart-Daten: E-Commerce Funnel
    private function get_ecommerce_funnel_chart_data($start_date, $end_date, $device_type = null) {
        $funnel = $this->ecommerce_funnel_analysis($start_date, $end_date, $device_type);
        
        if (!empty($funnel)) {
            $funnel_data = $funnel[0];
            $product_views = $funnel_data['product_views'] ?: 1; // Vermeide Division durch Null
            
            return [
                'labels' => ['Produktseiten', 'Warenkorb', 'Kasse', 'Bestellbestätigung'],
                'values' => [
                    $funnel_data['product_views'],
                    $funnel_data['cart_views'],
                    $funnel_data['checkout_views'],
                    $funnel_data['order_confirmations']
                ],
                'rates' => [
                    100,
                    round(($funnel_data['cart_views'] / $product_views) * 100, 1),
                    round(($funnel_data['checkout_views'] / $product_views) * 100, 1),
                    round(($funnel_data['order_confirmations'] / $product_views) * 100, 1)
                ]
            ];
        }
        
        return [];
    }

    private function get_pages_by_period($start_date, $end_date, $device_type = null) {
        $where_device = '';
        $params = [$start_date, $end_date];
        
        if ($device_type !== null) {
            $where_device = ' AND device_type = %s';
            $params[] = $device_type;
        }
        
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT url, page_title, COUNT(*) as count,
                    ROUND((COUNT(*) * 100.0 / SUM(COUNT(*)) OVER()), 1) as percentage
            FROM {$this->table_logs} 
            WHERE DATE(visit_time) BETWEEN %s AND %s 
            {$where_device}
            GROUP BY url, page_title 
            ORDER BY count DESC 
            LIMIT 20",
            ...$params
        ), ARRAY_A);
    }
    
    private function get_keywords_by_period($start_date, $end_date, $device_type = null) {
        $where_device = '';
        $params = [$start_date, $end_date];
        
        if ($device_type !== null) {
            $where_device = ' AND device_type = %s';
            $params[] = $device_type;
        }
        
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT keywords, COUNT(*) as count,
                    ROUND((COUNT(*) * 100.0 / SUM(COUNT(*)) OVER()), 1) as percentage
            FROM (
                SELECT session_id, keywords
                FROM {$this->table_logs}
                WHERE keywords != '' AND DATE(visit_time) BETWEEN %s AND %s
                {$where_device}
                GROUP BY session_id, keywords
            ) as sessions
            GROUP BY keywords 
            ORDER BY count DESC 
            LIMIT 20",
            ...$params
        ), ARRAY_A);
    }

    // Universelle Methode um richtigen Seiten-Titel zu holen
    private function get_proper_page_title($url, $current_title = '') 
    {        
        $post_id = url_to_postid($url);
        
        if ($post_id) {
            $proper_title = get_the_title($post_id);
            if (!empty($proper_title)) {
                return $proper_title;
            }
        }
        
        if (empty($current_title)) {
            $parsed_url = parse_url($url);
            $path = $parsed_url['path'] ?? '';
            if ($path) {
                $path_parts = explode('/', trim($path, '/'));
                $last_part = end($path_parts);
                if (!empty($last_part)) {
                    return ucfirst(str_replace(['-', '_'], ' ', $last_part));
                }
            }
            return 'Ohne Titel';
        }
        
        return $current_title;
    }

    private function enhance_page_titles($pages_data) 
    {
        foreach ($pages_data as &$page) {
            $page['page_title'] = $this->get_proper_page_title(
                $page['url'], 
                $page['page_title'] ?? ''
            );
        }
        return $pages_data;
    }


public function entry_pages_by_period($start_date, $end_date, $limit = 20, $device_type = null) {
        $where_device = '';
        $params = [$start_date, $end_date, $start_date, $end_date, $limit];
        
        if ($device_type !== null) {
            $where_device = ' AND device_type = %s';
            array_splice($params, 4, 0, [$device_type]); // Device vor Limit einfügen
        }
        
        $sql = "SELECT url, page_title, COUNT(*) as entries,
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
                {$where_device}
            ) as entry_pages 
            GROUP BY url, page_title 
            ORDER BY entries DESC 
            LIMIT %d";
            
        $results = $this->wpdb->get_results($this->wpdb->prepare($sql, ...$params), ARRAY_A);
        return $this->enhance_page_titles($results);
    }

    public function exit_pages_by_period($start_date, $end_date, $limit = 20, $device_type = null) {
        $where_device = '';
        $params = [$start_date, $end_date, $start_date, $end_date, $limit];
        
        if ($device_type !== null) {
            $where_device = ' AND device_type = %s';
            array_splice($params, 4, 0, [$device_type]); // Device vor Limit einfügen
        }
        
        $sql = "SELECT url, page_title, COUNT(*) as exits,
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
                {$where_device}
            ) as exit_pages 
            GROUP BY url, page_title 
            ORDER BY exits DESC 
            LIMIT %d";
            
        $results = $this->wpdb->get_results($this->wpdb->prepare($sql, ...$params), ARRAY_A);
        return $this->enhance_page_titles($results);
    }
    
    public function exit_rates_by_period($start_date, $end_date, $limit = 20, $device_type = null) {
        $where_device = '';
        $params = [$start_date, $end_date, $start_date, $end_date, $limit];
        
        if ($device_type !== null) {
            $where_device = ' AND device_type = %s';
            array_splice($params, 4, 0, [$device_type]); // Device vor Limit einfügen
        }
        
        $sql = "SELECT url, page_title,
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
                {$where_device}
             ) as page_analysis
             GROUP BY url, page_title
             HAVING total_views > 2
             ORDER BY exit_rate DESC 
             LIMIT %d";
             
        $results = $this->wpdb->get_results($this->wpdb->prepare($sql, ...$params), ARRAY_A);
        return $this->enhance_page_titles($results);
    }
}