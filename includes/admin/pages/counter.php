<?php
if (!is_admin()) {
    return;
}

use Utils\Tracker\Google\GoogleSearchConsole;
use Utils\Tracker\VisitorAnalytics;

$analyticsInstance = VisitorAnalytics::getAnalyticsInstance();

$report = $analyticsInstance->get_report_this_month();


$gsc = GoogleSearchConsole::getInstance();
$status = $gsc->getStatus();

// Formular verarbeiten
if (isset($_POST['save_credentials'])) {
    $clientId = sanitize_text_field($_POST['client_id'] ?? '');
    $clientSecret = sanitize_text_field($_POST['client_secret'] ?? '');
    
    if ($gsc->saveCredentials($clientId, $clientSecret)) {
        echo '<div class="notice notice-success"><p>✅ Client ID und Secret gespeichert!</p></div>';
        $status = $gsc->getStatus();
    }
}

// Primäre Domain setzen
if (isset($_POST['set_primary_domain'])) {
    $primaryDomain = sanitize_text_field($_POST['primary_domain'] ?? '');
    if ($gsc->setPrimaryDomain($primaryDomain)) {
        echo '<div class="notice notice-success"><p>✅ Primäre Domain gesetzt: ' . esc_html($primaryDomain) . '</p></div>';
    }
}

// Reset
if (isset($_POST['reset_all'])) {
    if ($gsc->reset()) {
        echo '<div class="notice notice-success"><p>✅ Alle Daten wurden zurückgesetzt!</p></div>';
        $status = $gsc->getStatus();
    }
}

// OAuth Callback
if (isset($_GET['code'])) {
    $result = $gsc->authenticate($_GET['code']);
    
    if ($result['success']) {
        echo '<div class="notice notice-success"><p>✅ ' . $result['message'] . '</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>❌ ' . $result['error'] . '</p></div>';
    }
    $status = $gsc->getStatus();
}
?>
<div class="wrap">

    <h1 class="wp-heading-inline">Bewertungen</h1>
    <hr class="wp-header-end">

    <h2 class="nav-tab-wrapper" style="margin-bottom: 20px;">
        <a href="#tab-stats" class="nav-tab nav-tab-active">Statistiken</a>
        <a href="#tab-gsc" class="nav-tab">Google Search Console</a>
    </h2>

    <div id="tab-stats" class="tab-content" style="display: block;">


