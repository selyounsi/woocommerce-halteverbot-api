<?php

namespace Utils\Tracker\Traits;

trait BasicAnalyticsTrait 
{
    private function query_count($where_sql = '', $params = [], $min_requests = 1, $device_type = null) {
        $sql = "
            SELECT COUNT(*) FROM (
                SELECT session_id, COUNT(*) as requests
                FROM {$this->table_logs}
                WHERE 1=1 $where_sql
        ";
        
        // Device-Type Filter hinzufügen
        if ($device_type !== null) {
            $sql .= " AND device_type = %s";
            $params[] = $device_type;
        }
        
        $sql .= "
                GROUP BY session_id
                HAVING requests >= %d
            ) t
        ";
        $params[] = $min_requests;
        return (int) $this->wpdb->get_var($this->wpdb->prepare($sql, $params));
    }

    public function visitors_today($device_type = null) {
        $date = current_time('Y-m-d');
        return $this->query_count(' AND DATE(visit_time) = %s', [$date], 1, $device_type);
    }

    public function visitors_yesterday($device_type = null) {
        $date = date('Y-m-d', strtotime('-1 day', current_time('timestamp')));
        return $this->query_count(' AND DATE(visit_time) = %s', [$date], 1, $device_type);
    }

    public function visitors_this_week($device_type = null) {
        $start = date('Y-m-d', strtotime('monday this week', current_time('timestamp')));
        $end = date('Y-m-d', strtotime('sunday this week', current_time('timestamp')));
        return $this->query_count(' AND DATE(visit_time) BETWEEN %s AND %s', [$start, $end], 1, $device_type);
    }

    public function visitors_this_month($device_type = null) {
        $month = date('Y-m', current_time('timestamp'));
        return $this->query_count(' AND DATE_FORMAT(visit_time, "%Y-%m") = %s', [$month], 1, $device_type);
    }

    public function visitors_last_month($device_type = null) {
        $last_month = date('Y-m', strtotime('-1 month', current_time('timestamp')));
        return $this->query_count(' AND DATE_FORMAT(visit_time, "%Y-%m") = %s', [$last_month], 1, $device_type);
    }

    public function visitors_this_year($device_type = null) {
        $year = date('Y', current_time('timestamp'));
        return $this->query_count(' AND YEAR(visit_time) = %s', [$year], 1, $device_type);
    }

    public function visitors_by_period($start_date, $end_date, $device_type = null) {
        return $this->query_count(' AND DATE(visit_time) BETWEEN %s AND %s', [$start_date, $end_date], 1, $device_type);
    }

    // --- Session KPIs ---
    private function get_avg_session_duration_by_period($start_date, $end_date, $device_type = null) {
        $where_device = '';
        $params = [$start_date, $end_date];
        
        if ($device_type !== null) {
            $where_device = ' AND device_type = %s';
            $params[] = $device_type;
        }
        
        $sql = $this->wpdb->prepare(
            "SELECT AVG(session_duration) FROM (
                SELECT session_id, TIMESTAMPDIFF(SECOND, MIN(visit_time), MAX(visit_time)) as session_duration
                FROM {$this->table_logs}
                WHERE DATE(visit_time) BETWEEN %s AND %s $where_device
                GROUP BY session_id
                HAVING COUNT(*) > 1
            ) as sessions",
            $params
        );
        return (int) $this->wpdb->get_var($sql);
    }

    private function get_avg_pages_per_session_by_period($start_date, $end_date, $device_type = null) {
        $where_device = '';
        $params = [$start_date, $end_date];
        
        if ($device_type !== null) {
            $where_device = ' AND device_type = %s';
            $params[] = $device_type;
        }
        
        $sql = $this->wpdb->prepare(
            "SELECT AVG(page_count) FROM (
                SELECT session_id, COUNT(*) as page_count
                FROM {$this->table_logs}
                WHERE DATE(visit_time) BETWEEN %s AND %s $where_device
                GROUP BY session_id
            ) as sessions",
            $params
        );
        return round($this->wpdb->get_var($sql), 1);
    }

    private function get_bounce_rate_by_period($start_date, $end_date, $device_type = null) {
        $where_device = '';
        $params = [$start_date, $end_date];
        
        if ($device_type !== null) {
            $where_device = ' AND device_type = %s';
            $params[] = $device_type;
        }
        
        $total = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(DISTINCT session_id) FROM {$this->table_logs} WHERE DATE(visit_time) BETWEEN %s AND %s $where_device",
            $params
        ));

        $bounced_params = $params;
        $bounced = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM (
                SELECT session_id FROM {$this->table_logs} 
                WHERE DATE(visit_time) BETWEEN %s AND %s $where_device
                GROUP BY session_id 
                HAVING COUNT(*) = 1
            ) as single_page",
            $bounced_params
        ));

        return $total > 0 ? round(($bounced / $total) * 100, 1) : 0;
    }

    private function get_avg_time_on_page_by_period($start_date, $end_date, $device_type = null) {
        $where_device = '';
        $params = [$start_date, $end_date];
        
        if ($device_type !== null) {
            $where_device = ' AND device_type = %s';
            $params[] = $device_type;
        }
        
        $sql = $this->wpdb->prepare(
            "SELECT AVG(time_on_page) FROM {$this->table_logs} 
             WHERE time_on_page > 0 AND DATE(visit_time) BETWEEN %s AND %s $where_device",
            $params
        );
        return round($this->wpdb->get_var($sql), 1);
    }
}