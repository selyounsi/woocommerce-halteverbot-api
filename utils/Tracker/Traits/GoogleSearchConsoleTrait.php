<?php

namespace Utils\Tracker\Traits;

trait GoogleSearchConsoleTrait 
{
    private function get_gsc_keywords_16_months() {
        $end_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-16 months'));
        
        return $this->get_gsc_keywords_by_period($start_date, $end_date);
    }

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
                'orderBy' => [
                    [
                        'dimension' => 'CLICKS',
                        'sortOrder' => 'DESCENDING'
                    ]
                ]
            ];
            
            $result = $this->gsc->getSearchAnalyticsData($payload);
            
            if ($result['success'] && !empty($result['data'])) {
                $totalClicks = array_sum(array_column($result['data'], 'clicks'));
                
                return array_map(function($row) use ($totalClicks) {
                    $clicks = $row['clicks'] ?? 0;
                    $percentage = $totalClicks > 0 ? round(($clicks / $totalClicks) * 100, 1) : 0;
                    
                    return [
                        'keywords' => $row['keys'][0] ?? 'N/A',
                        'count' => $clicks,
                        'percentage' => $percentage
                    ];
                }, $result['data']);
            }
            
            return [];
            
        } catch (\Exception $e) {
            error_log('GSC Keywords Error: ' . $e->getMessage());
            return [];
        }
    }
}