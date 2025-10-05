<?php

namespace Utils\Tracker\Traits;

trait BasicAnalyticsTrait 
{
    private function query_count($where_sql = '', $params = [], $min_requests = 1) {
        $sql = "
            SELECT COUNT(*) FROM (
                SELECT session_id, COUNT(*) as requests
                FROM {$this->table_logs}
                WHERE 1=1 $where_sql
                GROUP BY session_id
                HAVING requests >= %d
            ) t
        ";
        $params[] = $min_requests;
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

    public function visitors_last_month() {
        $last_month = date('Y-m', strtotime('-1 month', current_time('timestamp')));
        return $this->query_count(' AND DATE_FORMAT(visit_time, "%Y-%m") = %s', [$last_month]);
    }

    public function visitors_this_year() {
        $year = date('Y', current_time('timestamp'));
        return $this->query_count(' AND YEAR(visit_time) = %s', [$year]);
    }

    public function visitors_by_period($start_date, $end_date) {
        return $this->query_count(' AND DATE(visit_time) BETWEEN %s AND %s', [$start_date, $end_date]);
    }


    // --- Session KPIs ---
    private function get_avg_session_duration_by_period($start_date, $end_date) {
        // Diese Methode ist bereits korrekt (arbeitet auf Session-Ebene)
        $sql = $this->wpdb->prepare(
            "SELECT AVG(session_duration) FROM (
                SELECT session_id, TIMESTAMPDIFF(SECOND, MIN(visit_time), MAX(visit_time)) as session_duration
                FROM {$this->table_logs}
                WHERE DATE(visit_time) BETWEEN %s AND %s
                GROUP BY session_id
                HAVING COUNT(*) > 1
            ) as sessions",
            $start_date, $end_date
        );
        return (int) $this->wpdb->get_var($sql);
    }

    private function get_avg_pages_per_session_by_period($start_date, $end_date) {
        // Diese Methode ist bereits korrekt (arbeitet auf Session-Ebene)
        $sql = $this->wpdb->prepare(
            "SELECT AVG(page_count) FROM (
                SELECT session_id, COUNT(*) as page_count
                FROM {$this->table_logs}
                WHERE DATE(visit_time) BETWEEN %s AND %s
                GROUP BY session_id
            ) as sessions",
            $start_date, $end_date
        );
        return round($this->wpdb->get_var($sql), 1);
    }

    private function get_bounce_rate_by_period($start_date, $end_date) {
        // Diese Methode ist bereits korrekt (arbeitet auf Session-Ebene)
        $total = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(DISTINCT session_id) FROM {$this->table_logs} WHERE DATE(visit_time) BETWEEN %s AND %s",
            $start_date, $end_date
        ));

        $bounced = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM (
                SELECT session_id FROM {$this->table_logs} 
                WHERE DATE(visit_time) BETWEEN %s AND %s
                GROUP BY session_id 
                HAVING COUNT(*) = 1
            ) as single_page",
            $start_date, $end_date
        ));

        return $total > 0 ? round(($bounced / $total) * 100, 1) : 0;
    }

    private function get_avg_time_on_page_by_period($start_date, $end_date) {
        $sql = $this->wpdb->prepare(
            "SELECT AVG(time_on_page) FROM {$this->table_logs} 
             WHERE time_on_page > 0 AND DATE(visit_time) BETWEEN %s AND %s",
            $start_date, $end_date
        );
        return round($this->wpdb->get_var($sql), 1);
    }
}