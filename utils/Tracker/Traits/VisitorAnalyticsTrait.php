<?php

namespace Utils\Tracker\Traits;

trait VisitorAnalyticsTrait 
{
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
}