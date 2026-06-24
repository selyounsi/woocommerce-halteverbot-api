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

    // Stream orders in batches instead of loading all (limit => -1) at once. WooCommerce caches every
    // loaded order in the object cache, so the runtime cache is flushed after each batch to actually
    // free the memory; otherwise it keeps growing across batches and exhausts the memory limit.
    private function iterate_orders(array $args) {
        unset($args['paged'], $args['offset']);
        $args['limit'] = 200;
        $page = 1;
        do {
            $args['paged'] = $page;
            $batch = wc_get_orders($args);
            $found = is_array($batch) ? count($batch) : 0;
            foreach ($batch as $order) {
                yield $order;
            }
            unset($batch);
            if (function_exists('wp_cache_flush_runtime')) {
                wp_cache_flush_runtime();
            }
            gc_collect_cycles();
            $page++;
        } while ($found === 200);
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
        
        
        $total_orders = 0;
        $total_revenue = 0;
        $customer_ids = [];
        
        foreach ($this->iterate_orders($args) as $order) {
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
        
        $status_distribution = [];
        $total_orders = 0;

        foreach ($this->iterate_orders($args) as $order) {
            $total_orders++;
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
     * All-time status distribution counted per status through the SAME REST
     * endpoint the order list uses (wc/v3/orders -> X-WP-Total header), so every
     * count is guaranteed to match the list exactly. Runs in-process via
     * rest_do_request (no extra HTTP) and only fetches one order per status.
     */
    public function get_order_status_distribution_all_time() {
        $statuses = $this->get_wc_order_statuses();

        $rows = [];
        $total_orders = 0;

        foreach ($statuses as $status_key => $status_name) {
            $request = new \WP_REST_Request('GET', '/wc/v3/orders');
            $request->set_query_params([
                'status'   => preg_replace('/^wc-/', '', $status_key),
                'per_page' => 1,
            ]);

            $response = rest_do_request($request);
            $headers = is_object($response) ? $response->get_headers() : [];
            $count = isset($headers['X-WP-Total']) ? (int) $headers['X-WP-Total'] : 0;
            if ($count <= 0) {
                continue;
            }

            $rows[] = [
                'status'      => $status_key,
                'count'       => $count,
                'revenue'     => 0,
                'status_name' => $status_name,
            ];
            $total_orders += $count;
        }

        foreach ($rows as &$row) {
            $row['percentage'] = $total_orders > 0 ? round(($row['count'] / $total_orders) * 100, 1) : 0;
        }
        unset($row);

        usort($rows, function ($a, $b) {
            return $b['count'] - $a['count'];
        });

        return $rows;
    }

    /**
     * All-time order revenue summed per status in a single query (HPOS-aware),
     * so the overview KPIs can show real totals without iterating every order.
     * Returns total revenue (all statuses) and completed revenue.
     */
    public function get_order_revenue_all_time() {
        try {
            $statuses = array_keys($this->get_wc_order_statuses());
            if (empty($statuses)) {
                return ['total' => 0, 'completed' => 0];
            }
            $in = implode(',', array_fill(0, count($statuses), '%s'));
            $prefix = $this->wpdb->prefix;
            $hpos = class_exists('\Automattic\WooCommerce\Utilities\OrderUtil')
                && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();

            if ($hpos) {
                $rows = $this->wpdb->get_results($this->wpdb->prepare(
                    "SELECT status, SUM(total_amount) AS rev
                     FROM {$prefix}wc_orders
                     WHERE type = 'shop_order' AND status IN ($in)
                     GROUP BY status",
                    $statuses
                ));
            } else {
                $rows = $this->wpdb->get_results($this->wpdb->prepare(
                    "SELECT p.post_status AS status, SUM(CAST(pm.meta_value AS DECIMAL(18,2))) AS rev
                     FROM {$prefix}posts p
                     LEFT JOIN {$prefix}postmeta pm ON pm.post_id = p.ID AND pm.meta_key = '_order_total'
                     WHERE p.post_type = 'shop_order' AND p.post_status IN ($in)
                     GROUP BY p.post_status",
                    $statuses
                ));
            }

            $total = 0.0;
            $completed = 0.0;
            foreach ((array) $rows as $row) {
                $rev = (float) $row->rev;
                $total += $rev;
                if ($row->status === 'wc-completed') {
                    $completed = $rev;
                }
            }

            return ['total' => round($total, 2), 'completed' => round($completed, 2)];
        } catch (\Throwable $e) {
            return ['total' => 0, 'completed' => 0];
        }
    }

    /**
     * All-time order count and revenue grouped by payment method, in a single
     * HPOS-aware query, matching the shape of get_order_sources().
     */
    public function get_order_sources_all_time() {
        try {
            $statuses = array_keys($this->get_wc_order_statuses());
            if (empty($statuses)) {
                return [];
            }
            $in = implode(',', array_fill(0, count($statuses), '%s'));
            $prefix = $this->wpdb->prefix;
            $hpos = class_exists('\Automattic\WooCommerce\Utilities\OrderUtil')
                && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();

            if ($hpos) {
                $rows = $this->wpdb->get_results($this->wpdb->prepare(
                    "SELECT payment_method AS payment_method, COUNT(*) AS cnt, SUM(total_amount) AS rev
                     FROM {$prefix}wc_orders
                     WHERE type = 'shop_order' AND status IN ($in)
                     GROUP BY payment_method",
                    $statuses
                ));
            } else {
                $rows = $this->wpdb->get_results($this->wpdb->prepare(
                    "SELECT pm.meta_value AS payment_method, COUNT(*) AS cnt, SUM(CAST(pt.meta_value AS DECIMAL(18,2))) AS rev
                     FROM {$prefix}posts p
                     LEFT JOIN {$prefix}postmeta pm ON pm.post_id = p.ID AND pm.meta_key = '_payment_method'
                     LEFT JOIN {$prefix}postmeta pt ON pt.post_id = p.ID AND pt.meta_key = '_order_total'
                     WHERE p.post_type = 'shop_order' AND p.post_status IN ($in)
                     GROUP BY pm.meta_value",
                    $statuses
                ));
            }

            $total_orders = 0;
            foreach ((array) $rows as $row) {
                $total_orders += (int) $row->cnt;
            }

            $results = [];
            foreach ((array) $rows as $row) {
                $count = (int) $row->cnt;
                $revenue = round((float) $row->rev, 2);
                $results[] = [
                    'payment_method'  => $row->payment_method ?: 'unknown',
                    'count'           => $count,
                    'revenue'         => $revenue,
                    'percentage'      => $total_orders > 0 ? round(($count / $total_orders) * 100, 1) : 0,
                    'avg_order_value' => $count > 0 ? round($revenue / $count, 2) : 0,
                ];
            }

            usort($results, function ($a, $b) {
                return $b['count'] - $a['count'];
            });

            return $results;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Empty insights skeleton so the endpoint always returns a consistent shape.
     */
    private function empty_insights() {
        return [
            'kpis' => [
                'total_orders' => 0, 'completed_orders' => 0, 'completed_revenue' => 0,
                'total_revenue' => 0, 'avg_order_value' => 0, 'completed_percentage' => 0,
                'pending_orders' => 0, 'cancelled_orders' => 0, 'refunded_orders' => 0,
            ],
            'status_breakdown' => [],
            'payment_methods'  => [],
            'value_buckets'    => [],
            'weekday_hour'     => [],
            'by_hour'          => [],
            'by_weekday'       => [],
            'monthly'          => [],
            'daily'            => [],
        ];
    }

    /**
     * One efficient, date-flexible insights payload for the orders dashboard.
     * Everything is computed with grouped SQL (HPOS-aware), restricted to the
     * registered order statuses (so trash/draft are excluded and totals match the
     * status distribution). Pass start/end (Y-m-d) for a period, or null for all
     * time. Each block is isolated in try/catch so one failure never 500s.
     */
    public function get_orders_insights($start_date = null, $end_date = null) {
        $statuses = array_keys($this->get_wc_order_statuses());
        if (empty($statuses)) {
            return $this->empty_insights();
        }

        $prefix = $this->wpdb->prefix;
        $hpos = class_exists('\Automattic\WooCommerce\Utilities\OrderUtil')
            && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();

        $offset_min = (int) round(((float) get_option('gmt_offset', 0)) * 60);

        if ($hpos) {
            $from        = "{$prefix}wc_orders o";
            $status_col  = 'o.status';
            $local       = "DATE_ADD(o.date_created_gmt, INTERVAL {$offset_min} MINUTE)";
            $total       = 'o.total_amount';
            $payment     = "COALESCE(NULLIF(o.payment_method, ''), 'unknown')";
            $base        = "o.type = 'shop_order'";
            $range_col   = 'o.date_created_gmt';
        } else {
            $from        = "{$prefix}posts o
                            LEFT JOIN {$prefix}postmeta mt ON mt.post_id = o.ID AND mt.meta_key = '_order_total'
                            LEFT JOIN {$prefix}postmeta mp ON mp.post_id = o.ID AND mp.meta_key = '_payment_method'";
            $status_col  = 'o.post_status';
            $local       = 'o.post_date';
            $total       = 'CAST(mt.meta_value AS DECIMAL(18,2))';
            $payment     = "COALESCE(NULLIF(mp.meta_value, ''), 'unknown')";
            $base        = "o.post_type = 'shop_order'";
            $range_col   = 'o.post_date_gmt';
        }

        $in = implode(',', array_fill(0, count($statuses), '%s'));

        $range_sql = '';
        $date_params = [];
        if ($start_date && $end_date) {
            $range_sql = " AND {$range_col} BETWEEN %s AND %s";
            $date_params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
        }

        $where  = "WHERE {$base} AND {$status_col} IN ({$in}){$range_sql}";
        $params = array_merge($statuses, $date_params);

        $insights = $this->empty_insights();

        // 1) Per status: count + revenue -> KPIs + status breakdown
        try {
            $rows = $this->wpdb->get_results($this->wpdb->prepare(
                "SELECT {$status_col} AS k, COUNT(*) AS cnt, SUM({$total}) AS rev FROM {$from} {$where} GROUP BY {$status_col}",
                $params
            ));
            $breakdown = []; $by = []; $total_orders = 0; $total_rev = 0.0; $c_cnt = 0; $c_rev = 0.0;
            foreach ((array) $rows as $r) {
                $cnt = (int) $r->cnt; $rev = (float) $r->rev;
                $breakdown[] = ['status' => $r->k, 'count' => $cnt, 'revenue' => round($rev, 2)];
                $by[$r->k] = $cnt;
                $total_orders += $cnt; $total_rev += $rev;
                if ($r->k === 'wc-completed') { $c_cnt = $cnt; $c_rev = $rev; }
            }
            usort($breakdown, function ($a, $b) { return $b['count'] - $a['count']; });
            $insights['status_breakdown'] = $breakdown;
            $insights['kpis'] = [
                'total_orders'         => $total_orders,
                'completed_orders'     => $c_cnt,
                'completed_revenue'    => round($c_rev, 2),
                'total_revenue'        => round($total_rev, 2),
                'avg_order_value'      => $total_orders > 0 ? round($total_rev / $total_orders, 2) : 0,
                'completed_percentage' => $total_orders > 0 ? round($c_cnt / $total_orders * 100, 1) : 0,
                'pending_orders'       => isset($by['wc-pending']) ? $by['wc-pending'] : 0,
                'cancelled_orders'     => isset($by['wc-cancelled']) ? $by['wc-cancelled'] : 0,
                'refunded_orders'      => isset($by['wc-refunded']) ? $by['wc-refunded'] : 0,
            ];
        } catch (\Throwable $e) {}

        // 2) Payment methods
        try {
            $rows = $this->wpdb->get_results($this->wpdb->prepare(
                "SELECT {$payment} AS k, COUNT(*) AS cnt, SUM({$total}) AS rev FROM {$from} {$where} GROUP BY {$payment}",
                $params
            ));
            $pm = []; $tot = 0;
            foreach ((array) $rows as $r) { $tot += (int) $r->cnt; }
            foreach ((array) $rows as $r) {
                $cnt = (int) $r->cnt; $rev = round((float) $r->rev, 2);
                $pm[] = [
                    'payment_method'  => $r->k,
                    'count'           => $cnt,
                    'revenue'         => $rev,
                    'percentage'      => $tot > 0 ? round($cnt / $tot * 100, 1) : 0,
                    'avg_order_value' => $cnt > 0 ? round($rev / $cnt, 2) : 0,
                ];
            }
            usort($pm, function ($a, $b) { return $b['count'] - $a['count']; });
            $insights['payment_methods'] = $pm;
        } catch (\Throwable $e) {}

        // 3) Order value buckets
        try {
            $bucket = "CASE WHEN {$total} < 50 THEN 0 WHEN {$total} < 100 THEN 1 WHEN {$total} < 200 THEN 2 WHEN {$total} < 500 THEN 3 ELSE 4 END";
            $rows = $this->wpdb->get_results($this->wpdb->prepare(
                "SELECT {$bucket} AS b, COUNT(*) AS cnt, SUM({$total}) AS rev FROM {$from} {$where} GROUP BY b",
                $params
            ));
            $labels = ['0–49 €', '50–99 €', '100–199 €', '200–499 €', '500 €+'];
            $buckets = [];
            foreach ($labels as $i => $label) { $buckets[$i] = ['label' => $label, 'count' => 0, 'revenue' => 0]; }
            foreach ((array) $rows as $r) {
                $i = (int) $r->b;
                if (isset($buckets[$i])) { $buckets[$i]['count'] = (int) $r->cnt; $buckets[$i]['revenue'] = round((float) $r->rev, 2); }
            }
            $insights['value_buckets'] = array_values($buckets);
        } catch (\Throwable $e) {}

        // 4) Weekday x hour heatmap (+ derived by-hour / by-weekday)
        try {
            $rows = $this->wpdb->get_results($this->wpdb->prepare(
                "SELECT DAYOFWEEK({$local}) AS wd, HOUR({$local}) AS hr, COUNT(*) AS cnt FROM {$from} {$where} GROUP BY wd, hr",
                $params
            ));
            $wh = []; $by_hour = array_fill(0, 24, 0); $by_wd = array_fill(1, 7, 0);
            foreach ((array) $rows as $r) {
                $wd = (int) $r->wd; $hr = (int) $r->hr; $cnt = (int) $r->cnt;
                $wh[] = ['weekday' => $wd, 'hour' => $hr, 'count' => $cnt];
                if ($hr >= 0 && $hr < 24) { $by_hour[$hr] += $cnt; }
                if ($wd >= 1 && $wd <= 7) { $by_wd[$wd] += $cnt; }
            }
            $insights['weekday_hour'] = $wh;
            $insights['by_hour'] = array_map(function ($h) use ($by_hour) { return ['hour' => $h, 'count' => $by_hour[$h]]; }, array_keys($by_hour));
            $insights['by_weekday'] = array_map(function ($d) use ($by_wd) { return ['weekday' => $d, 'count' => $by_wd[$d]]; }, array_keys($by_wd));
        } catch (\Throwable $e) {}

        // 5) Monthly trend (all months in range)
        try {
            $rows = $this->wpdb->get_results($this->wpdb->prepare(
                "SELECT DATE_FORMAT({$local}, '%%Y-%%m') AS ym, COUNT(*) AS cnt, SUM({$total}) AS rev FROM {$from} {$where} GROUP BY ym ORDER BY ym",
                $params
            ));
            $monthly = [];
            foreach ((array) $rows as $r) { $monthly[] = ['month' => $r->ym, 'count' => (int) $r->cnt, 'revenue' => round((float) $r->rev, 2)]; }
            $insights['monthly'] = $monthly;
        } catch (\Throwable $e) {}

        // 6) Daily trend (capped to last 90 days for all-time to stay readable)
        try {
            $daily_where = $where; $daily_params = $params;
            if (!$start_date || !$end_date) {
                $daily_where .= " AND {$range_col} >= %s";
                $daily_params = array_merge($params, [date('Y-m-d', strtotime('-89 days')) . ' 00:00:00']);
            }
            $rows = $this->wpdb->get_results($this->wpdb->prepare(
                "SELECT DATE({$local}) AS d, COUNT(*) AS cnt, SUM({$total}) AS rev FROM {$from} {$daily_where} GROUP BY d ORDER BY d",
                $daily_params
            ));
            $daily = [];
            foreach ((array) $rows as $r) { $daily[] = ['date' => $r->d, 'count' => (int) $r->cnt, 'revenue' => round((float) $r->rev, 2)]; }
            $insights['daily'] = $daily;
        } catch (\Throwable $e) {}

        return $insights;
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
        
        
        foreach ($this->iterate_orders($args) as $order) {
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
        
        
        foreach ($this->iterate_orders($args) as $order) {
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
        
        
        $monthly_data = [];
        
        foreach ($this->iterate_orders($args) as $order) {
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
        
        
        $products_data = [];
        
        foreach ($this->iterate_orders($args) as $order) {
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
        
        
        $payment_methods = [];
        $total_orders = 0;
        
        foreach ($this->iterate_orders($args) as $order) {
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
        
        
        $customers = [];
        
        foreach ($this->iterate_orders($args) as $order) {
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
        
        
        $status_data = [];
        
        foreach ($this->iterate_orders($args) as $order) {
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
        
        
        $heatmap_data = [];
        
        foreach ($this->iterate_orders($args) as $order) {
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

        foreach ($this->iterate_orders($args) as $order) {
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