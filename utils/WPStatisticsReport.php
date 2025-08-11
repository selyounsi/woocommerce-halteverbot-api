<?php

namespace Utils;

class WPStatisticsReport
{
    private $wpdb;
    private $visitorTable;
    private $pageTable;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $prefix = $wpdb->prefix;

        $this->visitorTable = "{$prefix}statistics_visitor";
        $this->pageTable    = "{$prefix}statistics_pages";
    }

    /**
     * Interne Methode zum Berechnen von Prozentanteilen
     */
    private function addPercentage($items, $total)
    {
        foreach ($items as &$item) {
            $item['percent'] = $total > 0 ? round(($item['count'] / $total) * 100, 2) : 0.0;
        }
        return $items;
    }

    /**
     * Holt Gesamtanzahl Besucher für Zeitraum
     */
    private function getTotalVisitors($from, $to)
    {
        $fromDate = date('Y-m-d 00:00:00', strtotime($from));
        $toDate   = date('Y-m-d 23:59:59', strtotime($to));

        return (int) $this->wpdb->get_var($this->wpdb->prepare("
            SELECT COUNT(*) FROM {$this->visitorTable}
            WHERE last_counter BETWEEN %s AND %s
        ", $fromDate, $toDate));
    }

    /**
     * Holt Gesamtanzahl Seitenaufrufe für Zeitraum
     */
    private function getTotalPageViews($from, $to)
    {
        $fromDate = date('Y-m-d 00:00:00', strtotime($from));
        $toDate   = date('Y-m-d 23:59:59', strtotime($to));

        return (int) $this->wpdb->get_var($this->wpdb->prepare("
            SELECT COUNT(*) FROM {$this->pageTable}
            WHERE date BETWEEN %s AND %s
        ", $fromDate, $toDate));
    }

    /**
     * Allgemeine Top-Liste aus visitor-Tabelle + Prozent
     */
    private function getTopFromVisitors($column, $from, $to, $limit = 10)
    {
        $fromDate = date('Y-m-d 00:00:00', strtotime($from));
        $toDate   = date('Y-m-d 23:59:59', strtotime($to));
        $column   = esc_sql($column);

        $total = $this->getTotalVisitors($from, $to);

        $data = $this->wpdb->get_results($this->wpdb->prepare("
            SELECT {$column} AS value, COUNT(*) AS count
            FROM {$this->visitorTable}
            WHERE last_counter BETWEEN %s AND %s
            GROUP BY {$column}
            ORDER BY count DESC
            LIMIT %d
        ", $fromDate, $toDate, $limit), ARRAY_A);

        return $this->addPercentage($data, $total);
    }

    /**
     * Top Seiten mit Prozentanteil
     */
    public function getTopPages($from, $to, $limit = 10)
    {
        $fromDate = date('Y-m-d 00:00:00', strtotime($from));
        $toDate   = date('Y-m-d 23:59:59', strtotime($to));

        $total = $this->getTotalPageViews($from, $to);

        $data = $this->wpdb->get_results($this->wpdb->prepare("
            SELECT page AS value, COUNT(*) AS count
            FROM {$this->pageTable}
            WHERE date BETWEEN %s AND %s
            GROUP BY page
            ORDER BY count DESC
            LIMIT %d
        ", $fromDate, $toDate, $limit), ARRAY_A);

        return $this->addPercentage($data, $total);
    }

    /**
     * Kombinierter Top-X Report mit Prozentanteilen
     */
    public function getTopReport($from, $to, $limit = 10)
    {
        return [
            'devices'        => $this->getTopFromVisitors('device', $from, $to, $limit),
            'device_models'  => $this->getTopFromVisitors('model', $from, $to, $limit),        // angepasst auf 'model'
            'browsers'       => $this->getTopFromVisitors('agent', $from, $to, $limit),       // User-Agent (inkl. Browser)
            'os'             => $this->getTopFromVisitors('platform', $from, $to, $limit),    // Betriebssystem
            'pages'          => $this->getTopPages($from, $to, $limit),
            'referrers'      => $this->getTopFromVisitors('referred', $from, $to, $limit),
            'locations'      => $this->getTopFromVisitors('location', $from, $to, $limit),
            'cities'         => $this->getTopFromVisitors('city', $from, $to, $limit),
            'regions'        => $this->getTopFromVisitors('region', $from, $to, $limit),
            'continents'     => $this->getTopFromVisitors('continent', $from, $to, $limit),
            'source_channels'=> $this->getTopFromVisitors('source_channel', $from, $to, $limit),
            'source_names'   => $this->getTopFromVisitors('source_name', $from, $to, $limit),
        ];
    }
}
?>
