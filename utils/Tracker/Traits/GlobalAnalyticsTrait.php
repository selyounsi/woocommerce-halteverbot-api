<?php

namespace Utils\Tracker\Traits;

trait GlobalAnalyticsTrait 
{
    /**
     * Globale Statistiken (ohne Zeitraum-Filter)
     */
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
}