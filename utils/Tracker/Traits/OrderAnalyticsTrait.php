<?php

namespace Utils\Tracker\Traits;

trait OrderAnalyticsTrait 
{
    /**
     * Holt alle verfügbaren WooCommerce Bestellstatus
     */
    private function get_wc_order_statuses() {
        return wc_get_order_statuses(); // Gibt Array zurück: ['wc-pending' => 'Pending', 'wc-processing' => 'Processing', ...]
    }

    /**
     * Bestell-Statistiken für Zeitraum
     */
    public function get_order_stats($start_date = null, $end_date = null) {
        $args = [
            'limit' => -1,
            'return' => 'objects',
        ];
        
        // Datumsfilter hinzufügen
        if ($start_date && $end_date) {
            $args['date_created'] = $start_date . '...' . $end_date;
        }
        
        $orders = wc_get_orders($args);
        
        $total_orders = 0;
        $total_revenue = 0;
        $customer_ids = [];
        
        foreach ($orders as $order) {
            // Refunds ausschließen
            if ($order instanceof \WC_Order_Refund) {
                continue;
            }
            
            $total_orders++;
            $total_revenue += $order->get_total();
            
            // Verwende get_user_id() statt get_customer_id() für Kompatibilität
            $customer_id = $order->get_user_id();
            if ($customer_id) {
                $customer_ids[$customer_id] = true;
            }
        }
        
        $avg_order_value = $total_orders > 0 ? $total_revenue / $total_orders : 0;
        $unique_customers = count($customer_ids);
        
        return [
            'total_orders' => $total_orders,
            'total_revenue' => round($total_revenue, 2),
            'avg_order_value' => round($avg_order_value, 2),
            'unique_customers' => $unique_customers
        ];
    }

    /**
     * Bestellungen der letzten 7 Tage
     */
    public function get_order_stats_7d() {
        $end_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-6 days'));
        return $this->get_order_stats($start_date, $end_date);
    }

    /**
     * Bestellungen der letzten 30 Tage
     */
    public function get_order_stats_30d() {
        $end_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-29 days'));
        return $this->get_order_stats($start_date, $end_date);
    }

    /**
     * Status-Verteilung für Zeitraum mit Status-Namen
     */
    public function get_order_status_distribution($start_date = null, $end_date = null) {
        $statuses = $this->get_wc_order_statuses();
        
        $args = [
            'limit' => -1,
            'return' => 'objects',
        ];
        
        // Datumsfilter hinzufügen
        if ($start_date && $end_date) {
            $args['date_created'] = $start_date . '...' . $end_date;
        }
        
        $orders = wc_get_orders($args);
        
        $status_distribution = [];
        $total_orders = count($orders);
        
        foreach ($orders as $order) {
            $status = $order->get_status();
            $status_key = 'wc-' . $status;
            
            if (!isset($status_distribution[$status_key])) {
                $status_distribution[$status_key] = [
                    'status' => $status_key,
                    'count' => 0,
                    'revenue' => 0,
                    'status_name' => isset($statuses[$status_key]) ? $statuses[$status_key] : $status
                ];
            }
            
            $status_distribution[$status_key]['count']++;
            $status_distribution[$status_key]['revenue'] += $order->get_total();
        }
        
        // Prozente berechnen und in Array umwandeln
        $results = [];
        foreach ($status_distribution as $status_data) {
            $status_data['percentage'] = $total_orders > 0 ? round(($status_data['count'] / $total_orders) * 100, 1) : 0;
            $status_data['revenue'] = round($status_data['revenue'], 2);
            $results[] = $status_data;
        }
        
        // Nach Anzahl sortieren
        usort($results, function($a, $b) {
            return $b['count'] - $a['count'];
        });
        
        return $results;
    }

