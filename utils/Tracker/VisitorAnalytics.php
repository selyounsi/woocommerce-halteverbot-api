<?php

namespace Utils\Tracker;

use Utils\Tracker\Traits\ChartDataTrait;
use Utils\Tracker\Traits\ReviewsDataTrait;
use Utils\Tracker\Traits\BasicAnalyticsTrait;
use Utils\Tracker\Traits\GlobalAnalyticsTrait;
use Utils\Tracker\Traits\PageAnalyticsTrait;
use Utils\Tracker\Traits\DeviceAnalyticsTrait;
use Utils\Tracker\Traits\VisitorAnalyticsTrait;
use Utils\Tracker\Traits\WooCommerceDataTrait;
use Utils\Tracker\Traits\GoogleSearchConsoleTrait;
use Utils\Tracker\Traits\OrderAnalyticsTrait;

class VisitorAnalytics extends VisitorTracker 
{
    use BasicAnalyticsTrait,
        GlobalAnalyticsTrait,
        PageAnalyticsTrait, 
        DeviceAnalyticsTrait, 
        VisitorAnalyticsTrait,
        WooCommerceDataTrait,
        GoogleSearchConsoleTrait,
        ReviewsDataTrait,
        OrderAnalyticsTrait,
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
            'visitor_metrics' => [
                'today' => $this->visitors_today(),
                'yesterday' => $this->visitors_yesterday(),
                'this_week' => $this->visitors_this_week(),
                'this_month' => $this->visitors_this_month(),
                'last_month' => $this->visitors_last_month(),
                'this_year' => $this->visitors_this_year(),
                'total_visits' => $this->visitors_by_period($start_date, $end_date),
                'traffic_channels' => $this->get_traffic_channels_by_period($start_date, $end_date),
                'visitor_types' => $this->get_visitor_types_by_period($start_date, $end_date),
                'chart_data' => [
                    'page_performance' => $this->get_page_performance_chart($start_date, $end_date),
                    'german_cities' => $this->get_german_cities_chart($start_date, $end_date),
                    'traffic_sources' => $this->get_traffic_sources_by_period($start_date, $end_date),
                    'daily_visitors_30d' => $this->get_daily_visitors_chart_data(30),
                    'daily_visitors_7d' => $this->get_daily_visitors_chart_data(7),
                    'device_distribution' => $this->get_device_distribution_chart($start_date, $end_date),
                    'browser_distribution' => $this->get_browser_distribution_chart($start_date, $end_date),
                    'search_engine_distribution' => $this->get_search_engine_distribution_chart($start_date, $end_date),
                    'visit_heatmap' => $this->get_visit_heatmap_data($start_date, $end_date),
                ]
            ],
            'session_metrics' => [
                'avg_duration' => $this->get_avg_session_duration_by_period($start_date, $end_date),
                'avg_pages' => $this->get_avg_pages_per_session_by_period($start_date, $end_date),
                'bounce_rate' => $this->get_bounce_rate_by_period($start_date, $end_date),
                'avg_time_on_page' => $this->get_avg_time_on_page_by_period($start_date, $end_date)
            ],
            'reviews_metrics' => [
                'stats' => $this->get_reviews_stats($start_date, $end_date),
                'sources' => $this->get_reviews_by_source($start_date, $end_date),
                'top_sources' => $this->get_top_review_sources($start_date, $end_date, 8),
                'rating_distribution' => $this->get_rating_distribution_chart($start_date, $end_date),
                'monthly_trends' => $this->get_monthly_reviews_chart_data($start_date, $end_date, 6),
                'daily_data' => $this->get_daily_reviews_chart_data($start_date, $end_date, 30),
                'top_reviews' => $this->get_top_reviews($start_date, $end_date, 5),
                'rating_trends' => $this->get_rating_trends_chart($start_date, $end_date, 6),
                'chart_data' => [
                    'reviews_daily_30d' => $this->get_daily_reviews_30d(),
                    'reviews_daily_7d' => $this->get_daily_reviews_7d(),
                    'reviews_monthly_12m' => $this->get_monthly_reviews_12m(),
                    'rating_trends_6m' => $this->get_rating_trends_6m(),
                    'rating_distribution' => $this->get_rating_distribution_chart($start_date, $end_date),
                ]
            ],
            'order_metrics' => [
                'current_period' => [
                    'stats' => $this->get_order_stats($start_date, $end_date),
                    'status_distribution' => $this->get_order_status_distribution($start_date, $end_date),
                    'sources' => $this->get_order_sources($start_date, $end_date),
                    'top_products' => $this->get_top_products_by_revenue($start_date, $end_date, 10),
                    'customer_repeat' => $this->get_customer_repeat_rate($start_date, $end_date),
                    'avg_values' => $this->get_avg_order_value_by_status($start_date, $end_date)
                ],
                'last_7_days' => [
                    'stats' => $this->get_order_stats_7d(),
                    'status_distribution' => $this->get_order_status_distribution(
                        date('Y-m-d', strtotime('-6 days')), date('Y-m-d')
                    )
                ],
                'last_30_days' => [
                    'stats' => $this->get_order_stats_30d(),
                    'status_distribution' => $this->get_order_status_distribution(
                        date('Y-m-d', strtotime('-29 days')), date('Y-m-d')
                    )
                ],
                'chart_data' => [
                    'orders_daily_30d' => $this->get_daily_orders_30d(),
                    'orders_daily_7d' => $this->get_daily_orders_7d(),
                    'orders_monthly_12m' => $this->get_monthly_orders_12m(),
                    'order_status_distribution' => $this->get_order_status_distribution($start_date, $end_date),
                    'order_sources' => $this->get_order_sources($start_date, $end_date),
                    'order_time_heatmap' => $this->get_order_time_heatmap($start_date, $end_date),
                    'top_products' => $this->get_top_products_by_revenue($start_date, $end_date, 10)
                ]
            ],
            'page_metrics' => [
                'pages' => $this->get_pages_by_period($start_date, $end_date),
                'entry_pages' => $this->entry_pages_by_period($start_date, $end_date, 10),
                'exit_pages' => $this->exit_pages_by_period($start_date, $end_date, 10),
                'exit_rates' => $this->exit_rates_by_period($start_date, $end_date, 10),
                'page_categories' => $this->page_performance_by_category($start_date, $end_date),
                'woo_commerce_pages' => $this->woo_commerce_pages_performance($start_date, $end_date),
                'product_pages' => $this->product_pages_performance($start_date, $end_date),
                'category_pages' => $this->category_pages_performance($start_date, $end_date),
                'ecommerce_funnel' => $this->ecommerce_funnel_analysis($start_date, $end_date),
                'detailed_performance' => $this->detailed_page_performance($start_date, $end_date, 15),
                'page_flow' => $this->page_flow_analysis($start_date, $end_date, 8),
                'engagement_metrics' => $this->page_engagement_metrics($start_date, $end_date),
                'chart_data' => [
                    'page_performance' => $this->get_page_performance_chart_data($start_date, $end_date),
                    'engagement' => $this->get_engagement_chart_data($start_date, $end_date),
                    'traffic_flow' => $this->get_traffic_flow_chart_data($start_date, $end_date),
                    'exit_rates' => $this->get_exit_rates_chart_data($start_date, $end_date),
                    'page_comparison' => $this->get_page_comparison_chart_data($start_date, $end_date),
                    'ecommerce_funnel' => $this->get_ecommerce_funnel_chart_data($start_date, $end_date)
                ]
            ],
            'misc_metrics' => [
                'screen_resolutions' => $this->get_screen_resolutions_by_period($start_date, $end_date),
                'languages' => $this->get_languages_by_period($start_date, $end_date),
                'visit_times' => $this->get_visit_times_by_period($start_date, $end_date)
            ],
            'devices_metrics' => [
                'devices' => $this->get_devices_by_period($start_date, $end_date),
                'device_types' => $this->get_device_types_by_period($start_date, $end_date),
                'device_brands' => $this->get_device_brands_by_period($start_date, $end_date)
            ],
            'geo_metrics' => [
                'countries' => $this->get_countries_by_period($start_date, $end_date),
                'cities' => $this->get_cities_by_period($start_date, $end_date),
                'operating_systems' => $this->get_operating_systems_by_period($start_date, $end_date),
                'browsers' => $this->get_browsers_by_period($start_date, $end_date),
            ],
            'traffic_metrics' => [
                'gsc_keywords' => $this->get_gsc_keywords_by_period($start_date, $end_date, 30),
                'social_networks' => $this->get_social_networks_by_period($start_date, $end_date),
                'search_engines' => $this->get_search_engines_by_period($start_date, $end_date)
            ],
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