<div class="wp-list-table widefat fixed striped"> 
    <h3>Grundlegende Daten (Diesen Monat)</h3>

    <div class="dashboard-widgets-wrap" style="display: flex; gap: 1rem; flex-wrap:wrap">

        <!-- Besucher im Überblick -->
        <div class="postbox" style="flex: 1;">
            <div class="inside">
                <h2 class="hndle" style="margin-bottom: 10px;"><span>Besucher im Überblick</span></h2>
                <table class="widefat fixed striped">
                    <tbody>
                        <tr><td>Heute</td><td><strong><?php echo $analyticsInstance->visitors_today(); ?></strong></td></tr>
                        <tr><td>Gestern</td><td><strong><?php echo $analyticsInstance->visitors_yesterday(); ?></strong></td></tr>
                        <tr><td>Diese Woche</td><td><strong><?php echo $analyticsInstance->visitors_this_week(); ?></strong></td></tr>
                        <tr><td>Dieser Monat</td><td><strong><?php echo $analyticsInstance->visitors_this_month(); ?></strong></td></tr>
                        <tr><td>Letzter Monat</td><td><strong><?php echo $analyticsInstance->visitors_last_month(); ?></strong></td></tr>
                        <tr><td>Dieses Jahr</td><td><strong><?php echo $analyticsInstance->visitors_this_year(); ?></strong></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Session Metriken -->
        <div class="postbox" style="flex: 1;">
            <div class="inside">
                <h2 class="hndle" style="margin-bottom: 10px;"><span>Session Metriken</span></h2>
                <table class="widefat fixed striped">
                    <tbody>
                        <tr><td>Ø Session-Dauer</td><td><strong><?php echo $report['session_metrics']['avg_duration']; ?>s</strong></td></tr>
                        <tr><td>Ø Seiten/Session</td><td><strong><?php echo $report['session_metrics']['avg_pages']; ?></strong></td></tr>
                        <tr><td>Bounce Rate</td><td><strong><?php echo $report['session_metrics']['bounce_rate']; ?>%</strong></td></tr>
                        <tr><td>Ø Zeit/Seite</td><td><strong><?php echo $report['session_metrics']['avg_time_on_page']; ?>s</strong></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Visitor Types -->
        <div class="postbox" style="flex: 1;">
            <div class="inside">
                <h2 class="hndle" style="margin-bottom: 10px;"><span>Besucher-Typen</span></h2>
                <table class="widefat fixed striped">
                    <tbody>
                        <?php foreach ($report["visitor_types"] as $visitor): ?>
                            <tr>
                                <td><?php echo esc_html($visitor["visitor_type"]); ?></td>
                                <td><strong><?php echo $visitor["count"]; ?></strong></td>
                                <td><?php echo $visitor["percentage"]; ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Traffic Channels -->
        <div class="postbox" style="flex: 1;">
            <div class="inside">
                <h2 class="hndle" style="margin-bottom: 10px;"><span>Traffic-Kanäle</span></h2>
                <table class="widefat fixed striped">
                    <tbody>
                        <?php foreach ($report["traffic_channels"] as $channel): ?>
                            <tr>
                                <td><?php echo esc_html($channel["source_channel"]); ?></td>
                                <td><strong><?php echo $channel["count"]; ?></strong></td>
                                <td><?php echo $channel["percentage"]; ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- WooCommerce Metrics -->
        <div class="postbox" style="width: 100%; flex-basis: 100%">
            <div class="inside">
                <h2 class="hndle" style="margin-bottom: 10px;"><span>WooCommerce Metriken</span></h2>
                
                <!-- WC Overview - Strukturierte Zeiträume -->
                <div style="display: flex; gap: 1rem; margin-bottom: 20px; flex-wrap: wrap;">
                    <!-- Letzte 7 Tage -->
                    <div style="flex: 1; min-width: 160px; text-align: center; padding: 15px; background: #f0f8ff; border-radius: 5px; border-left: 4px solid #2271b1;">
                        <h3 style="margin: 0 0 5px 0; font-size: 14px;">Letzte 7 Tage</h3>
                        <div style="font-size: 20px; font-weight: bold; color: #2271b1;">
                            <?php echo $report['wc_metrics']['last_7_days']['conversion_rate']; ?>%
                        </div>
                        <div style="font-size: 12px; color: #666; margin-top: 5px;">
                            Conversion Rate
                        </div>
                        <div style="font-size: 14px; font-weight: bold; color: #333; margin-top: 8px;">
                            <?php echo number_format($report['wc_metrics']['last_7_days']['revenue'], 2, ',', '.'); ?> €
                        </div>
                        <div style="font-size: 11px; color: #666;">
                            Umsatz
                        </div>
                        <div style="font-size: 12px; color: #666; margin-top: 5px;">
                            Ø <?php echo number_format($report['wc_metrics']['last_7_days']['average_order_value'], 2, ',', '.'); ?> €
                        </div>
                        <div style="font-size: 11px; color: #666;">
                            pro Bestellung
                        </div>
                        <!-- NEU: Kontakt Conversions -->
                        <div style="font-size: 11px; color: #666; margin-top: 5px; border-top: 1px solid #eee; padding-top: 5px;">
                            📞 <?php echo $report['wc_metrics']['last_7_days']['contact_conversions']['phone_clicks'] ?? 0; ?> 
                            ✉️ <?php echo $report['wc_metrics']['last_7_days']['contact_conversions']['email_clicks'] ?? 0; ?>
                        </div>
                    </div>

                    <!-- Letzte 30 Tage -->
                    <div style="flex: 1; min-width: 160px; text-align: center; padding: 15px; background: #f0f8ff; border-radius: 5px; border-left: 4px solid #2271b1;">
                        <h3 style="margin: 0 0 5px 0; font-size: 14px;">Letzte 30 Tage</h3>
                        <div style="font-size: 20px; font-weight: bold; color: #2271b1;">
                            <?php echo $report['wc_metrics']['last_30_days']['conversion_rate']; ?>%
                        </div>
                        <div style="font-size: 12px; color: #666; margin-top: 5px;">
                            Conversion Rate
                        </div>
                        <div style="font-size: 14px; font-weight: bold; color: #333; margin-top: 8px;">
                            <?php echo number_format($report['wc_metrics']['last_30_days']['revenue'], 2, ',', '.'); ?> €
                        </div>
                        <div style="font-size: 11px; color: #666;">
                            Umsatz
                        </div>
                        <div style="font-size: 12px; color: #666; margin-top: 5px;">
                            Ø <?php echo number_format($report['wc_metrics']['last_30_days']['average_order_value'], 2, ',', '.'); ?> €
                        </div>
                        <div style="font-size: 11px; color: #666;">
                            pro Bestellung
                        </div>
                        <!-- NEU: Kontakt Conversions -->
                        <div style="font-size: 11px; color: #666; margin-top: 5px; border-top: 1px solid #eee; padding-top: 5px;">
                            📞 <?php echo $report['wc_metrics']['last_30_days']['contact_conversions']['phone_clicks'] ?? 0; ?> 
                            ✉️ <?php echo $report['wc_metrics']['last_30_days']['contact_conversions']['email_clicks'] ?? 0; ?>
                        </div>
                    </div>

                    <!-- Diesen Monat -->
                    <div style="flex: 1; min-width: 160px; text-align: center; padding: 15px; background: #f0f8ff; border-radius: 5px; border-left: 4px solid #2271b1;">
                        <h3 style="margin: 0 0 5px 0; font-size: 14px;">Diesen Monat</h3>
                        <div style="font-size: 20px; font-weight: bold; color: #2271b1;">
                            <?php echo $report['wc_metrics']['current_period']['conversion_rate']; ?>%
                        </div>
                        <div style="font-size: 12px; color: #666; margin-top: 5px;">
                            Conversion Rate
                        </div>
                        <div style="font-size: 14px; font-weight: bold; color: #333; margin-top: 8px;">
                            <?php echo number_format($report['wc_metrics']['current_period']['revenue'], 2, ',', '.'); ?> €
                        </div>
                        <div style="font-size: 11px; color: #666;">
                            Umsatz
                        </div>
                        <div style="font-size: 12px; color: #666; margin-top: 5px;">
                            Ø <?php echo number_format($report['wc_metrics']['current_period']['average_order_value'], 2, ',', '.'); ?> €
                        </div>
                        <div style="font-size: 11px; color: #666;">
                            pro Bestellung
                        </div>
                        <!-- NEU: Kontakt Conversions -->
                        <div style="font-size: 11px; color: #666; margin-top: 5px; border-top: 1px solid #eee; padding-top: 5px;">
                            📞 <?php echo $report['wc_metrics']['current_period']['contact_conversions']['phone_clicks'] ?? 0; ?> 
                            ✉️ <?php echo $report['wc_metrics']['current_period']['contact_conversions']['email_clicks'] ?? 0; ?>
                        </div>
                    </div>

                    <!-- Bestellungen & Kunden -->
                    <div style="flex: 1; min-width: 160px; text-align: center; padding: 15px; background: #fff8e1; border-radius: 5px;">
                        <h3 style="margin: 0 0 5px 0; font-size: 14px;">Bestellungen & Kunden</h3>
                        <div style="font-size: 16px; font-weight: bold; color: #f57c00; margin: 5px 0;">
                            <?php echo $report['wc_metrics']['current_period']['total_orders']; ?> Bestellungen
                        </div>
                        <div style="font-size: 16px; font-weight: bold; color: #f57c00; margin: 5px 0;">
                            <?php echo $report['wc_metrics']['current_period']['unique_customers']; ?> Kunden
                        </div>
                        <div style="font-size: 14px; color: #666; margin-top: 5px;">
                            <?php echo $report['wc_metrics']['customer_metrics']['repeat_customer_rate']; ?>% Stammkunden
                        </div>
                        <!-- NEU: Kontakt Engagement -->
                        <div style="font-size: 12px; color: #666; margin-top: 5px; border-top: 1px solid #eee; padding-top: 5px;">
                            Kontakt Rate: <?php echo $report['wc_metrics']['funnel']['contact_engagement'] ?? 0; ?>%
                        </div>
                    </div>

                    <!-- Bestätigte Umsätze -->
                    <div style="flex: 1; min-width: 160px; text-align: center; padding: 15px; background: #e8f5e8; border-radius: 5px;">
                        <h3 style="margin: 0 0 5px 0; font-size: 14px;">Bestätigte Umsätze</h3>
                        <div style="font-size: 14px; font-weight: bold; color: #2e7d32; margin: 5px 0;">
                            7 Tage: <?php echo number_format($report['wc_metrics']['last_7_days']['confirmed_revenue'], 2, ',', '.'); ?> €
                        </div>
                        <div style="font-size: 14px; font-weight: bold; color: #2e7d32; margin: 5px 0;">
                            30 Tage: <?php echo number_format($report['wc_metrics']['last_30_days']['confirmed_revenue'], 2, ',', '.'); ?> €
                        </div>
                        <div style="font-size: 14px; font-weight: bold; color: #2e7d32; margin: 5px 0;">
                            Monat: <?php echo number_format($report['wc_metrics']['current_period']['confirmed_revenue'], 2, ',', '.'); ?> €
                        </div>
                    </div>
                </div>

                <!-- NEUE: Engagement Metriken -->
                <div style="display: flex; gap: 1rem; margin-bottom: 20px; flex-wrap: wrap;">
                    <!-- Kontakt Events -->
                    <div style="flex: 1; min-width: 200px; padding: 15px; background: #e8f5e8; border-radius: 5px;">
                        <h3 style="margin: 0 0 15px 0; color: #2e7d32;">📞 Kontakt Events</h3>
                        <?php if (!empty($report['wc_metrics']['engagement_metrics']['contact_events'])): ?>
                            <table style="width: 100%;">
                                <?php foreach ($report['wc_metrics']['engagement_metrics']['contact_events'] as $event): ?>
                                    <tr>
                                        <td><?php echo $event['event_type'] == 'phone_click' ? '📞 Telefon' : '✉️ E-Mail'; ?>:</td>
                                        <td style="text-align: right; font-weight: bold;">
                                            <?php echo $event['count']; ?> (<?php echo $event['percentage']; ?>%)
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        <?php else: ?>
                            <p style="text-align: center; color: #666;">Keine Kontakt-Events</p>
                        <?php endif; ?>
                    </div>

                    <!-- Such-Analytics -->
                    <div style="flex: 1; min-width: 200px; padding: 15px; background: #e3f2fd; border-radius: 5px;">
                        <h3 style="margin: 0 0 15px 0; color: #1976d2;">🔍 Top Suchbegriffe</h3>
                        <?php if (!empty($report['wc_metrics']['engagement_metrics']['search_analytics'])): ?>
                            <table style="width: 100%; font-size: 12px;">
                                <?php foreach (array_slice($report['wc_metrics']['engagement_metrics']['search_analytics'], 0, 5) as $search): ?>
                                    <tr>
                                        <td><?php echo esc_html($search['keywords']); ?></td>
                                        <td style="text-align: right; font-weight: bold;">
                                            <?php echo $search['search_count']; ?>×
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        <?php else: ?>
                            <p style="text-align: center; color: #666;">Keine Suchbegriffe</p>
                        <?php endif; ?>
                    </div>

                    <!-- Weitere Engagement -->
                    <div style="flex: 1; min-width: 200px; padding: 15px; background: #f3e5f5; border-radius: 5px;">
                        <h3 style="margin: 0 0 15px 0; color: #7b1fa2;">⭐ Engagement</h3>
                        <table style="width: 100%;">
                            <tr>
                                <td>Kategorie Views:</td>
                                <td style="text-align: right; font-weight: bold;">
                                    <?php echo $report['wc_metrics']['engagement_metrics']['category_engagement'] ?? 0; ?>
                                </td>
                            </tr>
                            <tr>
                                <td>Wunschliste:</td>
                                <td style="text-align: right; font-weight: bold;">
                                    <?php echo $report['wc_metrics']['engagement_metrics']['wishlist_activity'] ?? 0; ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Funnel & Device Performance -->
                <div style="display: flex; gap: 1rem; margin-bottom: 20px; flex-wrap: wrap;">
                    <!-- Funnel Metriken -->
                    <div style="flex: 1; min-width: 300px; padding: 15px; background: #f3e5f5; border-radius: 5px;">
                        <h3 style="margin: 0 0 15px 0; color: #7b1fa2;">🔄 Funnel Conversion</h3>
                        <table style="width: 100%;">
                            <tr>
                                <td>View → Cart:</td>
                                <td style="text-align: right; font-weight: bold;">
                                    <?php echo $report['wc_metrics']['funnel']['view_to_cart']; ?>%
                                </td>
                            </tr>
                            <tr>
                                <td>Cart → Checkout:</td>
                                <td style="text-align: right; font-weight: bold;">
                                    <?php echo $report['wc_metrics']['funnel']['cart_to_checkout']; ?>%
                                </td>
                            </tr>
                            <tr>
                                <td>Checkout → Order:</td>
                                <td style="text-align: right; font-weight: bold;">
                                    <?php echo $report['wc_metrics']['funnel']['checkout_to_order']; ?>%
                                </td>
                            </tr>
                            <tr style="border-top: 1px solid #ddd;">
                                <td>Cart Abandonment:</td>
                                <td style="text-align: right; font-weight: bold; color: #d32f2f;">
                                    <?php echo $report['wc_metrics']['funnel']['cart_abandonment_rate']; ?>%
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Device Performance -->
                    <div style="flex: 1; min-width: 300px; padding: 15px; background: #e3f2fd; border-radius: 5px;">
                        <h3 style="margin: 0 0 15px 0; color: #1976d2;">📱 Device Performance</h3>
                        <table style="width: 100%;">
                            <tr>
                                <td>🖥️ Desktop:</td>
                                <td style="text-align: right; font-weight: bold;">
                                    <?php echo $report['wc_metrics']['device_performance']['desktop']; ?>%
                                </td>
                            </tr>
                            <tr>
                                <td>📱 Mobile:</td>
                                <td style="text-align: right; font-weight: bold;">
                                    <?php echo $report['wc_metrics']['device_performance']['mobile']; ?>%
                                </td>
                            </tr>
                            <tr>
                                <td>📟 Tablet:</td>
                                <td style="text-align: right; font-weight: bold;">
                                    <?php echo $report['wc_metrics']['device_performance']['tablet']; ?>%
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Kunden Analyse -->
                    <div style="flex: 1; min-width: 300px; padding: 15px; background: #e8f5e8; border-radius: 5px;">
                        <h3 style="margin: 0 0 15px 0; color: #2e7d32;">👥 Kunden Analyse</h3>
                        <table style="width: 100%;">
                            <tr>
                                <td>Neue Kunden:</td>
                                <td style="text-align: right; font-weight: bold;">
                                    <?php echo $report['wc_metrics']['customer_metrics']['new_vs_returning']['new_customers']; ?>
                                    (<?php echo $report['wc_metrics']['customer_metrics']['new_vs_returning']['new_percentage']; ?>%)
                                </td>
                            </tr>
                            <tr>
                                <td>Stammkunden:</td>
                                <td style="text-align: right; font-weight: bold;">
                                    <?php echo $report['wc_metrics']['customer_metrics']['new_vs_returning']['returning_customers']; ?>
                                    (<?php echo $report['wc_metrics']['customer_metrics']['new_vs_returning']['returning_percentage']; ?>%)
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- WC Events -->
                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Event-Typ</th>
                            <th>Anzahl</th>
                            <th>Anteil</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($report["wc_metrics"]["events"])): ?>
                            <?php foreach ($report["wc_metrics"]["events"] as $event): ?>
                                <tr>
                                    <td><?php echo esc_html($event["event_type"]); ?></td>
                                    <td><strong><?php echo $event["count"]; ?></strong></td>
                                    <td><?php echo $event["percentage"]; ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" style="text-align: center;">Keine WooCommerce Events verfügbar</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- NEUE: Erweiterte Diagramme -->
                <div style="display: flex; gap: 1rem; margin-bottom: 20px; flex-wrap: wrap;">
                    <!-- Funnel Diagramm -->
                    <div style="flex: 1; min-width: 400px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                        <h3 style="margin: 0 0 15px 0;">📊 Funnel Conversion</h3>
                        <div style="height: 250px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                            <canvas id="funnelChart"></canvas>
                        </div>
                    </div>

                    <!-- Device Performance Diagramm -->
                    <div style="flex: 1; min-width: 400px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                        <h3 style="margin: 0 0 15px 0;">📱 Device Conversion</h3>
                        <div style="height: 250px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                            <canvas id="deviceChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- NEUE: Kontakt Events Diagramm -->
                <div style="display: flex; gap: 1rem; margin-bottom: 20px; flex-wrap: wrap;">
                    <!-- Kontakt Events Verlauf -->
                    <div style="flex: 1; min-width: 400px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                        <h3 style="margin: 0 0 15px 0;">📞 Kontakt Events (letzte 7 Tage)</h3>
                        <div style="height: 250px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                            <canvas id="contactChart7d"></canvas>
                        </div>
                    </div>

                    <!-- Revenue vs Orders -->
                    <div style="flex: 1; min-width: 400px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                        <h3 style="margin: 0 0 15px 0;">💰 Umsatz vs Bestellungen (7 Tage)</h3>
                        <div style="height: 250px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                            <canvas id="revenueChart7d"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Conversion Rate Diagramme -->
                <div style="display: flex; gap: 1rem; margin-bottom: 20px; flex-wrap: wrap;">
                    <!-- 7-Tage Diagramm -->
                    <div style="flex: 1; min-width: 400px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                        <h3 style="margin: 0 0 15px 0;">Conversion Rate Verlauf (letzte 7 Tage)</h3>
                        <div style="height: 200px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                            <canvas id="conversionChart7d"></canvas>
                        </div>
                    </div>

                    <!-- 30-Tage Diagramm -->
                    <div style="flex: 1; min-width: 400px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                        <h3 style="margin: 0 0 15px 0;">Conversion Rate Verlauf (letzte 30 Tage)</h3>
                        <div style="height: 200px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                            <canvas id="conversionChart30d"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Events Diagramme -->
                <div style="display: flex; gap: 1rem; margin-bottom: 20px; flex-wrap: wrap;">
                    <!-- 7-Tage Events -->
                    <div style="flex: 1; min-width: 400px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                        <h3 style="margin: 0 0 15px 0;">Events Verlauf (letzte 7 Tage)</h3>
                        <div style="height: 250px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                            <canvas id="eventsChart7d"></canvas>
                        </div>
                    </div>

                    <!-- 30-Tage Events -->
                    <div style="flex: 1; min-width: 400px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                        <h3 style="margin: 0 0 15px 0;">Events Verlauf (letzte 30 Tage)</h3>
                        <div style="height: 250px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                            <canvas id="eventsChart30d"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Meistbesuchte Seiten -->
        <div class="postbox" style="width: 100%; flex-basis: 100%">
            <div class="inside">
                <h2 class="hndle" style="margin-bottom: 10px;"><span>Meistbesuchte Seiten</span></h2>
                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 50%;">Seite</th>
                            <th>Aufrufe</th>
                            <th>Anteil</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($report["pages"])): ?>
                            <?php foreach ($report["pages"] as $page): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 500;">
                                            <?php echo esc_html($page["page_title"] ?: 'Ohne Titel'); ?>
                                        </div>
                                        <div style="font-size: 11px; color: #666; margin-top: 2px;">
                                            <?php echo esc_html($page["url"]); ?>
                                        </div>
                                    </td>
                                    <td><strong><?php echo $page["count"]; ?></strong></td>
                                    <td><?php echo $page["percentage"]; ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" style="text-align: center;">Keine Seitenaufrufe im gewählten Zeitraum</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Einstiegsseiten -->
        <div class="postbox" style="width: 100%; flex-basis: 100%">
            <div class="inside">
                <h2 class="hndle" style="margin-bottom: 10px;"><span>Einstiegsseiten</span></h2>
                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 50%;">Seite</th>
                            <th>Einstiege</th>
                            <th>Anteil</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($report["entry_pages"])): ?>
                            <?php foreach ($report["entry_pages"] as $page): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 500;">
                                            <?php echo esc_html($page["page_title"] ?: 'Ohne Titel'); ?>
                                        </div>
                                        <div style="font-size: 11px; color: #666; margin-top: 2px;">
                                            <?php echo esc_html($page["url"]); ?>
                                        </div>
                                    </td>
                                    <td><strong><?php echo $page["entries"]; ?></strong></td>
                                    <td><?php echo $page["percentage"]; ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" style="text-align: center;">Keine Einstiegsseiten verfügbar</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Ausstiegsseiten -->
        <div class="postbox" style="width: 100%; flex-basis: 100%">
            <div class="inside">
                <h2 class="hndle" style="margin-bottom: 10px;"><span>Ausstiegsseiten</span></h2>
                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 50%;">Seite</th>
                            <th>Ausstiege</th>
                            <th>Anteil</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($report["exit_pages"])): ?>
                            <?php foreach ($report["exit_pages"] as $page): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 500;">
                                            <?php echo esc_html($page["page_title"] ?: 'Ohne Titel'); ?>
                                        </div>
                                        <div style="font-size: 11px; color: #666; margin-top: 2px;">
                                            <?php echo esc_html($page["url"]); ?>
                                        </div>
                                    </td>
                                    <td><strong><?php echo $page["exits"]; ?></strong></td>
                                    <td><?php echo $page["percentage"]; ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" style="text-align: center;">Keine Ausstiegsseiten verfügbar</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Exit-Raten -->
        <div class="postbox" style="width: 100%; flex-basis: 100%">
            <div class="inside">
                <h2 class="hndle" style="margin-bottom: 10px;"><span>Exit-Raten</span></h2>
                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 40%;">Seite</th>
                            <th>Gesamtaufrufe</th>
                            <th>Ausstiege</th>
                            <th>Exit-Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($report["exit_rates"])): ?>
                            <?php foreach ($report["exit_rates"] as $page): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 500;">
                                            <?php echo esc_html($page["page_title"] ?: 'Ohne Titel'); ?>
                                        </div>
                                        <div style="font-size: 11px; color: #666; margin-top: 2px;">
                                            <?php echo esc_html($page["url"]); ?>
                                        </div>
                                    </td>
                                    <td><strong><?php echo $page["total_views"]; ?></strong></td>
                                    <td><strong><?php echo $page["exit_views"]; ?></strong></td>
                                    <td><strong><?php echo $page["exit_rate"]; ?>%</strong></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center;">Keine Exit-Raten verfügbar</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Gerätetypen -->
        <div class="postbox" style="flex: 1;">
            <div class="inside">
                <h2 class="hndle"><span>Gerätetypen</span></h2>
                <table class="widefat fixed striped">
                    <tbody>
                        <?php foreach ($report["device_types"] as $device): ?>
                            <tr>
                                <td><?php echo esc_html($device["device_type"]); ?></td>
                                <td><strong><?php echo $device["count"]; ?></strong></td>
                                <td><?php echo $device["percentage"]; ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Geräte-Marken -->
        <div class="postbox" style="flex: 1;">
            <div class="inside">
                <h2 class="hndle"><span>Top Geräte-Marken</span></h2>
                <table class="widefat fixed striped">
                    <tbody>
                        <?php foreach ($report["device_brands"] as $brand): ?>
                            <tr>
                                <td><?php echo esc_html($brand["brand"]); ?></td>
                                <td><strong><?php echo $brand["count"]; ?></strong></td>
                                <td><?php echo $brand["percentage"]; ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Betriebssysteme -->
        <div class="postbox" style="flex: 1;">
            <div class="inside">
                <h2 class="hndle"><span>Betriebssysteme</span></h2>
                <table class="widefat fixed striped">
                    <tbody>
                        <?php foreach ($report["operating_systems"] as $os): ?>
                            <tr>
                                <td><?php echo esc_html($os["platform"]); ?></td>
                                <td><strong><?php echo $os["count"]; ?></strong></td>
                                <td><?php echo $os["percentage"]; ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Browsers -->
        <div class="postbox" style="flex: 1;">
            <div class="inside">
                <h2 class="hndle" style="margin-bottom: 10px;"><span>Browsers</span></h2>
                <table class="widefat fixed striped">
                    <tbody>
                        <?php foreach ($report["browsers"] as $browser): ?>
                            <tr>
                                <td><?php echo esc_html($browser["browser_name"]); ?></td>
                                <td><strong><?php echo $browser["count"]; ?></strong></td>
                                <td><?php echo $browser["percentage"]; ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Bildschirmauflösungen -->
        <div class="postbox" style="width: 100%; flex-basis: 100%">
            <div class="inside">
                <h2 class="hndle" style="margin-bottom: 10px;"><span>Bildschirmauflösungen</span></h2>
                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Auflösung</th>
                            <th>Sessions</th>
                            <th>Anteil</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($report["screen_resolutions"])): ?>
                            <?php foreach ($report["screen_resolutions"] as $resolution): ?>
                                <tr>
                                    <td><?php echo esc_html($resolution["screen_resolution"]); ?></td>
                                    <td><strong><?php echo $resolution["count"]; ?></strong></td>
                                    <td><?php echo $resolution["percentage"]; ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" style="text-align: center;">Keine Auflösungsdaten verfügbar</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Sprachen -->
        <div class="postbox" style="flex: 1;">
            <div class="inside">
                <h2 class="hndle" style="margin-bottom: 10px;"><span>Sprachen</span></h2>
                <table class="widefat fixed striped">
                    <tbody>
                        <?php foreach ($report["languages"] as $language): ?>
                            <tr>
                                <td><?php echo esc_html($language["language_clean"]); ?></td>
                                <td><strong><?php echo $language["count"]; ?></strong></td>
                                <td><?php echo $language["percentage"]; ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Besuchszeiten -->
        <div class="postbox" style="width: 100%; flex-basis: 100%">
            <div class="inside">
                <h2 class="hndle" style="margin-bottom: 10px;"><span>Besuchszeiten (Tageszeit)</span></h2>
                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Uhrzeit</th>
                            <th>Sessions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($report["visit_times"])): ?>
                            <?php foreach ($report["visit_times"] as $time): ?>
                                <tr>
                                    <td><?php echo sprintf("%02d:00 - %02d:59", $time["hour"], $time["hour"]); ?></td>
                                    <td><strong><?php echo $time["count"]; ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" style="text-align: center;">Keine Zeitdaten verfügbar</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Länder -->
        <div class="postbox" style="width: 100%; flex-basis: 100%">
            <div class="inside">
                <h2 class="hndle" style="margin-bottom: 10px;"><span>Länder</span></h2>
                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Land</th>
                            <th>Sessions</th>
                            <th>Anteil</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report["countries"] as $country): ?>
                            <tr>
                                <td><?php echo esc_html($country["country_name"]); ?></td>
                                <td><strong><?php echo $country["count"]; ?></strong></td>
                                <td><?php echo $country["percentage"]; ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Städte -->
        <div class="postbox" style="width: 100%; flex-basis: 100%">
            <div class="inside">
                <h2 class="hndle" style="margin-bottom: 10px;"><span>Top Städte</span></h2>
                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Stadt</th>
                            <th>Land</th>
                            <th>Sessions</th>
                            <th>Anteil</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($report["cities"])): ?>
                            <?php foreach ($report["cities"] as $city): ?>
                                <tr>
                                    <td><?php echo esc_html($city["city"]); ?></td>
                                    <td><?php echo esc_html($city["country_name"]); ?></td>
                                    <td><strong><?php echo $city["count"]; ?></strong></td>
                                    <td><?php echo $city["percentage"]; ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center;">Keine Städtedaten verfügbar</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Suchmaschinen -->
        <div class="postbox" style="flex: 1;">
            <div class="inside">
                <h2 class="hndle" style="margin-bottom: 10px;"><span>Suchmaschinen</span></h2>
                <table class="widefat fixed striped">
                    <tbody>
                        <?php foreach ($report["search_engines"] as $engine): ?>
                            <tr>
                                <td><?php echo esc_html($engine["source_name"]); ?></td>
                                <td><strong><?php echo $engine["count"]; ?></strong></td>
                                <td><?php echo $engine["percentage"]; ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Soziale Netzwerke -->
        <div class="postbox" style="flex: 1;">
            <div class="inside">
                <h2 class="hndle" style="margin-bottom: 10px;"><span>Soziale Netzwerke</span></h2>
                <table class="widefat fixed striped">
                    <tbody>
                        <?php foreach ($report["social_networks"] as $social): ?>
                            <tr>
                                <td><?php echo esc_html($social["source_name"]); ?></td>
                                <td><strong><?php echo $social["count"]; ?></strong></td>
                                <td><?php echo $social["percentage"]; ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- GSC Keywords -->
        <div class="postbox" style="width: 100%; flex-basis: 100%">
            <div class="inside">
                <h2 class="hndle" style="margin-bottom: 10px;"><span>Google Search Console Keywords (Letzen 16 Monate)</span></h2>
                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Keyword</th>
                            <th>Clicks</th>
                            <th>Anteil</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($report["gsc_keywords"])): ?>
                            <?php foreach ($report["gsc_keywords"] as $keyword): ?>
                                <tr>
                                    <td><?php echo esc_html($keyword["keywords"]); ?></td>
                                    <td><strong><?php echo $keyword["count"]; ?></strong></td>
                                    <td><?php echo $keyword["percentage"]; ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" style="text-align: center;">Keine GSC Daten verfügbar</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
    </div>

    <div id="tab-gsc" class="tab-content" style="display: none;">


        <div class="wrap">
            <h1>Google Search Console Integration</h1>

            <!-- Setup-Anleitung -->
            <div class="postbox">
                <div class="inside">
                    <h2>📋 Setup-Anleitung</h2>
                    <ol>
                        <li><strong>Google Cloud Console öffnen:</strong> <a href="https://console.cloud.google.com/" target="_blank">console.cloud.google.com</a></li>
                        <li><strong>Projekt auswählen oder erstellen</strong></li>
                        <li><strong>APIs aktivieren:</strong>
                            <ul>
                                <li>Google Search Console API</li>
                            </ul>
                        </li>
                        <li><strong>OAuth 2.0 Client ID erstellen:</strong>
                            <ul>
                                <li>Zu "APIs & Services" → "Credentials" gehen</li>
                                <li>"Create Credentials" → "OAuth 2.0 Client IDs"</li>
                                <li>Application type: "Web application"</li>
                                <li>Name: "Halteverbot Search Console"</li>
                            </ul>
                        </li>
                        <li><strong>Authorized redirect URIs hinzufügen:</strong>
                            <ul>
                                <li><code><?php echo esc_url($gsc->getCurrentUrl()); ?></code></li>
                            </ul>
                        </li>
                        <li><strong>Client ID und Secret unten eintragen</strong></li>
                    </ol>
                </div>
            </div>

            <!-- Status-Übersicht -->
            <div class="postbox">
                <div class="inside">
                    <h2>🔍 Status</h2>
                    <table class="widefat">
                        <tr>
                            <td><strong>Client ID konfiguriert:</strong></td>
                            <td><?php echo $status['has_client_id'] ? '✅ Ja' : '❌ Nein'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Client Secret konfiguriert:</strong></td>
                            <td><?php echo $status['has_client_secret'] ? '✅ Ja' : '❌ Nein'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Mit Google verbunden:</strong></td>
                            <td><?php echo $status['authenticated'] ? '✅ Ja' : '❌ Nein'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Primäre Domain:</strong></td>
                            <td>
                                <?php 
                                $primaryDomain = $gsc->getPrimaryDomain();
                                echo $primaryDomain ? '✅ ' . esc_html($primaryDomain) : '❌ Nicht festgelegt';
                                ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Client ID/Secret Konfiguration -->
            <div class="postbox">
                <div class="inside">
                    <h2>⚙️ API Konfiguration</h2>
                    <form method="post">
                        <table class="form-table">
                            <tr>
                                <th scope="row">Client ID</th>
                                <td>
                                    <input type="text" name="client_id" value="<?php echo esc_attr($gsc->getClientId()); ?>" class="regular-text" placeholder="1054151987867-xxxxxxxx.apps.googleusercontent.com">
                                    <p class="description">Von Google Cloud Console</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Client Secret</th>
                                <td>
                                    <input type="password" name="client_secret" value="<?php echo esc_attr($gsc->getClientSecret()); ?>" class="regular-text" placeholder="GOCSPX-xxxxxxxx">
                                    <p class="description">Von Google Cloud Console</p>
                                </td>
                            </tr>
                        </table>
                        <?php submit_button('Credentials speichern', 'primary', 'save_credentials'); ?>
                    </form>
                </div>
            </div>

            <!-- Verbindung mit Google -->
            <?php if ($status['configured'] && !$status['authenticated']): ?>
                <div class="postbox">
                    <div class="inside">
                        <h2>🔗 Verbindung mit Google</h2>
                        <p>Klicke auf den Button um die Verbindung mit Google Search Console herzustellen:</p>
                        <?php $authUrl = $gsc->getAuthUrl(); ?>
                        <?php if ($authUrl): ?>
                            <a href="<?php echo esc_url($authUrl); ?>" class="button button-primary button-large">
                                Mit Google Search Console verbinden
                            </a>
                        <?php else: ?>
                            <p class="notice notice-error">Client ID und Secret müssen zuerst konfiguriert werden.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Websites anzeigen mit Primär-Domain Auswahl -->
            <?php if ($status['authenticated']): ?>
                <div class="postbox">
                    <div class="inside">
                        <h2>🌐 Deine Websites</h2>
                        <?php
                        $sitesResult = $gsc->getSitesWithPrimary();
                        if ($sitesResult['success'] && !empty($sitesResult['sites'])): 
                            $sites = $sitesResult['sites'];
                            $primaryDomain = $sitesResult['primary_domain'];
                        ?>
                            <!-- Primäre Domain Auswahl -->
                            <form method="post" style="margin-bottom: 20px;">
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">Primäre Domain wählen:</th>
                                        <td>
                                            <select name="primary_domain" class="regular-text">
                                                <option value="">-- Bitte wählen --</option>
                                                <?php foreach ($sites as $site): ?>
                                                    <option value="<?php echo esc_attr($site['siteUrl']); ?>" 
                                                        <?php selected($site['siteUrl'], $primaryDomain); ?>>
                                                        <?php echo esc_html($site['siteUrl']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <?php submit_button('Als primär festlegen', 'secondary', 'set_primary_domain'); ?>
                                        </td>
                                    </tr>
                                </table>
                            </form>

                            <!-- Websites Tabelle -->
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th>Website URL</th>
                                        <th>Berechtigung</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sites as $site): ?>
                                        <tr>
                                            <td>
                                                <?php echo esc_html($site['siteUrl']); ?>
                                                <?php if ($site['is_primary']): ?>
                                                    <span style="color: #46b450; font-weight: bold;">★ Primär</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo esc_html($site['permissionLevel'] ?? 'N/A'); ?></td>
                                            <td>
                                                <?php if ($site['is_primary']): ?>
                                                    <span style="color: #46b450;">✅ Aktiv</span>
                                                <?php else: ?>
                                                    <span style="color: #ccc;">⭕ Inaktiv</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <p><strong>Gesamt:</strong> <?php echo count($sites); ?> Websites</p>


                            <?php if (!empty($primaryDomain)): ?>
                                <div style="margin-top: 30px; padding: 15px; background: #f9f9f9; border-radius: 4px;">
                                    <h3>📊 Daten für primäre Domain: <?php echo esc_html($primaryDomain); ?></h3>
                                    <?php
                                    // Beispiel: Daten der letzten 30 Tage abrufen
                                    $startDate = date('Y-m-d', strtotime('-30 days'));
                                    $endDate = date('Y-m-d');
                                    
                                    $payload = [
                                        'startDate' => $startDate,
                                        'endDate' => $endDate,
                                        'dimensions' => ['query'],
                                        'rowLimit' => 10,
                                        'orderBy' => [
                                            [
                                                'dimension' => 'CLICKS',
                                                'sortOrder' => 'DESCENDING'
                                            ]
                                        ]
                                    ];
                                    
                                    $analyticsData = $gsc->getPrimaryDomainData($payload);
                                    
                                    if ($analyticsData['success'] && !empty($analyticsData['data'])):
                                        $rows = $analyticsData['data'];
                                    ?>
                                        <p><strong>Top Suchbegriffe (letzte 30 Tage):</strong></p>
                                        <table class="wp-list-table widefat fixed striped">
                                            <thead>
                                                <tr>
                                                    <th>Suchbegriff</th>
                                                    <th>Klicks</th>
                                                    <th>Impressionen</th>
                                                    <th>CTR</th>
                                                    <th>Position</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($rows as $row): ?>
                                                    <tr>
                                                        <td><?php echo esc_html($row['keys'][0] ?? 'N/A'); ?></td>
                                                        <td><?php echo esc_html($row['clicks'] ?? 0); ?></td>
                                                        <td><?php echo esc_html($row['impressions'] ?? 0); ?></td>
                                                        <td><?php echo round(($row['ctr'] ?? 0) * 100, 2); ?>%</td>
                                                        <td><?php echo round($row['position'] ?? 0, 1); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    <?php else: ?>
                                        <p>Keine Daten verfügbar oder Fehler: <?php echo esc_html($analyticsData['error'] ?? 'Unbekannter Fehler'); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>


                        <?php else: ?>
                            <div class="notice notice-error">
                                <p>Fehler beim Abrufen der Websites: <?php echo esc_html($sitesResult['error']); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Reset Option -->
            <div class="postbox">
                <div class="inside">
                    <h2>🔄 Zurücksetzen</h2>
                    <p>Lösche alle gespeicherten Daten (Client ID, Secret und Tokens):</p>
                    <form method="post" onsubmit="return confirm('Wirklich alle Daten zurücksetzen?');">
                        <?php submit_button('Alle Daten zurücksetzen', 'delete', 'reset_all'); ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>

    document.addEventListener('DOMContentLoaded', function() {
        // Daten aus PHP
        const dailyData7d = <?php echo json_encode($report['wc_metrics']['last_7_days']['daily_data'] ?? []); ?>;
        const dailyData30d = <?php echo json_encode($report['wc_metrics']['last_30_days']['daily_data'] ?? []); ?>;

        // Event Types und Farben
        const eventTypes = ['product_view', 'add_to_cart', 'order_complete', 'phone_click', 'email_click'];
        
        const eventLabels = {
            'product_view': 'Product Views',
            'add_to_cart': 'Add to Cart', 
            'checkout_start': 'Checkout Start',
            'order_complete': 'Orders Complete',
            'phone_click': 'Phone Clicks',
            'email_click': 'Email Clicks'
        };

        const eventColors = {
            'product_view': '#2271b1',
            'add_to_cart': '#f0c420', 
            'checkout_start': '#d63638',
            'order_complete': '#2e7d32',
            'phone_click': '#7e57c2',
            'email_click': '#26a69a'
        };

        // 1. Conversion Rate Diagramm (7 Tage)
        if (Object.keys(dailyData7d).length > 0) {
            const dates7d = Object.keys(dailyData7d);
            const conversionRates7d = dates7d.map(date => dailyData7d[date].conversion_rate);
            
            new Chart(document.getElementById('conversionChart7d'), {
                type: 'line',
                data: {
                    labels: dates7d,
                    datasets: [{
                        label: 'Conversion Rate (%)',
                        data: conversionRates7d,
                        borderColor: '#2271b1',
                        backgroundColor: 'rgba(34, 113, 177, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: { 
                                display: true, 
                                text: 'Conversion Rate (%)' 
                            }
                        },
                        x: {
                            title: { 
                                display: true, 
                                text: 'Datum' 
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `Conversion Rate: ${context.parsed.y}%`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // 2. Conversion Rate Diagramm (30 Tage)
        if (Object.keys(dailyData30d).length > 0) {
            const dates30d = Object.keys(dailyData30d);
            const conversionRates30d = dates30d.map(date => dailyData30d[date].conversion_rate);
            
            new Chart(document.getElementById('conversionChart30d'), {
                type: 'line',
                data: {
                    labels: dates30d,
                    datasets: [{
                        label: 'Conversion Rate (%)',
                        data: conversionRates30d,
                        borderColor: '#2271b1',
                        backgroundColor: 'rgba(34, 113, 177, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: { 
                                display: true, 
                                text: 'Conversion Rate (%)' 
                            }
                        },
                        x: {
                            title: { 
                                display: true, 
                                text: 'Datum' 
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `Conversion Rate: ${context.parsed.y}%`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // 3. Events Diagramm (7 Tage)
        if (Object.keys(dailyData7d).length > 0) {
            const dates7d = Object.keys(dailyData7d);
            
            const datasets = eventTypes.map(eventType => ({
                label: eventLabels[eventType],
                data: dates7d.map(date => dailyData7d[date][eventType]),
                borderColor: eventColors[eventType],
                backgroundColor: eventColors[eventType] + '20',
                borderWidth: 2,
                tension: 0.4
            }));

            new Chart(document.getElementById('eventsChart7d'), {
                type: 'line',
                data: {
                    labels: dates7d,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: { 
                                display: true, 
                                text: 'Anzahl Events' 
                            }
                        },
                        x: {
                            title: { 
                                display: true, 
                                text: 'Datum' 
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.dataset.label}: ${context.parsed.y}`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // 4. Events Diagramm (30 Tage)
        if (Object.keys(dailyData30d).length > 0) {
            const dates30d = Object.keys(dailyData30d);
            
            const datasets = eventTypes.map(eventType => ({
                label: eventLabels[eventType],
                data: dates30d.map(date => dailyData30d[date][eventType]),
                borderColor: eventColors[eventType],
                backgroundColor: eventColors[eventType] + '20',
                borderWidth: 2,
                tension: 0.4
            }));

            new Chart(document.getElementById('eventsChart30d'), {
                type: 'line',
                data: {
                    labels: dates30d,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: { 
                                display: true, 
                                text: 'Anzahl Events' 
                            }
                        },
                        x: {
                            title: { 
                                display: true, 
                                text: 'Datum' 
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.dataset.label}: ${context.parsed.y}`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Funnel Chart
        const funnelCtx = document.getElementById('funnelChart')?.getContext('2d');
        if (funnelCtx) {
            new Chart(funnelCtx, {
                type: 'bar',
                data: {
                    labels: ['View → Cart', 'Cart → Checkout', 'Checkout → Order'],
                    datasets: [{
                        label: 'Conversion Rate (%)',
                        data: [
                            <?php echo $report['wc_metrics']['funnel']['view_to_cart']; ?>,
                            <?php echo $report['wc_metrics']['funnel']['cart_to_checkout']; ?>,
                            <?php echo $report['wc_metrics']['funnel']['checkout_to_order']; ?>
                        ],
                        backgroundColor: ['#4CAF50', '#2196F3', '#FF9800'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            title: { display: true, text: 'Conversion Rate (%)' }
                        }
                    }
                }
            });
        }

        // Device Chart
        const deviceCtx = document.getElementById('deviceChart')?.getContext('2d');
        if (deviceCtx) {
            new Chart(deviceCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Desktop', 'Mobile', 'Tablet'],
                    datasets: [{
                        data: [
                            <?php echo $report['wc_metrics']['device_performance']['desktop']; ?>,
                            <?php echo $report['wc_metrics']['device_performance']['mobile']; ?>,
                            <?php echo $report['wc_metrics']['device_performance']['tablet']; ?>
                        ],
                        backgroundColor: ['#2196F3', '#4CAF50', '#FF9800'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }

        // Contact Events Chart (7 Tage)
        const contactData7d = <?php echo json_encode($report['wc_metrics']['last_7_days']['daily_data'] ?? []); ?>;
        if (Object.keys(contactData7d).length > 0) {
            const dates7d = Object.keys(contactData7d);
            const phoneClicks = dates7d.map(date => contactData7d[date]['phone_click'] || 0);
            const emailClicks = dates7d.map(date => contactData7d[date]['email_click'] || 0);
            
            new Chart(document.getElementById('contactChart7d'), {
                type: 'line',
                data: {
                    labels: dates7d,
                    datasets: [
                        {
                            label: '📞 Telefon Klicks',
                            data: phoneClicks,
                            borderColor: '#4CAF50',
                            backgroundColor: 'rgba(76, 175, 80, 0.1)',
                            borderWidth: 2,
                            tension: 0.4
                        },
                        {
                            label: '✉️ E-Mail Klicks',
                            data: emailClicks,
                            borderColor: '#2196F3',
                            backgroundColor: 'rgba(33, 150, 243, 0.1)',
                            borderWidth: 2,
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, title: { display: true, text: 'Anzahl Klicks' } },
                        x: { title: { display: true, text: 'Datum' } }
                    }
                }
            });
        }

        // Revenue vs Orders Chart
        if (Object.keys(contactData7d).length > 0) {
            const dates7d = Object.keys(contactData7d);
            const revenueData = dates7d.map(date => contactData7d[date]['order_complete'] * 50); // Beispiel Umsatz
            const ordersData = dates7d.map(date => contactData7d[date]['order_complete'] || 0);
            
            new Chart(document.getElementById('revenueChart7d'), {
                type: 'bar',
                data: {
                    labels: dates7d,
                    datasets: [
                        {
                            label: '💰 Umsatz (€)',
                            data: revenueData,
                            backgroundColor: 'rgba(76, 175, 80, 0.6)',
                            borderColor: '#4CAF50',
                            borderWidth: 1,
                            yAxisID: 'y'
                        },
                        {
                            label: '📦 Bestellungen',
                            data: ordersData,
                            type: 'line',
                            borderColor: '#2196F3',
                            backgroundColor: 'rgba(33, 150, 243, 0.1)',
                            borderWidth: 2,
                            tension: 0.4,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: { display: true, text: 'Umsatz (€)' }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: { display: true, text: 'Bestellungen' },
                            grid: { drawOnChartArea: false }
                        }
                    }
                }
            });
        }

    });

    (function(){
        const tabs = document.querySelectorAll('.nav-tab-wrapper .nav-tab');
        const contents = document.querySelectorAll('.tab-content');

        function activateTab(hash) {
            // Falls kein hash, nimm den ersten Tab als Default
            let target = hash || tabs[0].getAttribute('href');

            // Alle Tabs und Inhalte deaktivieren
            tabs.forEach(t => t.classList.remove('nav-tab-active'));
            contents.forEach(c => c.style.display = 'none');

            // Aktivieren
            const tabToActivate = Array.from(tabs).find(t => t.getAttribute('href') === target);
            const contentToShow = document.querySelector(target);

            if (tabToActivate && contentToShow) {
                tabToActivate.classList.add('nav-tab-active');
                contentToShow.style.display = 'block';
            }
        }

        tabs.forEach(tab => {
            tab.addEventListener('click', function(e) {
                e.preventDefault();

                const target = this.getAttribute('href');
                activateTab(target);

                // Optional: URL-Hash anpassen ohne Reload
                history.replaceState(null, '', target);
            });
        });

        // Beim Laden prüfen, ob ein Hash in der URL ist und aktivieren
        activateTab(window.location.hash);
    })();
</script>
