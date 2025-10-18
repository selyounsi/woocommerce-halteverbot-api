<?php

namespace Utils\Tracker\Traits;

trait DeviceAnalyticsTrait 
{
    private function get_devices_by_period($start_date, $end_date, $device_type = null) {
        $where_device = '';
        $params = [$start_date, $end_date];
        
        if ($device_type !== null) {
            $where_device = ' AND device_type = %s';
            $params[] = $device_type;
        }
        
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
                WHERE DATE(visit_time) BETWEEN %s AND %s $where_device
                GROUP BY session_id, device_type, device_brand, device_model
            ) as sessions
            GROUP BY device_type, device_brand, device_model 
            ORDER BY device_type, count DESC",
            $params
        ), ARRAY_A);
    }

    private function get_device_types_by_period($start_date, $end_date, $device_type = null) {
        $where_device = '';
        $params = [$start_date, $end_date];
        
        if ($device_type !== null) {
            $where_device = ' AND device_type = %s';
            $params[] = $device_type;
        }
        
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT device_type, COUNT(*) as count,
                    ROUND((COUNT(*) * 100.0 / SUM(COUNT(*)) OVER()), 1) as percentage
            FROM (
                SELECT session_id, device_type
                FROM {$this->table_logs}
                WHERE DATE(visit_time) BETWEEN %s AND %s $where_device
                GROUP BY session_id, device_type
            ) as sessions
            GROUP BY device_type 
            ORDER BY count DESC",
            $params
        ), ARRAY_A);
    }

    private function get_device_brands_by_period($start_date, $end_date, $device_type = null) {
        $where_device = '';
        $params = [$start_date, $end_date];
        
        if ($device_type !== null) {
            $where_device = ' AND device_type = %s';
            $params[] = $device_type;
        }
        
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT 
                COALESCE(NULLIF(device_brand, ''), 'Unknown') as brand,
                COUNT(*) as count,
                ROUND((COUNT(*) * 100.0 / SUM(COUNT(*)) OVER()), 1) as percentage
            FROM (
                SELECT session_id, device_brand
                FROM {$this->table_logs}
                WHERE DATE(visit_time) BETWEEN %s AND %s $where_device
                GROUP BY session_id, device_brand
            ) as sessions
            GROUP BY brand 
            ORDER BY count DESC
            LIMIT 10",
            $params
        ), ARRAY_A);
    }

    private function get_browsers_by_period($start_date, $end_date, $device_type = null) {
        $where_device = '';
        $params = [$start_date, $end_date];
        
        if ($device_type !== null) {
            $where_device = ' AND device_type = %s';
            $params[] = $device_type;
        }
        
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT browser_name, browser_version, platform, COUNT(*) as count,
                    ROUND((COUNT(*) * 100.0 / SUM(COUNT(*)) OVER()), 1) as percentage
            FROM (
                SELECT session_id, browser_name, browser_version, platform
                FROM {$this->table_logs}
                WHERE DATE(visit_time) BETWEEN %s AND %s $where_device
                GROUP BY session_id, browser_name, browser_version, platform
            ) as sessions
            GROUP BY browser_name
            ORDER BY count DESC",
            $params
        ), ARRAY_A);
    }

    private function get_operating_systems_by_period($start_date, $end_date, $device_type = null) {
        $where_device = '';
        $params = [$start_date, $end_date];
        
        if ($device_type !== null) {
            $where_device = ' AND device_type = %s';
            $params[] = $device_type;
        }
        
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT platform, COUNT(*) as count,
                    ROUND((COUNT(*) * 100.0 / SUM(COUNT(*)) OVER()), 1) as percentage
            FROM (
                SELECT session_id, platform
                FROM {$this->table_logs}
                WHERE DATE(visit_time) BETWEEN %s AND %s $where_device
                GROUP BY session_id, platform
            ) as sessions
            GROUP BY platform 
            ORDER BY count DESC",
            $params
        ), ARRAY_A);
    }
    
    private function get_screen_resolutions_by_period($start_date, $end_date, $device_type = null) {
        $where_device = '';
        $params = [$start_date, $end_date];
        
        if ($device_type !== null) {
            $where_device = ' AND device_type = %s';
            $params[] = $device_type;
        }
        
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT screen_resolution, COUNT(*) as count,
                    ROUND((COUNT(*) * 100.0 / SUM(COUNT(*)) OVER()), 1) as percentage
            FROM (
                SELECT session_id, screen_resolution
                FROM {$this->table_logs}
                WHERE screen_resolution != '' AND DATE(visit_time) BETWEEN %s AND %s $where_device
                GROUP BY session_id, screen_resolution
            ) as sessions
            GROUP BY screen_resolution 
            ORDER BY count DESC
            LIMIT 15",
            $params
        ), ARRAY_A);
    }

    private function get_languages_by_period($start_date, $end_date, $device_type = null) {
        $where_device = '';
        $params = [$start_date, $end_date];
        
        if ($device_type !== null) {
            $where_device = ' AND device_type = %s';
            $params[] = $device_type;
        }
        
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
                WHERE language != '' AND DATE(visit_time) BETWEEN %s AND %s $where_device
                GROUP BY session_id, language
            ) as sessions
            GROUP BY language_clean 
            ORDER BY count DESC",
            $params
        ), ARRAY_A);
    }
}