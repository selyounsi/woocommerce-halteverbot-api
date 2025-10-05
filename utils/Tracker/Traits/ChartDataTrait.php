<?php

namespace Utils\Tracker\Traits;

trait ChartDataTrait {
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
}