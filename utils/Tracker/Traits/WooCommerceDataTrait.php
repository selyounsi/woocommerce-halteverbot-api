<?php

namespace Utils\Tracker\Traits;

trait WooCommerceDataTrait 
{
    /**
     * WC Events für den Zeitraum
     */
    private function get_wc_events_by_period($start_date, $end_date) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT event_type, COUNT(*) as count,
                    ROUND((COUNT(*) * 100.0 / SUM(COUNT(*)) OVER()), 1) as percentage
             FROM {$this->table_wc_events} 
             WHERE DATE(event_time) BETWEEN %s AND %s 
             GROUP BY event_type 
             ORDER BY count DESC",
            $start_date, $end_date
        ), ARRAY_A);
    }

    /**
     * Conversion Rate für Zeitraum
     */
    private function get_wc_conversion_rate_by_period($start_date, $end_date) {
        $product_views = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) 
            FROM {$this->table_wc_events} 
            WHERE event_type = 'product_view' 
            AND DATE(event_time) BETWEEN %s AND %s",
            $start_date, $end_date
        ));

        $orders = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) 
            FROM {$this->table_wc_events} 
            WHERE event_type = 'order_complete' 
            AND DATE(event_time) BETWEEN %s AND %s",
            $start_date, $end_date
        ));

        return $product_views > 0 ? round(($orders / $product_views) * 100, 2) : 0;
    }

    /**
     * Umsatz für Zeitraum
     */
    public function wc_revenue_by_period($start_date, $end_date) {
        $orders_table = $this->wpdb->prefix . 'wc_orders';
        
        $sql = $this->wpdb->prepare(
            "SELECT SUM(o.total_amount) as revenue
            FROM {$this->table_wc_events} e
            LEFT JOIN {$orders_table} o ON e.order_id = o.id
            WHERE e.event_type = 'order_complete' 
            AND DATE(e.event_time) BETWEEN %s AND %s",
            $start_date, $end_date
        );
        return round($this->wpdb->get_var($sql) ?? 0, 2);
    }

    /**
     * Bestätigter Umsatz für Zeitraum
     */
    public function wc_confirmed_revenue_by_period($start_date, $end_date) {
        $orders_table = $this->wpdb->prefix . 'wc_orders';
        
        $sql = $this->wpdb->prepare(
            "SELECT SUM(o.total_amount) as revenue
            FROM {$this->table_wc_events} e
            LEFT JOIN {$orders_table} o ON e.order_id = o.id
            WHERE e.event_type = 'order_complete' 
            AND DATE(e.event_time) BETWEEN %s AND %s
            AND o.status = 'completed'",
            $start_date, $end_date
        );
        return round($this->wpdb->get_var($sql) ?? 0, 2);
    }

    /**
     * Durchschnittlicher Bestellwert
     */
    private function get_average_order_value($start_date, $end_date) {
        $orders_table = $this->wpdb->prefix . 'wc_orders';
        
        $revenue = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT SUM(o.total_amount) 
            FROM {$this->table_wc_events} e
            LEFT JOIN {$orders_table} o ON e.order_id = o.id
            WHERE e.event_type = 'order_complete' 
            AND DATE(e.event_time) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
        
        $order_count = $this->get_total_orders($start_date, $end_date);
        
        return $order_count > 0 ? round($revenue / $order_count, 2) : 0;
    }

    /**
     * Gesamtzahl der Bestellungen
     */
    private function get_total_orders($start_date, $end_date) {
        return $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) 
            FROM {$this->table_wc_events} 
            WHERE event_type = 'order_complete' 
            AND DATE(event_time) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
    }

    /**
     * Anzahl einmaliger Kunden
     */
    private function get_unique_customers($start_date, $end_date) {
        return $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(DISTINCT customer_id) 
            FROM {$this->table_wc_events} 
            WHERE event_type = 'order_complete' 
            AND customer_id IS NOT NULL
            AND DATE(event_time) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
    }

    /**
     * Kontakt-Conversions
     */
    private function get_contact_conversions($start_date, $end_date) {
        $phone_clicks = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_wc_events} 
            WHERE event_type = 'phone_click' 
            AND DATE(event_time) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
        
        $email_clicks = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_wc_events} 
            WHERE event_type = 'email_click' 
            AND DATE(event_time) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
        
        return [
            'phone_clicks' => $phone_clicks,
            'email_clicks' => $email_clicks,
            'total_contacts' => $phone_clicks + $email_clicks
        ];
    }

    /**
     * Tägliche WC Events für X Tage
     */
    private function get_daily_wc_events_for_days($days) {
        $end_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-'.($days-1).' days'));
        
        // Erstelle alle Tage
        $daily_data = [];
        for ($i = $days-1; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-$i days"));
            $daily_data[$day] = [
                'conversion_rate' => 0,
                'product_view' => 0,        // Korrigiert: product_view statt product_views
                'add_to_cart' => 0,
                'checkout_start' => 0,
                'order_complete' => 0,
                'phone_click' => 0,
                'email_click' => 0
            ];
        }

        // Hole echte Daten aus der Datenbank
        $results = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT DATE(event_time) as day, event_type, COUNT(*) as count
            FROM {$this->table_wc_events} 
            WHERE DATE(event_time) BETWEEN %s AND %s 
            GROUP BY day, event_type 
            ORDER BY day",
            $start_date, $end_date
        ), ARRAY_A);

        // Fülle die täglichen Daten
        foreach ($results as $row) {
            $day = $row['day'];
            if (isset($daily_data[$day])) {
                $daily_data[$day][$row['event_type']] = (int)$row['count'];
            }
        }

        // Berechne Conversion Rate für jeden Tag
        foreach ($daily_data as $day => &$data) {
            $product_views = $data['product_view'];  // Korrigiert: product_view statt product_views
            $orders = $data['order_complete'];
            $data['conversion_rate'] = $product_views > 0 ? round(($orders / $product_views) * 100, 2) : 0;
        }

        return $daily_data;
    }

    /**
     * Funnel Conversion Rate - KORRIGIERT für doppelte Events
     */
    private function get_funnel_rate($from_event, $to_event, $start_date, $end_date) {
        // Zähle UNTERSCHIEDLICHE Sessions mit from_event
        $from_count = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(DISTINCT session_id) FROM {$this->table_wc_events} 
            WHERE event_type = %s AND DATE(event_time) BETWEEN %s AND %s",
            $from_event, $start_date, $end_date
        ));
        
        // Zähle UNTERSCHIEDLICHE Sessions die ZUERST from_event und DANN to_event hatten
        $conversion_count = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(DISTINCT fe.session_id) 
            FROM {$this->table_wc_events} fe
            INNER JOIN {$this->table_wc_events} te ON fe.session_id = te.session_id
            WHERE fe.event_type = %s 
            AND te.event_type = %s
            AND fe.event_time < te.event_time
            AND DATE(fe.event_time) BETWEEN %s AND %s
            AND DATE(te.event_time) BETWEEN %s AND %s",
            $from_event, $to_event, $start_date, $end_date, $start_date, $end_date
        ));
        
        return [
            'from_count' => $from_count,
            'conversion_count' => $conversion_count,
            'percentage' => $from_count > 0 ? round(($conversion_count / $from_count) * 100, 2) : 0
        ];
    }

    /**
     * Warenkorb-Abbrecherquote
     */
    private function get_cart_abandonment_rate($start_date, $end_date) {
        $cart_sessions = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(DISTINCT session_id) 
            FROM {$this->table_wc_events} 
            WHERE event_type = 'add_to_cart' 
            AND DATE(event_time) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
        
        $order_sessions = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(DISTINCT session_id) 
            FROM {$this->table_wc_events} 
            WHERE event_type = 'order_complete' 
            AND DATE(event_time) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
        
        return $cart_sessions > 0 ? round((($cart_sessions - $order_sessions) / $cart_sessions) * 100, 2) : 0;
    }

    /**
     * Kontakt-Engagement Rate
     */
    private function get_contact_engagement_rate($start_date, $end_date) {
        $product_views = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_wc_events} 
            WHERE event_type = 'product_view' 
            AND DATE(event_time) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
        
        $contact_events = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_wc_events} 
            WHERE event_type IN ('phone_click', 'email_click') 
            AND DATE(event_time) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
        
        return $product_views > 0 ? round(($contact_events / $product_views) * 100, 2) : 0;
    }

    /**
     * Einfache Session Analyse mit session_id
     */
    private function get_session_analysis($start_date, $end_date) {
        $sessions_per_visitor = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT ip, user_agent, COUNT(DISTINCT session_id) as session_count
            FROM {$this->table_logs} 
            WHERE DATE(visit_time) BETWEEN %s AND %s
            GROUP BY ip, user_agent",
            $start_date, $end_date
        ), ARRAY_A);
        
        $new_sessions = 0;
        $returning_sessions = 0;
        
        foreach ($sessions_per_visitor as $visitor) {
            if ($visitor['session_count'] == 1) {
                $new_sessions++;
            } else {
                $returning_sessions++;
            }
        }
        
        return [
            'new_sessions' => $new_sessions,
            'returning_sessions' => $returning_sessions,
            'total_visitors' => count($sessions_per_visitor)
        ];
    }

    /**
     * Zählt High-Value Sessions (mit Bestellung oder Kontakt)
     */
    private function get_high_value_sessions($start_date, $end_date) {
        return $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(DISTINCT e.session_id) 
            FROM {$this->table_wc_events} e
            INNER JOIN {$this->table_logs} l ON e.session_id = l.session_id
            WHERE e.event_type IN ('order_complete', 'phone_click', 'email_click')
            AND DATE(e.event_time) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
    }

    /**
     * Online Bestellungen
     */
    private function get_online_orders($start_date, $end_date) {
        return $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) 
            FROM {$this->table_wc_events} 
            WHERE event_type = 'order_complete' 
            AND DATE(event_time) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
    }

    /**
     * Kontakt Leads
     */
    private function get_contact_leads($start_date, $end_date) {
        return $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(DISTINCT session_id) 
            FROM {$this->table_wc_events} 
            WHERE event_type IN ('phone_click', 'email_click')
            AND DATE(event_time) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
    }

    /**
     * Berechnet Conversion Rate nach Gerätetyp
     */
    private function get_conversion_rate_by_device($device_type, $start_date, $end_date) {
        $product_views = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) 
            FROM {$this->table_wc_events} e
            INNER JOIN {$this->table_logs} l ON e.session_id = l.session_id
            WHERE e.event_type = 'product_view' 
            AND l.device_type = %s
            AND DATE(e.event_time) BETWEEN %s AND %s",
            $device_type, $start_date, $end_date
        ));
        
        $orders = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) 
            FROM {$this->table_wc_events} e
            INNER JOIN {$this->table_logs} l ON e.session_id = l.session_id
            WHERE e.event_type = 'order_complete' 
            AND l.device_type = %s
            AND DATE(e.event_time) BETWEEN %s AND %s",
            $device_type, $start_date, $end_date
        ));
        
        return $product_views > 0 ? round(($orders / $product_views) * 100, 2) : 0;
    }

    /**
     * Gibt Kontakt-Events zurück
     */
    private function get_contact_events_by_period($start_date, $end_date) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT event_type, COUNT(*) as count,
                    ROUND((COUNT(*) * 100.0 / SUM(COUNT(*)) OVER()), 1) as percentage
            FROM {$this->table_wc_events} 
            WHERE event_type IN ('phone_click', 'email_click')
            AND DATE(event_time) BETWEEN %s AND %s 
            GROUP BY event_type 
            ORDER BY count DESC",
            $start_date, $end_date
        ), ARRAY_A);
    }

    /**
     * Such-Analytics
     */
    private function get_search_analytics($start_date, $end_date) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT extra_value as search_term, COUNT(*) as search_count
            FROM {$this->table_wc_events} 
            WHERE event_type = 'product_search'
            AND DATE(event_time) BETWEEN %s AND %s 
            GROUP BY extra_value 
            ORDER BY search_count DESC 
            LIMIT 10",
            $start_date, $end_date
        ), ARRAY_A);
    }

    /**
     * Kategorie Engagement
     */
    private function get_category_engagement($start_date, $end_date) {
        return $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_wc_events} 
            WHERE event_type = 'category_view' 
            AND DATE(event_time) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
    }

    /**
     * Wunschliste Aktivität
     */
    private function get_wishlist_activity($start_date, $end_date) {
        return $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_wc_events} 
            WHERE event_type = 'add_to_wishlist' 
            AND DATE(event_time) BETWEEN %s AND %s",
            $start_date, $end_date
        ));
    }

    /**
     * Conversion nach Quelle
     */
    private function get_conversion_source_rate($event_types, $start_date, $end_date) {
        return $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_wc_events} 
            WHERE event_type IN (%s) 
            AND DATE(event_time) BETWEEN %s AND %s",
            $event_types, $start_date, $end_date
        ));
    }
}