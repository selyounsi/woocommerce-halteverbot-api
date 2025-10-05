<?php

namespace Utils\Tracker\Traits;

trait PageAnalyticsTrait {

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
}