    /**
     * Tägliche Bestellungen für Chart (30 Tage)
     */
    public function get_daily_orders_30d() {
        $end_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-29 days'));
        
        $daily_data = [];
        for ($i = 29; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-$i days"));
            $daily_data[$day] = [
                'orders' => 0,
                'revenue' => 0,
                'completed_orders' => 0,
                'completed_revenue' => 0
            ];
        }

        $args = [
            'limit' => -1,
            'date_created' => $start_date . '...' . $end_date,
            'return' => 'objects',
        ];
        
        $orders = wc_get_orders($args);
        
        foreach ($orders as $order) {
            $order_date = $order->get_date_created()->format('Y-m-d');
            $status = $order->get_status();
            $total = $order->get_total();
            
            if (isset($daily_data[$order_date])) {
                $daily_data[$order_date]['orders']++;
                $daily_data[$order_date]['revenue'] += $total;
                
                if ($status === 'completed') {
                    $daily_data[$order_date]['completed_orders']++;
                    $daily_data[$order_date]['completed_revenue'] += $total;
                }
            }
        }
        
        // Werte runden
        foreach ($daily_data as &$day_data) {
            $day_data['revenue'] = round($day_data['revenue'], 2);
            $day_data['completed_revenue'] = round($day_data['completed_revenue'], 2);
        }
        
        return $daily_data;
    }

    /**
     * Tägliche Bestellungen für Chart (7 Tage)
     */
    public function get_daily_orders_7d() {
        $end_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-6 days'));
        
        $daily_data = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-$i days"));
            $daily_data[$day] = [
                'orders' => 0,
                'revenue' => 0,
                'completed_orders' => 0,
                'completed_revenue' => 0
            ];
        }

        $args = [
            'limit' => -1,
            'date_created' => $start_date . '...' . $end_date,
            'return' => 'objects',
        ];
        
        $orders = wc_get_orders($args);
        
        foreach ($orders as $order) {
            $order_date = $order->get_date_created()->format('Y-m-d');
            $status = $order->get_status();
            $total = $order->get_total();
            
            if (isset($daily_data[$order_date])) {
                $daily_data[$order_date]['orders']++;
                $daily_data[$order_date]['revenue'] += $total;
                
                if ($status === 'completed') {
                    $daily_data[$order_date]['completed_orders']++;
                    $daily_data[$order_date]['completed_revenue'] += $total;
                }
            }
        }
        
        // Werte runden
        foreach ($daily_data as &$day_data) {
            $day_data['revenue'] = round($day_data['revenue'], 2);
            $day_data['completed_revenue'] = round($day_data['completed_revenue'], 2);
        }
        
