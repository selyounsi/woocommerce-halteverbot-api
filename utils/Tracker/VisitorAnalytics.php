<?php

namespace Utils\Tracker;

use Utils\Tracker\Traits\ChartDataTrait;
use Utils\Tracker\Traits\BasicAnalyticsTrait;
use Utils\Tracker\Traits\GlobalAnalyticsTrait;
use Utils\Tracker\Traits\PageAnalyticsTrait;
use Utils\Tracker\Traits\DeviceAnalyticsTrait;
use Utils\Tracker\Traits\VisitorAnalyticsTrait;
use Utils\Tracker\Traits\WooCommerceDataTrait;
use Utils\Tracker\Traits\GoogleSearchConsoleTrait;

class VisitorAnalytics extends VisitorTracker 
{
    use BasicAnalyticsTrait,
        GlobalAnalyticsTrait,
        PageAnalyticsTrait, 
        DeviceAnalyticsTrait, 
        VisitorAnalyticsTrait,
        WooCommerceDataTrait,
        GoogleSearchConsoleTrait,
        ChartDataTrait;

    private static $analytics_instance = null;
    private $gsc;

    public static function getAnalyticsInstance(): self {
        if (self::$analytics_instance === null) {
            self::$analytics_instance = new self();
        }
        return self::$analytics_instance;
    }

    public function __construct() {
        parent::__construct(); 
        $this->gsc = \Utils\Tracker\Google\GoogleSearchConsole::getInstance();
    }

    public function get_report_today(): array {
        $today = date('Y-m-d');
        return $this->get_report($today, $today);
    }

    public function get_report_yesterday(): array {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        return $this->get_report($yesterday, $yesterday);
    }

    public function get_report_this_week(): array {
        $monday = date('Y-m-d', strtotime('monday this week'));
        $sunday = date('Y-m-d', strtotime('sunday this week'));
        return $this->get_report($monday, $sunday);
    }

    public function get_report_this_month(): array {
        $firstDay = date('Y-m-01');
        $lastDay  = date('Y-m-t');
        return $this->get_report($firstDay, $lastDay);
    }

    public function get_report_this_year(): array {
        $firstDay = date('Y-01-01');
        $lastDay  = date('Y-12-31');
        return $this->get_report($firstDay, $lastDay);
    }

