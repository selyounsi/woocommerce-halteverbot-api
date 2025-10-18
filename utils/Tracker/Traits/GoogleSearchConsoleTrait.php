<?php

namespace Utils\Tracker\Traits;

trait GoogleSearchConsoleTrait 
{
    /**
     * Basisfunktion: Holt Keyword-Daten aus der Search Console
     */
    private function get_gsc_keywords_by_period($start_date, $end_date, $limit = 30) {
        try {
            if (!$this->gsc->isAuthenticated() || !$this->gsc->getPrimaryDomain()) {
                return [];
            }

            $payload = [
                'startDate' => $start_date,
                'endDate' => $end_date,
                'dimensions' => ['query'],
                'rowLimit' => $limit,
                'startRow' => 0,
                'orderBy' => [
                    [
                        'dimension' => 'CLICKS',
                        'sortOrder' => 'DESCENDING'
                    ]
                ]
            ];

            $result = $this->gsc->getSearchAnalyticsData($payload);

            if ($result['success'] && !empty($result['data'])) {
                return array_map(function ($row) {
                    return [
                        'search_term' => $row['keys'][0] ?? 'N/A',
                        'clicks' => $row['clicks'] ?? 0,
                        'impressions' => $row['impressions'] ?? 0,
                        'position' => round($row['position'] ?? 0, 2),
                        'ctr' => round(($row['ctr'] ?? 0) * 100, 2)
                    ];
                }, $result['data']);
            }

            return [];
        } catch (\Exception $e) {
            error_log('GSC Keywords Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Hilfsfunktion: Holt Daten für aktuellen & vorherigen Zeitraum
     */
    private function get_gsc_comparison_data($start_date, $end_date) {
        $days = (strtotime($end_date) - strtotime($start_date)) / 86400;
        $prev_end = date('Y-m-d', strtotime($start_date . ' -1 day'));
        $prev_start = date('Y-m-d', strtotime($prev_end . " -{$days} days"));

        $current = $this->get_gsc_keywords_by_period($start_date, $end_date, 250);
        $previous = $this->get_gsc_keywords_by_period($prev_start, $prev_end, 250);

        $prev_index = [];
        foreach ($previous as $row) {
            $prev_index[$row['search_term']] = $row;
        }

        $merged = [];
        foreach ($current as $row) {
            $term = $row['search_term'];
            $last = $prev_index[$term] ?? null;
            
            $change = null;
            $status = 'new'; // Standard: neues Keyword
            
            if ($last) {
                $change = round(($last['position'] - $row['position']), 2);
                
                // Status bestimmen
                if ($change > 0) {
                    $status = 'improved';
                } elseif ($change < 0) {
                    $status = 'declined';
                } else {
                    $status = 'stable';
                }
            }

            $merged[] = [
                'search_term' => $term,
                'clicks' => $row['clicks'],
                'impressions' => $row['impressions'],
                'position' => $row['position'],
                'last_position' => $last['position'] ?? null,
                'change' => $change,
                'status' => $status // Neu: improved, declined, stable, new
            ];
        }

        // Neue Keywords erkennen (nicht im vorherigen Zeitraum vorhanden)
        $new_keywords = array_map(function($row) {
            return [
                'search_term' => $row['search_term'],
                'clicks' => $row['clicks'],
                'impressions' => $row['impressions'],
                'position' => $row['position'],
                'last_position' => null,
                'change' => null,
                'status' => 'new'
            ];
        }, array_filter($merged, fn($row) => $row['last_position'] === null));

        return [
            'current' => $merged,
            'new' => array_values($new_keywords)
        ];
    }

    /**
     * Gibt alle Keyword-Kategorien mit Vergleichsdaten zurück
     */
    public function get_gsc_keywords_with_comparison($start_date, $end_date) {
        $comparison_data = $this->get_gsc_comparison_data($start_date, $end_date);
        $current_data = $comparison_data['current'];
        $new_data = $comparison_data['new'];
        
        // Top Keywords (nach Klicks)
        usort($current_data, fn($a, $b) => $b['clicks'] <=> $a['clicks']);
        $top_keywords = array_slice($current_data, 0, 10);
        
        // Winner Keywords (Position verbessert)
        $winners = array_filter($current_data, fn($row) => $row['change'] !== null && $row['change'] > 0);
        usort($winners, fn($a, $b) => $b['change'] <=> $a['change']);
        $winner_keywords = array_slice($winners, 0, 10);
        
        // Loser Keywords (Position verschlechtert)
        $losers = array_filter($current_data, fn($row) => $row['change'] !== null && $row['change'] < 0);
        usort($losers, fn($a, $b) => $a['change'] <=> $b['change']);
        $loser_keywords = array_slice($losers, 0, 10);
        
        // New Keywords (nach Impressionen)
        usort($new_data, fn($a, $b) => $b['impressions'] <=> $a['impressions']);
        $new_keywords = array_slice($new_data, 0, 10);
        
        return [
            'top_keywords' => $top_keywords,
            'winner_keywords' => $winner_keywords,
            'loser_keywords' => $loser_keywords,
            'new_keywords' => $new_keywords,
            'comparison_data' => $comparison_data,
            'period' => [
                'start_date' => $start_date,
                'end_date' => $end_date
            ]
        ];
    }

    public function get_gsc_top_keywords($start_date, $end_date) {
        $data = $this->get_gsc_keywords_with_comparison($start_date, $end_date);
        return $data['top_keywords'];
    }

    public function get_gsc_winner_keywords($start_date, $end_date) {
        $data = $this->get_gsc_keywords_with_comparison($start_date, $end_date);
        return $data['winner_keywords'];
    }

    public function get_gsc_loser_keywords($start_date, $end_date) {
        $data = $this->get_gsc_keywords_with_comparison($start_date, $end_date);
        return $data['loser_keywords'];
    }

    public function get_gsc_new_keywords($start_date, $end_date) {
        $data = $this->get_gsc_keywords_with_comparison($start_date, $end_date);
        return $data['new_keywords'];
    }
}