        return $daily_data;
    }

    /**
     * Monatliche Bestellungen für Chart (12 Monate)
     */
    public function get_monthly_orders_12m() {
        $end_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-11 months'));
        
        $args = [
            'limit' => -1,
            'date_created' => $start_date . '...' . $end_date,
            'return' => 'objects',
        ];
        
        $orders = wc_get_orders($args);
        
        $monthly_data = [];
        
        foreach ($orders as $order) {
            $month = $order->get_date_created()->format('Y-m');
            $status = $order->get_status();
            $total = $order->get_total();
            
            if (!isset($monthly_data[$month])) {
                $monthly_data[$month] = [
                    'month' => $month,
                    'total_orders' => 0,
                    'total_revenue' => 0,
                    'completed_orders' => 0,
                    'completed_revenue' => 0
                ];
            }
            
            $monthly_data[$month]['total_orders']++;
            $monthly_data[$month]['total_revenue'] += $total;
            
            if ($status === 'completed') {
                $monthly_data[$month]['completed_orders']++;
                $monthly_data[$month]['completed_revenue'] += $total;
            }
        }
        
        // Durchschnitt berechnen und Werte runden
        $results = [];
        foreach ($monthly_data as $month_data) {
            $month_data['avg_order_value'] = $month_data['total_orders'] > 0 ? 
                round($month_data['total_revenue'] / $month_data['total_orders'], 2) : 0;
            $month_data['total_revenue'] = round($month_data['total_revenue'], 2);
            $month_data['completed_revenue'] = round($month_data['completed_revenue'], 2);
            $results[] = $month_data;
        }
        
        // Nach Monat sortieren
        usort($results, function($a, $b) {
            return strcmp($a['month'], $b['month']);
        });
        
        return $results;
    }

    /**
     * Top Produkte nach Umsatz für Zeitraum
     */
    public function get_top_products_by_revenue($start_date = null, $end_date = null, $limit = 10) {
        $args = [
            'limit' => -1,
            'status' => ['completed'],
            'return' => 'objects',
        ];
        
        // Datumsfilter hinzufügen
        if ($start_date && $end_date) {
            $args['date_created'] = $start_date . '...' . $end_date;
        }
        
        $orders = wc_get_orders($args);
        
        $products_data = [];
        
        foreach ($orders as $order) {
            foreach ($order->get_items() as $item) {
                $product = $item->get_product();
                $product_id = $item->get_product_id();
                $product_name = $item->get_name();
                $quantity = $item->get_quantity();
                $total = $item->get_total();
                
                if (!isset($products_data[$product_id])) {
                    $products_data[$product_id] = [
                        'product_id' => $product_id,
                        'product_name' => $product_name,
                        'total_quantity' => 0,
                        'total_revenue' => 0,
                        'order_count' => 0,
                        'order_ids' => []
                    ];
                }
                
                $products_data[$product_id]['total_quantity'] += $quantity;
                $products_data[$product_id]['total_revenue'] += $total;
                
                if (!in_array($order->get_id(), $products_data[$product_id]['order_ids'])) {
                    $products_data[$product_id]['order_ids'][] = $order->get_id();
                    $products_data[$product_id]['order_count']++;
                }
            }
        }
        
        // Durchschnittspreis berechnen und in Array umwandeln
        $results = [];
        foreach ($products_data as $product_data) {
            $product_data['avg_product_price'] = $product_data['total_quantity'] > 0 ? 
                round($product_data['total_revenue'] / $product_data['total_quantity'], 2) : 0;
            $product_data['total_revenue'] = round($product_data['total_revenue'], 2);
            unset($product_data['order_ids']); // Nicht benötigte Daten entfernen
            $results[] = $product_data;
        }
        
        // Nach Umsatz sortieren und limitieren
        usort($results, function($a, $b) {
            return $b['total_revenue'] - $a['total_revenue'];
        });
        
        return array_slice($results, 0, $limit);
    }

    /**
     * Herkunft der Bestellungen (Payment Methoden)
     */
    public function get_order_sources($start_date = null, $end_date = null) {
        $args = [
            'limit' => -1,
            'return' => 'objects',
        ];
        
        // Datumsfilter hinzufügen
        if ($start_date && $end_date) {
            $args['date_created'] = $start_date . '...' . $end_date;
        }
        
        $orders = wc_get_orders($args);
        
        $payment_methods = [];
        $total_orders = 0;
        
        foreach ($orders as $order) {
            // Refunds ausschließen
            if ($order instanceof \WC_Order_Refund) {
                continue;
            }
            
            // Payment Method mit Fallback
            $payment_method = method_exists($order, 'get_payment_method') ? $order->get_payment_method() : 'unknown';
            $total = $order->get_total();
            
            if (!isset($payment_methods[$payment_method])) {
                $payment_methods[$payment_method] = [
                    'payment_method' => $payment_method,
                    'count' => 0,
                    'revenue' => 0,
                    'order_totals' => []
                ];
            }
            
            $payment_methods[$payment_method]['count']++;
            $payment_methods[$payment_method]['revenue'] += $total;
            $payment_methods[$payment_method]['order_totals'][] = $total;
            $total_orders++;
        }
        
        // Prozente und Durchschnitt berechnen
        $results = [];
        foreach ($payment_methods as $method_data) {
            $method_data['percentage'] = $total_orders > 0 ? 
                round(($method_data['count'] / $total_orders) * 100, 1) : 0;
            $method_data['avg_order_value'] = $method_data['count'] > 0 ? 
                round($method_data['revenue'] / $method_data['count'], 2) : 0;
            $method_data['revenue'] = round($method_data['revenue'], 2);
            unset($method_data['order_totals']); // Nicht benötigte Daten entfernen
            $results[] = $method_data;
        }
        
        // Nach Anzahl sortieren
        usort($results, function($a, $b) {
            return $b['count'] - $a['count'];
        });
        
        return $results;
    }

    /**
     * Kunden-Wiederholungsrate
     */
    public function get_customer_repeat_rate($start_date = null, $end_date = null) {
        $args = [
            'limit' => -1,
            'return' => 'objects',
        ];
        
        // Datumsfilter hinzufügen
        if ($start_date && $end_date) {
            $args['date_created'] = $start_date . '...' . $end_date;
        }
        
        $orders = wc_get_orders($args);
        
        $customers = [];
        
        foreach ($orders as $order) {
            // Refunds ausschließen
            if ($order instanceof \WC_Order_Refund) {
                continue;
            }
            
            // Verwende get_user_id() statt get_customer_id() für Kompatibilität
            $customer_id = $order->get_user_id();
            if ($customer_id) {
                if (!isset($customers[$customer_id])) {
                    $customers[$customer_id] = 0;
                }
                $customers[$customer_id]++;
            }
        }
        
        $total_customers = count($customers);
        $repeat_customers = 0;
        
        foreach ($customers as $order_count) {
            if ($order_count > 1) {
                $repeat_customers++;
            }
        }
        
        $repeat_rate = $total_customers > 0 ? round(($repeat_customers / $total_customers) * 100, 1) : 0;
        
        return [
            'total_customers' => $total_customers,
            'repeat_customers' => $repeat_customers,
            'repeat_rate' => $repeat_rate
        ];
    }

    /**
     * Durchschnittliche Bestellwerte nach Status
     */
    public function get_avg_order_value_by_status($start_date = null, $end_date = null) {
        $args = [
            'limit' => -1,
            'return' => 'objects',
        ];
        
        // Datumsfilter hinzufügen
        if ($start_date && $end_date) {
            $args['date_created'] = $start_date . '...' . $end_date;
        }
        
        $orders = wc_get_orders($args);
        
        $status_data = [];
        
        foreach ($orders as $order) {
            $status = $order->get_status();
            $total = $order->get_total();
            
            if (!isset($status_data[$status])) {
                $status_data[$status] = [
                    'status' => $status,
                    'order_count' => 0,
                    'total_revenue' => 0,
                    'min_order_value' => PHP_FLOAT_MAX,
                    'max_order_value' => 0
                ];
            }
            
            $status_data[$status]['order_count']++;
            $status_data[$status]['total_revenue'] += $total;
            $status_data[$status]['min_order_value'] = min($status_data[$status]['min_order_value'], $total);
            $status_data[$status]['max_order_value'] = max($status_data[$status]['max_order_value'], $total);
        }
        
        // Durchschnitt berechnen und in Array umwandeln
        $results = [];
        foreach ($status_data as $data) {
            $data['avg_order_value'] = $data['order_count'] > 0 ? 
                round($data['total_revenue'] / $data['order_count'], 2) : 0;
            $data['min_order_value'] = $data['min_order_value'] === PHP_FLOAT_MAX ? 0 : round($data['min_order_value'], 2);
            $data['max_order_value'] = round($data['max_order_value'], 2);
            $results[] = $data;
        }
        
        // Nach Durchschnittswert sortieren
        usort($results, function($a, $b) {
            return $b['avg_order_value'] - $a['avg_order_value'];
        });
        
        return $results;
    }

    /**
     * Stündliche Bestellungs-Verteilung für Heatmap
     */
    public function get_order_time_heatmap($start_date = null, $end_date = null) {
        $args = [
            'limit' => -1,
            'return' => 'objects',
        ];
        
        // Datumsfilter hinzufügen
        if ($start_date && $end_date) {
            $args['date_created'] = $start_date . '...' . $end_date;
        }
        
        $orders = wc_get_orders($args);
        
        $heatmap_data = [];
        
        foreach ($orders as $order) {
            $hour = (int)$order->get_date_created()->format('H');
            $day = $order->get_date_created()->format('l'); // Monday, Tuesday, etc.
            $total = $order->get_total();
            
            $key = $hour . '_' . $day;
            
            if (!isset($heatmap_data[$key])) {
                $heatmap_data[$key] = [
                    'hour' => $hour,
                    'day' => $day,
                    'orders' => 0,
                    'total_revenue' => 0
                ];
            }
            
            $heatmap_data[$key]['orders']++;
            $heatmap_data[$key]['total_revenue'] += $total;
        }
        
        // Durchschnitt berechnen und in Array umwandeln
        $results = [];
        foreach ($heatmap_data as $data) {
            $data['avg_order_value'] = $data['orders'] > 0 ? 
                round($data['total_revenue'] / $data['orders'], 2) : 0;
            $results[] = $data;
        }
        
        // Nach Wochentag und Stunde sortieren
        $day_order = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        usort($results, function($a, $b) use ($day_order) {
            $day_cmp = array_search($a['day'], $day_order) - array_search($b['day'], $day_order);
            if ($day_cmp !== 0) return $day_cmp;
            return $a['hour'] - $b['hour'];
        });
        
        return $results;
    }


    /**
     * Bestellungen für beliebigen Zeitraum
     */
    public function total_orders_range($start_date, $end_date, $device = null) {
        // Sicherheits-Check: Falls Datumsangaben leer oder ungültig sind
        if (!$start_date || !$end_date) {
            return [
                'total_orders' => 0,
                'completed_orders' => 0,
                'pending_orders' => 0,
                'cancelled_orders' => 0,
                'refunded_orders' => 0,
                'total_revenue' => 0,
                'completed_revenue' => 0,
                'avg_order_value' => 0,
                'completed_percentage' => 0,
                'unique_customers' => 0,
                'avg_processing_time' => 0,
            ];
        }

        // Standardisiere Datumsformat auf Y-m-d H:i:s
        $start_date = date('Y-m-d 00:00:00', strtotime($start_date));
        $end_date = date('Y-m-d 23:59:59', strtotime($end_date));

        // Verwende die zentrale Helper-Methode
        return $this->get_total_orders_for_period($start_date, $end_date, $device);
    }


    /**
     * Heutige Bestellungen
     */
    public function total_orders_today($device = null) {
        $start_date = date('Y-m-d 00:00:00');
        $end_date = date('Y-m-d 23:59:59');
        return $this->get_total_orders_for_period($start_date, $end_date, $device);
    }

    /**
     * Gestern
     */
    public function total_orders_yesterday($device = null) {
        $start_date = date('Y-m-d 00:00:00', strtotime('-1 day'));
        $end_date = date('Y-m-d 23:59:59', strtotime('-1 day'));
        return $this->get_total_orders_for_period($start_date, $end_date, $device);
    }

    /**
     * Diese Woche (Montag–heute)
     */
    public function total_orders_this_week($device = null) {
        $start_date = date('Y-m-d 00:00:00', strtotime('monday this week'));
        $end_date = date('Y-m-d 23:59:59');
        return $this->get_total_orders_for_period($start_date, $end_date, $device);
    }

    /**
     * Diesen Monat
     */
    public function total_orders_this_month($device = null) {
        $start_date = date('Y-m-01 00:00:00');
        $end_date = date('Y-m-t 23:59:59');
        return $this->get_total_orders_for_period($start_date, $end_date, $device);
    }

    /**
     * Letzten Monat
     */
    public function total_orders_last_month($device = null) {
        $start_date = date('Y-m-01 00:00:00', strtotime('first day of last month'));
        $end_date = date('Y-m-t 23:59:59', strtotime('last day of last month'));
        return $this->get_total_orders_for_period($start_date, $end_date, $device);
    }

    /**
     * Dieses Jahr
     */
    public function total_orders_this_year($device = null) {
        $start_date = date('Y-01-01 00:00:00');
        $end_date = date('Y-12-31 23:59:59');
        return $this->get_total_orders_for_period($start_date, $end_date, $device);
    }

    /**
     * Zentrale Auswertung für beliebige Zeiträume
     */
    private function get_total_orders_for_period($start_date, $end_date, $device = null) {
        $args = [
            'limit' => -1,
            'return' => 'objects',
            'date_created' => $start_date . '...' . $end_date,
        ];

        // Optionaler Gerätefilter (wenn z. B. als Order Meta gespeichert)
        if ($device) {
            $args['meta_query'] = [
                [
                    'key' => 'device',
                    'value' => $device,
                    'compare' => '='
                ]
            ];
        }

        $orders = wc_get_orders($args);

        $stats = [
            'total_orders' => 0,           // Gesamtanzahl aller Bestellungen im Zeitraum (unabhängig vom Status)
            'completed_orders' => 0,       // Anzahl der abgeschlossenen / erfolgreich bezahlten Bestellungen
            'pending_orders' => 0,         // Anzahl der offenen / noch nicht abgeschlossenen Bestellungen
            'cancelled_orders' => 0,       // Anzahl der vom Kunden oder System stornierten Bestellungen
            'refunded_orders' => 0,        // Anzahl der erstatteten Bestellungen (nach Abschluss oder Storno)
            'total_revenue' => 0,          // Gesamtumsatz aller Bestellungen im Zeitraum (inkl. offener)
            'completed_revenue' => 0,      // Umsatz ausschließlich aus abgeschlossenen Bestellungen
            'unique_customers' => [],      // Liste oder Anzahl der eindeutigen Kunden, die im Zeitraum bestellt haben
            'avg_processing_time' => 0,    // Durchschnittliche Bearbeitungszeit pro Bestellung (in Stunden, z. B. von Bestellung bis Abschluss)
            'order_status_distribution' => $this->get_order_status_distribution($start_date, $end_date),
            'order_sources' => $this->get_order_sources($start_date, $end_date),
            'order_time_heatmap' => $this->get_order_time_heatmap($start_date, $end_date),
        ];

        foreach ($orders as $order) {
            if ($order instanceof \WC_Order_Refund) continue;

            $stats['total_orders']++;
            $stats['total_revenue'] += $order->get_total();

            $status = $order->get_status();

            if ($status === 'completed') {
                $stats['completed_orders']++;
                $stats['completed_revenue'] += $order->get_total();

                // Bearbeitungszeit berechnen
                $created = $order->get_date_created();
                $completed = $order->get_date_completed();
                if ($created && $completed) {
                    $diff = $completed->getTimestamp() - $created->getTimestamp();
                    $stats['avg_processing_time'] += $diff / 3600; // Stunden
                }
            } elseif ($status === 'pending') {
                $stats['pending_orders']++;
            } elseif (in_array($status, ['cancelled', 'failed'])) {
                $stats['cancelled_orders']++;
            } elseif ($status === 'refunded') {
                $stats['refunded_orders']++;
            }

            // Kunden zählen
            $customer_id = $order->get_user_id();
            if ($customer_id) {
                $stats['unique_customers'][$customer_id] = true;
            }
        }

        // Berechnete Felder ergänzen
        $completed_percentage = $stats['total_orders'] > 0
            ? round(($stats['completed_orders'] / $stats['total_orders']) * 100, 1)
            : 0;

        $stats['avg_order_value'] = $stats['total_orders'] > 0
            ? round($stats['total_revenue'] / $stats['total_orders'], 2)
            : 0;

        $stats['completed_percentage'] = $completed_percentage;
        $stats['unique_customers'] = count($stats['unique_customers']);
        $stats['avg_processing_time'] = $stats['completed_orders'] > 0
            ? round($stats['avg_processing_time'] / $stats['completed_orders'], 2)
            : 0;

        return $stats;
    }

}