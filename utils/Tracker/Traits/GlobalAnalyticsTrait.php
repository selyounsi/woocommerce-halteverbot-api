<?php

namespace Utils\Tracker\Traits;

trait GlobalAnalyticsTrait 
{
    /**
     * Globale Statistiken (ohne Zeitraum-Filter)
     */
    public function visitors_by_referrer($device_type = null) {
        $where_device = '';
        $params = [];
        
        if ($device_type !== null) {
            $where_device = ' WHERE device_type = %s';
            $params[] = $device_type;
        }
        
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
            $where_device
            GROUP BY source_channel
            ORDER BY count DESC";
            
        if ($device_type !== null) {
            return $this->wpdb->get_results($this->wpdb->prepare($sql, $params), ARRAY_A);
        }
        
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    public function visitors_by_device($device_type = null) {
        $where_device = '';
        $params = [];
        
        if ($device_type !== null) {
            $where_device = ' WHERE device_type = %s';
            $params[] = $device_type;
        }
        
        $sql = "SELECT device_type, device_brand, device_model, COUNT(*) as count 
                FROM {$this->table_logs} 
                $where_device
                GROUP BY device_type, device_brand, device_model 
                ORDER BY count DESC";
                
        if ($device_type !== null) {
            return $this->wpdb->get_results($this->wpdb->prepare($sql, $params), ARRAY_A);
        }
        
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    public function visitors_by_browser($device_type = null) {
        $where_device = '';
        $params = [];
        
        if ($device_type !== null) {
            $where_device = ' WHERE device_type = %s';
            $params[] = $device_type;
        }
        
        $sql = "SELECT browser_name, browser_version, platform, COUNT(*) as count 
                FROM {$this->table_logs} 
                $where_device
                GROUP BY browser_name, browser_version, platform 
                ORDER BY count DESC";
                
        if ($device_type !== null) {
            return $this->wpdb->get_results($this->wpdb->prepare($sql, $params), ARRAY_A);
        }
        
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    public function visitors_by_country($device_type = null) {
        $where_device = '';
        $params = [];
        
        if ($device_type !== null) {
            $where_device = " WHERE country_code != '' AND device_type = %s";
            $params[] = $device_type;
        } else {
            $where_device = " WHERE country_code != ''";
        }
        
        $sql = "SELECT country_code, country_name, COUNT(*) as count 
                FROM {$this->table_logs} 
                $where_device
                GROUP BY country_code, country_name 
                ORDER BY count DESC";
                
        if ($device_type !== null) {
            return $this->wpdb->get_results($this->wpdb->prepare($sql, $params), ARRAY_A);
        }
        
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    public function page_views($device_type = null) {
        $where_device = '';
        $params = [];
        
        if ($device_type !== null) {
            $where_device = ' WHERE device_type = %s';
            $params[] = $device_type;
        }
        
        $sql = "SELECT url, page_title, COUNT(*) as count 
                FROM {$this->table_logs} 
                $where_device
                GROUP BY url, page_title 
                ORDER BY count DESC 
                LIMIT 20";
                
        if ($device_type !== null) {
            return $this->wpdb->get_results($this->wpdb->prepare($sql, $params), ARRAY_A);
        }
        
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    public function popular_keywords($device_type = null) {
        $where_device = '';
        $params = [];
        
        if ($device_type !== null) {
            $where_device = " WHERE keywords != '' AND device_type = %s";
            $params[] = $device_type;
        } else {
            $where_device = " WHERE keywords != ''";
        }
        
        $sql = "SELECT keywords, COUNT(*) as count 
                FROM {$this->table_logs} 
                $where_device
                GROUP BY keywords 
                ORDER BY count DESC 
                LIMIT 20";
                
        if ($device_type !== null) {
            return $this->wpdb->get_results($this->wpdb->prepare($sql, $params), ARRAY_A);
        }
        
        return $this->wpdb->get_results($sql, ARRAY_A);
    }
}