    public function get_report($start_date, $end_date): array {
        return [
            'total_visits' => $this->visitors_by_period($start_date, $end_date),
            'visitors' => [
                'today' => $this->visitors_today(),
                'yesterday' => $this->visitors_yesterday(),
                'this_week' => $this->visitors_this_week(),
                'this_month' => $this->visitors_this_month(),
                'last_month' => $this->visitors_last_month(),
                'this_year' => $this->visitors_this_year()
            ],
            'session_metrics' => [
                'avg_duration' => $this->get_avg_session_duration_by_period($start_date, $end_date),
                'avg_pages' => $this->get_avg_pages_per_session_by_period($start_date, $end_date),
                'bounce_rate' => $this->get_bounce_rate_by_period($start_date, $end_date),
                'avg_time_on_page' => $this->get_avg_time_on_page_by_period($start_date, $end_date)
            ],
            'chart_data' => [
                'daily_visitors_30d' => $this->get_daily_visitors_chart_data(30),
                'daily_visitors_7d' => $this->get_daily_visitors_chart_data(7),
                'device_distribution' => $this->get_device_distribution_chart($start_date, $end_date),
                'browser_distribution' => $this->get_browser_distribution_chart($start_date, $end_date),
                'traffic_sources' => $this->get_traffic_sources_chart($start_date, $end_date),
                'visit_heatmap' => $this->get_visit_heatmap_data($start_date, $end_date),
                'german_cities' => $this->get_german_cities_chart($start_date, $end_date),
                'page_performance' => $this->get_page_performance_chart($start_date, $end_date)
            ],            
            'entry_pages' => $this->entry_pages_by_period($start_date, $end_date, 10),
            'exit_pages' => $this->exit_pages_by_period($start_date, $end_date, 10),
            'exit_rates' => $this->exit_rates_by_period($start_date, $end_date, 10),
            'devices' => $this->get_devices_by_period($start_date, $end_date),
            'device_types' => $this->get_device_types_by_period($start_date, $end_date),
            'device_brands' => $this->get_device_brands_by_period($start_date, $end_date),
            'browsers' => $this->get_browsers_by_period($start_date, $end_date),
            'countries' => $this->get_countries_by_period($start_date, $end_date),
            'traffic_sources' => $this->get_traffic_sources_by_period($start_date, $end_date),
            'pages' => $this->get_pages_by_period($start_date, $end_date),
            'search_engines' => $this->get_search_engines_by_period($start_date, $end_date),
            'social_networks' => $this->get_social_networks_by_period($start_date, $end_date),
            'traffic_channels' => $this->get_traffic_channels_by_period($start_date, $end_date),
            'operating_systems' => $this->get_operating_systems_by_period($start_date, $end_date),
            'visitor_types' => $this->get_visitor_types_by_period($start_date, $end_date),
            'visit_times' => $this->get_visit_times_by_period($start_date, $end_date),
            'screen_resolutions' => $this->get_screen_resolutions_by_period($start_date, $end_date),
            'languages' => $this->get_languages_by_period($start_date, $end_date),
            'keywords' => $this->get_keywords_by_period($start_date, $end_date),
            'cities' => $this->get_cities_by_period($start_date, $end_date),
            'gsc_keywords' => $this->get_gsc_keywords_16_months(), 
            'wc_metrics' => [
                'events' => $this->get_wc_events_by_period($start_date, $end_date),
                
                // Aktuelle Periode
                'current_period' => [
                    'conversion_rate' => $this->get_wc_conversion_rate_by_period($start_date, $end_date),
                    'revenue' => $this->wc_revenue_by_period($start_date, $end_date),
                    'confirmed_revenue' => $this->wc_confirmed_revenue_by_period($start_date, $end_date),
                    'average_order_value' => $this->get_average_order_value($start_date, $end_date),
                    'total_orders' => $this->get_total_orders($start_date, $end_date),
                    'unique_customers' => $this->get_unique_customers($start_date, $end_date),
                    'contact_conversions' => $this->get_contact_conversions($start_date, $end_date) // NEU
                ],
                
                // Letzte 7 Tage
                'last_7_days' => [
                    'conversion_rate' => $this->get_wc_conversion_rate_by_period(date('Y-m-d', strtotime('-7 days')), $end_date),
                    'revenue' => $this->wc_revenue_by_period(date('Y-m-d', strtotime('-7 days')), $end_date),
                    'confirmed_revenue' => $this->wc_confirmed_revenue_by_period(date('Y-m-d', strtotime('-7 days')), $end_date),
                    'average_order_value' => $this->get_average_order_value(date('Y-m-d', strtotime('-7 days')), $end_date),
                    'total_orders' => $this->get_total_orders(date('Y-m-d', strtotime('-7 days')), $end_date),
                    'contact_conversions' => $this->get_contact_conversions(date('Y-m-d', strtotime('-7 days')), $end_date), // NEU
                    'daily_data' => $this->get_daily_wc_events_for_days(7)
                ],
                
                // Letzte 30 Tage
                'last_30_days' => [
                    'conversion_rate' => $this->get_wc_conversion_rate_by_period(date('Y-m-d', strtotime('-30 days')), $end_date),
                    'revenue' => $this->wc_revenue_by_period(date('Y-m-d', strtotime('-30 days')), $end_date),
                    'confirmed_revenue' => $this->wc_confirmed_revenue_by_period(date('Y-m-d', strtotime('-30 days')), $end_date),
                    'average_order_value' => $this->get_average_order_value(date('Y-m-d', strtotime('-30 days')), $end_date),
                    'total_orders' => $this->get_total_orders(date('Y-m-d', strtotime('-30 days')), $end_date),
                    'contact_conversions' => $this->get_contact_conversions(date('Y-m-d', strtotime('-30 days')), $end_date), // NEU
                    'daily_data' => $this->get_daily_wc_events_for_days(30)
                ],
                
                // Erweiterter Funnel
                'funnel' => [
                    'view_to_cart' => $this->get_funnel_rate('product_view', 'add_to_cart', $start_date, $end_date),
                    'cart_to_checkout' => $this->get_funnel_rate('add_to_cart', 'checkout_start', $start_date, $end_date),
                    'checkout_to_order' => $this->get_funnel_rate('checkout_start', 'order_complete', $start_date, $end_date),
                    'cart_abandonment_rate' => $this->get_cart_abandonment_rate($start_date, $end_date),
                    'contact_engagement' => $this->get_contact_engagement_rate($start_date, $end_date) // NEU
                ],
                
                // Kunden Metriken
                'customer_metrics' => [
                    'session_analysis' => $this->get_session_analysis($start_date, $end_date),
                    'conversion_breakdown' => [
                        'online_orders' => $this->get_online_orders($start_date, $end_date),
                        'contact_leads' => $this->get_contact_leads($start_date, $end_date),
                        'total_conversions' => $this->get_online_orders($start_date, $end_date) + $this->get_contact_leads($start_date, $end_date),
                        'high_value_sessions' => $this->get_high_value_sessions($start_date, $end_date)
                    ]
                ],
                
                // Geräte Performance
                'device_performance' => [
                    'desktop' => $this->get_conversion_rate_by_device('desktop', $start_date, $end_date),
                    'mobile' => $this->get_conversion_rate_by_device('mobile', $start_date, $end_date),
                    'tablet' => $this->get_conversion_rate_by_device('tablet', $start_date, $end_date)
                ],
                
                // Top Produkte
                'top_products' => $this->get_top_products_by_revenue($start_date, $end_date, 10),
                
                // NEUE: Engagement Metriken
                'engagement_metrics' => [
                    'contact_events' => $this->get_contact_events_by_period($start_date, $end_date),
                    'search_analytics' => $this->get_gsc_keywords_by_period($start_date, $end_date, 10),
                    'category_engagement' => $this->get_category_engagement($start_date, $end_date),
                    'wishlist_activity' => $this->get_wishlist_activity($start_date, $end_date)
                ],
                
                // NEUE: Conversion Quellen
                'conversion_sources' => [
                    'online_orders' => $this->get_conversion_source_rate('order_complete', $start_date, $end_date),
                    'contact_leads' => $this->get_conversion_source_rate('phone_click,email_click', $start_date, $end_date),
                    'search_leads' => $this->get_conversion_source_rate('product_search', $start_date, $end_date)
                ]
            ]
        ];
    }
}