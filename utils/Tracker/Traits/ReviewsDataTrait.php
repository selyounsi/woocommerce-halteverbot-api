<?php

namespace Utils\Tracker\Traits;

trait ReviewsDataTrait {
    private $table_reviews;

    public function init_reviews_table() {
        $this->table_reviews = $this->wpdb->prefix . 'wha_reviews';
    }

    /**
     * Grundlegende Bewertungs-Statistiken mit Zeitraum
     */
    public function get_reviews_stats($start_date = null, $end_date = null) {
        $this->init_reviews_table();
        
        $where_clause = '';
        $params = [];
        
        if ($start_date && $end_date) {
            $where_clause = ' WHERE DATE(created_at) BETWEEN %s AND %s';
            $params = [$start_date, $end_date];
        }
        
        $sql = $this->wpdb->prepare(
            "SELECT 
                COUNT(*) as total_reviews,
                AVG(rating) as avg_rating,
                COUNT(CASE WHEN rating = 5 THEN 1 END) as five_stars,
                COUNT(CASE WHEN rating = 4 THEN 1 END) as four_stars,
                COUNT(CASE WHEN rating = 3 THEN 1 END) as three_stars,
                COUNT(CASE WHEN rating = 2 THEN 1 END) as two_stars,
                COUNT(CASE WHEN rating = 1 THEN 1 END) as one_stars,
                COUNT(CASE WHEN is_shown = 1 THEN 1 END) as shown_reviews,
                COUNT(CASE WHEN LENGTH(TRIM(review_text)) > 10 THEN 1 END) as reviews_with_text
             FROM {$this->table_reviews}
             {$where_clause}",
            $params
        );
        
        return $this->wpdb->get_row($sql, ARRAY_A);
    }

    /**
     * Bewertungen nach Quelle mit Zeitraum (dynamisch)
     */
    public function get_reviews_by_source($start_date = null, $end_date = null) {
        $this->init_reviews_table();
        
        $where_clause = '';
        $params = [];
        
        if ($start_date && $end_date) {
            $where_clause = ' WHERE DATE(created_at) BETWEEN %s AND %s';
            $params = [$start_date, $end_date];
        }
        
        $sql = $this->wpdb->prepare(
            "SELECT 
                COALESCE(NULLIF(referral_source, ''), 'Keine Angabe') as source,
                COUNT(*) as count,
                AVG(rating) as avg_rating,
                ROUND((COUNT(*) * 100.0 / SUM(COUNT(*)) OVER()), 1) as percentage
             FROM {$this->table_reviews}
             {$where_clause}
             GROUP BY referral_source
             ORDER BY count DESC",
            $params
        );
        
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * Top Quellen für die Übersicht mit Zeitraum
     */
    public function get_top_review_sources($start_date = null, $end_date = null, $limit = 8) {
        $this->init_reviews_table();
        
        $where_clause = '';
        $params = [];
        
        if ($start_date && $end_date) {
            $where_clause = ' WHERE DATE(created_at) BETWEEN %s AND %s';
            $params = [$start_date, $end_date];
        }
        
        // Füge Limit zu den Parametern hinzu
        $params[] = $limit;
        
        $sql = $this->wpdb->prepare(
            "SELECT 
                COALESCE(NULLIF(referral_source, ''), 'Keine Angabe') as source,
                COUNT(*) as count,
                AVG(rating) as avg_rating,
                ROUND((COUNT(*) * 100.0 / SUM(COUNT(*)) OVER()), 1) as percentage
             FROM {$this->table_reviews}
             {$where_clause}
             GROUP BY referral_source
             ORDER BY count DESC
             LIMIT %d",
            $params
        );
        
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * Sterne-Verteilung für Chart mit Zeitraum
     */
    public function get_rating_distribution_chart($start_date = null, $end_date = null) {
        $this->init_reviews_table();
        
        $where_clause = '';
        $params = [];
        
        if ($start_date && $end_date) {
            $where_clause = ' WHERE DATE(created_at) BETWEEN %s AND %s';
            $params = [$start_date, $end_date];
        }
        
        $sql = $this->wpdb->prepare(
            "SELECT 
                rating,
                COUNT(*) as count,
                ROUND((COUNT(*) * 100.0 / SUM(COUNT(*)) OVER()), 1) as percentage
             FROM {$this->table_reviews}
             {$where_clause}
             GROUP BY rating
             ORDER BY rating DESC",
            $params
        );
        
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * Monatliche Bewertungs-Trends mit Zeitraum
     */
    public function get_monthly_reviews_chart_data($start_date = null, $end_date = null, $months = 12) {
        $this->init_reviews_table();
        
        // Wenn keine expliziten Daten, berechne basierend auf Monaten
        if (!$start_date || !$end_date) {
            $end_date = date('Y-m-d');
            $start_date = date('Y-m-d', strtotime("-$months months"));
        }
        
        $sql = $this->wpdb->prepare(
            "SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as total_reviews,
                AVG(rating) as avg_rating,
                COUNT(CASE WHEN rating = 5 THEN 1 END) as five_stars,
                COUNT(CASE WHEN rating >= 4 THEN 1 END) as positive_reviews
             FROM {$this->table_reviews}
             WHERE DATE(created_at) BETWEEN %s AND %s
             GROUP BY DATE_FORMAT(created_at, '%Y-%m')
             ORDER BY month ASC",
            $start_date, $end_date
        );
        
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * Tägliche Bewertungen mit Zeitraum
     */
    public function get_daily_reviews_chart_data($start_date = null, $end_date = null, $days = 30) {
        $this->init_reviews_table();
        
        // Wenn keine expliziten Daten, berechne basierend auf Tagen
        if (!$start_date || !$end_date) {
            $end_date = date('Y-m-d');
            $start_date = date('Y-m-d', strtotime("-$days days"));
        }
        
        // Erstelle alle Tage im Zeitraum
        $daily_data = [];
        $current_date = $start_date;
        while ($current_date <= $end_date) {
            $daily_data[$current_date] = [
                'reviews' => 0,
                'avg_rating' => 0,
                'positive_reviews' => 0
            ];
            $current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
        }

        // Hole echte Daten
        $results = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT 
                DATE(created_at) as date,
                COUNT(*) as reviews,
                AVG(rating) as avg_rating,
                COUNT(CASE WHEN rating >= 4 THEN 1 END) as positive_reviews
             FROM {$this->table_reviews}
             WHERE DATE(created_at) BETWEEN %s AND %s
             GROUP BY DATE(created_at)
             ORDER BY date ASC",
            $start_date, $end_date
        ), ARRAY_A);

        // Fülle die Daten
        foreach ($results as $row) {
            $date = $row['date'];
            if (isset($daily_data[$date])) {
                $daily_data[$date] = [
                    'reviews' => (int)$row['reviews'],
                    'avg_rating' => round($row['avg_rating'], 1),
                    'positive_reviews' => (int)$row['positive_reviews']
                ];
            }
        }

        return $daily_data;
    }

    /**
     * Top Bewertungen mit Zeitraum
     */
    public function get_top_reviews($start_date = null, $end_date = null, $limit = 10) {
        $this->init_reviews_table();
        
        $where_clause = '';
        $params = [];
        
        if ($start_date && $end_date) {
            $where_clause = ' WHERE DATE(created_at) BETWEEN %s AND %s';
            $params = [$start_date, $end_date];
        }
        
        // Füge Limit zu den Parametern hinzu
        $params[] = $limit;
        
        $sql = $this->wpdb->prepare(
            "SELECT 
                id,
                order_id,
                review_text,
                rating,
                referral_source,
                created_at
             FROM {$this->table_reviews}
             {$where_clause}
             AND LENGTH(TRIM(review_text)) > 10
             AND is_shown = 1
             ORDER BY rating DESC, created_at DESC
             LIMIT %d",
            $params
        );
        
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * Bewertungs-Trends für Chart mit Zeitraum
     */
    public function get_rating_trends_chart($start_date = null, $end_date = null, $months = 6) {
        $this->init_reviews_table();
        
        // Wenn keine expliziten Daten, berechne basierend auf Monaten
        if (!$start_date || !$end_date) {
            $end_date = date('Y-m-d');
            $start_date = date('Y-m-d', strtotime("-$months months"));
        }
        
        $sql = $this->wpdb->prepare(
            "SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                AVG(rating) as avg_rating,
                COUNT(*) as review_count
             FROM {$this->table_reviews}
             WHERE DATE(created_at) BETWEEN %s AND %s
             GROUP BY DATE_FORMAT(created_at, '%Y-%m')
             ORDER BY month ASC",
            $start_date, $end_date
        );
        
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * Alternative: Gruppierung nur für leere/Standardwerte
     */
    public function get_reviews_by_source_cleaned($start_date = null, $end_date = null) {
        $this->init_reviews_table();
        
        $where_clause = '';
        $params = [];
        
        if ($start_date && $end_date) {
            $where_clause = ' WHERE DATE(created_at) BETWEEN %s AND %s';
            $params = [$start_date, $end_date];
        }
        
        $sql = $this->wpdb->prepare(
            "SELECT 
                CASE 
                    WHEN referral_source IS NULL OR referral_source = '' OR referral_source = 'Bitte wählen Sie eine Option (optional)' 
                    THEN 'Keine Angabe'
                    ELSE referral_source
                END as source,
                COUNT(*) as count,
                AVG(rating) as avg_rating,
                ROUND((COUNT(*) * 100.0 / SUM(COUNT(*)) OVER()), 1) as percentage
             FROM {$this->table_reviews}
             {$where_clause}
             GROUP BY 
                CASE 
                    WHEN referral_source IS NULL OR referral_source = '' OR referral_source = 'Bitte wählen Sie eine Option (optional)' 
                    THEN 'Keine Angabe'
                    ELSE referral_source
                END
             ORDER BY count DESC",
            $params
        );
        
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * Tägliche Bewertungen der letzten 30 Tage für Chart
     */
    public function get_daily_reviews_30d() {
        $this->init_reviews_table();
        
        $end_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-29 days'));
        
        // Erstelle alle Tage
        $daily_data = [];
        for ($i = 29; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-$i days"));
            $daily_data[$day] = [
                'reviews' => 0,
                'avg_rating' => 0,
                'positive_reviews' => 0
            ];
        }

        // Hole echte Daten
        $results = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT 
                DATE(created_at) as date,
                COUNT(*) as reviews,
                AVG(rating) as avg_rating,
                COUNT(CASE WHEN rating >= 4 THEN 1 END) as positive_reviews
            FROM {$this->table_reviews}
            WHERE DATE(created_at) BETWEEN %s AND %s
            GROUP BY DATE(created_at)
            ORDER BY date ASC",
            $start_date, $end_date
        ), ARRAY_A);

        // Fülle die Daten
        foreach ($results as $row) {
            $date = $row['date'];
            if (isset($daily_data[$date])) {
                $daily_data[$date] = [
                    'reviews' => (int)$row['reviews'],
                    'avg_rating' => round($row['avg_rating'], 1),
                    'positive_reviews' => (int)$row['positive_reviews']
                ];
            }
        }

        return $daily_data;
    }

    /**
     * Tägliche Bewertungen der letzten 7 Tage für Chart
     */
    public function get_daily_reviews_7d() {
        $this->init_reviews_table();
        
        $end_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-6 days'));
        
        // Erstelle alle Tage
        $daily_data = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-$i days"));
            $daily_data[$day] = [
                'reviews' => 0,
                'avg_rating' => 0,
                'positive_reviews' => 0
            ];
        }

        // Hole echte Daten
        $results = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT 
                DATE(created_at) as date,
                COUNT(*) as reviews,
                AVG(rating) as avg_rating,
                COUNT(CASE WHEN rating >= 4 THEN 1 END) as positive_reviews
            FROM {$this->table_reviews}
            WHERE DATE(created_at) BETWEEN %s AND %s
            GROUP BY DATE(created_at)
            ORDER BY date ASC",
            $start_date, $end_date
        ), ARRAY_A);

        // Fülle die Daten
        foreach ($results as $row) {
            $date = $row['date'];
            if (isset($daily_data[$date])) {
                $daily_data[$date] = [
                    'reviews' => (int)$row['reviews'],
                    'avg_rating' => round($row['avg_rating'], 1),
                    'positive_reviews' => (int)$row['positive_reviews']
                ];
            }
        }

        return $daily_data;
    }

    /**
     * Monatliche Bewertungen der letzten 12 Monate für Chart
     */
    public function get_monthly_reviews_12m() {
        $this->init_reviews_table();
        
        $end_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-11 months'));
        
        $sql = $this->wpdb->prepare(
            "SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as total_reviews,
                AVG(rating) as avg_rating,
                COUNT(CASE WHEN rating = 5 THEN 1 END) as five_stars,
                COUNT(CASE WHEN rating >= 4 THEN 1 END) as positive_reviews
            FROM {$this->table_reviews}
            WHERE DATE(created_at) BETWEEN %s AND %s
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC",
            $start_date, $end_date
        );
        
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * Bewertungs-Trends der letzten 6 Monate für Chart
     */
    public function get_rating_trends_6m() {
        $this->init_reviews_table();
        
        $end_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-5 months'));
        
        $sql = $this->wpdb->prepare(
            "SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                AVG(rating) as avg_rating,
                COUNT(*) as review_count
            FROM {$this->table_reviews}
            WHERE DATE(created_at) BETWEEN %s AND %s
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC",
            $start_date, $end_date
        );
        
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

}