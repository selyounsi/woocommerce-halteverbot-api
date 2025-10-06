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


<!-- VISITOR ANALYTICS CHARTS -->
<div class="postbox" style="width: 100%; flex-basis: 100%">
    <div class="inside">
        <h2 class="hndle" style="margin-bottom: 20px;"><span>📊 Visitor Analytics Charts</span></h2>
        
        <!-- Zeitleiste Charts -->
        <div style="display: flex; gap: 1rem; margin-bottom: 20px; flex-wrap: wrap;">
            <!-- Tägliche Besucher (30 Tage) -->
            <div style="flex: 1; min-width: 400px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h3 style="margin: 0 0 15px 0;">📈 Tägliche Besucher (30 Tage)</h3>
                <div style="height: 300px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                    <canvas id="dailyVisitorsChart30d"></canvas>
                </div>
            </div>
            
            <!-- Tägliche Besucher (7 Tage) -->
            <div style="flex: 1; min-width: 400px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h3 style="margin: 0 0 15px 0;">📈 Tägliche Besucher (7 Tage)</h3>
                <div style="height: 300px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                    <canvas id="dailyVisitorsChart7d"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Verteilungs-Charts -->
        <div style="display: flex; gap: 1rem; margin-bottom: 20px; flex-wrap: wrap;">
            <!-- Geräteverteilung -->
            <div style="flex: 1; min-width: 300px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h3 style="margin: 0 0 15px 0;">📱 Geräteverteilung</h3>
                <div style="height: 250px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                    <canvas id="deviceDistributionChart"></canvas>
                </div>
            </div>
            
            <!-- Browser-Verteilung -->
            <div style="flex: 1; min-width: 300px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h3 style="margin: 0 0 15px 0;">🌐 Browser-Verteilung</h3>
                <div style="height: 250px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                    <canvas id="browserDistributionChart"></canvas>
                </div>
            </div>
            
            <!-- Traffic-Quellen -->
            <div style="flex: 1; min-width: 300px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h3 style="margin: 0 0 15px 0;">🚦 Traffic-Quellen</h3>
                <div style="height: 250px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                    <canvas id="trafficSourcesChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Heatmap und Karten -->
        <div style="display: flex; gap: 1rem; margin-bottom: 20px; flex-wrap: wrap;">
            <!-- Besuchszeiten Heatmap -->
            <div style="flex: 1; min-width: 400px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h3 style="margin: 0 0 15px 0;">🕒 Besuchszeiten Heatmap</h3>
                <div style="height: 300px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                    <canvas id="visitHeatmapChart"></canvas>
                </div>
            </div>
            
            <!-- Top Deutsche Städte -->
            <div style="flex: 1; min-width: 400px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h3 style="margin: 0 0 15px 0;">🗺️ Top Deutsche Städte</h3>
                <div style="height: 300px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                    <canvas id="germanCitiesChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Seiten-Performance -->
        <div style="display: flex; gap: 1rem; margin-bottom: 20px; flex-wrap: wrap;">
            <!-- Seiten Performance -->
            <div style="flex: 1; min-width: 400px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h3 style="margin: 0 0 15px 0;">📄 Seiten Performance</h3>
                <div style="height: 300px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                    <canvas id="pagePerformanceChart"></canvas>
                </div>
            </div>
        </div>
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

                    <!-- Session Analyse -->
                    <div style="flex: 1; min-width: 300px; padding: 15px; background: #e8f5e8; border-radius: 5px;">
                        <h3 style="margin: 0 0 15px 0; color: #2e7d32;">👥 Besucher Analyse</h3>
                        <table style="width: 100%;">
                            <tr>
                                <td>Einmalige Besucher:</td>
                                <td style="text-align: right; font-weight: bold;">
                                    <?php echo $report['wc_metrics']['customer_metrics']['session_analysis']['new_sessions']; ?>
                                </td>
                            </tr>
                            <tr>
                                <td>Mehrfach-Besucher:</td>
                                <td style="text-align: right; font-weight: bold;">
                                    <?php echo $report['wc_metrics']['customer_metrics']['session_analysis']['returning_sessions']; ?>
                                </td>
                            </tr>
                            <tr style="border-top: 1px solid #ddd;">
                                <td><strong>Gesamt Besucher:</strong></td>
                                <td style="text-align: right; font-weight: bold; color: #2e7d32;">
                                    <?php echo $report['wc_metrics']['customer_metrics']['session_analysis']['total_visitors']; ?>
                                </td>
                            </tr>
                        </table>
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

                    <!-- Conversion Analyse -->
                    <div style="flex: 1; min-width: 300px; padding: 15px; background: #e3f2fd; border-radius: 5px;">
                        <h3 style="margin: 0 0 15px 0; color: #1976d2;">🎯 Conversion Analyse</h3>
                        <table style="width: 100%;">
                            <tr>
                                <td>Online Bestellungen:</td>
                                <td style="text-align: right; font-weight: bold;">
                                    <?php echo $report['wc_metrics']['customer_metrics']['conversion_breakdown']['online_orders']; ?>
                                </td>
                            </tr>
                            <tr>
                                <td>Kontakt Leads:</td>
                                <td style="text-align: right; font-weight: bold;">
                                    <?php echo $report['wc_metrics']['customer_metrics']['conversion_breakdown']['contact_leads']; ?>
                                </td>
                            </tr>
                            <tr>
                                <td>High-Value Sessions:</td>
                                <td style="text-align: right; font-weight: bold; color: #d32f2f;">
                                    <?php echo $report['wc_metrics']['customer_metrics']['conversion_breakdown']['high_value_sessions']; ?>
                                </td>
                            </tr>
                            <tr style="border-top: 1px solid #ddd;">
                                <td><strong>Gesamt Conversions:</strong></td>
                                <td style="text-align: right; font-weight: bold; color: #2e7d32;">
                                    <?php echo $report['wc_metrics']['customer_metrics']['conversion_breakdown']['total_conversions']; ?>
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

    <!-- BEWERTUNGS ANALYTICS -->
    <div class="postbox" style="width: 100%; flex-basis: 100%">
        <div class="inside">
            <h2 class="hndle" style="margin-bottom: 20px;"><span>⭐ Bewertungs Analytics</span></h2>
            
            <!-- Bewertungs Übersicht -->
            <div style="display: flex; gap: 1rem; margin-bottom: 20px; flex-wrap: wrap;">
                <?php 
                $reviews_stats = $report['reviews_metrics']['stats'] ?? [];
                $rating_distribution = $report['reviews_metrics']['rating_distribution'] ?? [];
                ?>
                
                <!-- Gesamt-Statistiken -->
                <div style="flex: 1; min-width: 200px; padding: 15px; background: #fff3cd; border-radius: 5px; border-left: 4px solid #ffc107;">
                    <h3 style="margin: 0 0 15px 0; color: #856404;">📊 Gesamtstatistik</h3>
                    <table style="width: 100%;">
                        <tr>
                            <td>Gesamt Bewertungen:</td>
                            <td style="text-align: right; font-weight: bold;"><?php echo $reviews_stats['total_reviews'] ?? 0; ?></td>
                        </tr>
                        <tr>
                            <td>Durchschnittliche Bewertung:</td>
                            <td style="text-align: right; font-weight: bold;">
                                <?php echo number_format($reviews_stats['avg_rating'] ?? 0, 1); ?> ⭐
                            </td>
                        </tr>
                        <tr>
                            <td>Bewertungen mit Text:</td>
                            <td style="text-align: right; font-weight: bold;"><?php echo $reviews_stats['reviews_with_text'] ?? 0; ?></td>
                        </tr>
                        <tr>
                            <td>Angezeigt:</td>
                            <td style="text-align: right; font-weight: bold;"><?php echo $reviews_stats['shown_reviews'] ?? 0; ?></td>
                        </tr>
                    </table>
                </div>

                <!-- Sterne-Verteilung -->
                <div style="flex: 1; min-width: 250px; padding: 15px; background: #e8f5e8; border-radius: 5px; border-left: 4px solid #28a745;">
                    <h3 style="margin: 0 0 15px 0; color: #155724;">⭐ Sterne-Verteilung</h3>
                    <table style="width: 100%;">
                        <?php foreach ($rating_distribution as $rating): ?>
                            <tr>
                                <td>
                                    <?php echo str_repeat('⭐', $rating['rating']); ?> 
                                    (<?php echo $rating['rating']; ?> Sterne):
                                </td>
                                <td style="text-align: right; font-weight: bold;">
                                    <?php echo $rating['count']; ?> (<?php echo $rating['percentage']; ?>%)
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>

                <!-- Bewertungs-Quellen -->
                <div style="flex: 1; min-width: 200px; padding: 15px; background: #e3f2fd; border-radius: 5px; border-left: 4px solid #2196F3;">
                    <h3 style="margin: 0 0 15px 0; color: #0d47a1;">🔍 Top Quellen</h3>
                    <?php if (!empty($report['reviews_metrics']['sources'])): ?>
                        <table style="width: 100%; font-size: 12px;">
                            <?php foreach (array_slice($report['reviews_metrics']['sources'], 0, 5) as $source): ?>
                                <tr>
                                    <td><?php echo esc_html($source['source']); ?></td>
                                    <td style="text-align: right; font-weight: bold;">
                                        <?php echo $source['count']; ?> (Ø <?php echo number_format($source['avg_rating'], 1); ?>⭐)
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php else: ?>
                        <p style="text-align: center; color: #666;">Keine Quellen-Daten</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Bewertungs Charts -->
            <div style="display: flex; gap: 1rem; margin-bottom: 20px; flex-wrap: wrap;">
                <!-- Sterne-Verteilung Chart -->
                <div style="flex: 1; min-width: 400px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                    <h3 style="margin: 0 0 15px 0;">📊 Sterne-Verteilung (Diesen Monat)</h3>
                    <div style="height: 300px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                        <canvas id="ratingDistributionChart"></canvas>
                    </div>
                </div>

                <!-- Bewertungs-Trends -->
                <div style="flex: 1; min-width: 400px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                    <h3 style="margin: 0 0 15px 0;">📈 Bewertungs-Trends (6 Monate)</h3>
                    <div style="height: 300px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                        <canvas id="ratingTrendsChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Tägliche & Monatliche Bewertungen -->
            <div style="display: flex; gap: 1rem; margin-bottom: 20px; flex-wrap: wrap;">
                <!-- Tägliche Bewertungen (30 Tage) -->
                <div style="flex: 1; min-width: 400px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                    <h3 style="margin: 0 0 15px 0;">📅 Tägliche Bewertungen (30 Tage)</h3>
                    <div style="height: 300px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                        <canvas id="dailyReviews30dChart"></canvas>
                    </div>
                </div>

                <!-- Tägliche Bewertungen (7 Tage) -->
                <div style="flex: 1; min-width: 400px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                    <h3 style="margin: 0 0 15px 0;">📅 Tägliche Bewertungen (7 Tage)</h3>
                    <div style="height: 300px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                        <canvas id="dailyReviews7dChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Monatliche Bewertungen -->
            <div style="display: flex; gap: 1rem; margin-bottom: 20px; flex-wrap: wrap;">
                <!-- Monatliche Übersicht -->
                <div style="flex: 1; min-width: 400px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                    <h3 style="margin: 0 0 15px 0;">📅 Monatliche Bewertungen (12 Monate)</h3>
                    <div style="height: 300px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                        <canvas id="monthlyReviewsChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Top Bewertungen -->
            <div class="postbox" style="width: 100%; flex-basis: 100%">
                <div class="inside">
                    <h2 class="hndle" style="margin-bottom: 10px;"><span>🏆 Top Bewertungen (diesen Monat)</span></h2>
                    <table class="widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Bewertung</th>
                                <th>Text</th>
                                <th>Quelle</th>
                                <th>Datum</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($report['reviews_metrics']['top_reviews'])): ?>
                                <?php foreach ($report['reviews_metrics']['top_reviews'] as $review): ?>
                                    <tr>
                                        <td style="width: 100px;">
                                            <div style="font-size: 18px; color: #ffc107;">
                                                <?php echo str_repeat('⭐', $review['rating']); ?>
                                            </div>
                                            <div style="font-size: 12px; color: #666;">
                                                <?php echo $review['rating']; ?> Sterne
                                            </div>
                                        </td>
                                        <td>
                                            <div style="font-weight: 500;">
                                                <?php echo esc_html($review['review_text']); ?>
                                            </div>
                                            <?php if ($review['order_id']): ?>
                                                <div style="font-size: 11px; color: #666; margin-top: 2px;">
                                                    Bestellung #<?php echo $review['order_id']; ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="width: 150px;">
                                            <?php echo esc_html($review['referral_source']); ?>
                                        </td>
                                        <td style="width: 120px;">
                                            <?php echo date('d.m.Y', strtotime($review['created_at'])); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center;">Keine Bewertungen mit Text verfügbar</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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



    const chartData = <?php echo json_encode($report['chart_data'] ?? []); ?>;

    // 1. Tägliche Besucher (7-30 Tage)
    function createDailyVisitorsChart(canvasId, data, title) {
        if (!data || Object.keys(data).length === 0) {
            document.getElementById(canvasId).innerHTML = 
                `<div style="display: flex; justify-content: center; align-items: center; height: 100%; color: #666;">
                    Keine Daten verfügbar für ${title}
                </div>`;
            return;
        }

        const dates = Object.keys(data);
        const visitors = dates.map(date => data[date].visitors || 0);
        const pageviews = dates.map(date => data[date].page_views || 0);

        new Chart(document.getElementById(canvasId), {
            type: 'line',
            data: {
                labels: dates,
                datasets: [
                    {
                        label: 'Besucher',
                        data: visitors,
                        borderColor: '#2271b1',
                        backgroundColor: 'rgba(34, 113, 177, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Page Views',
                        data: pageviews,
                        borderColor: '#2e7d32',
                        backgroundColor: 'rgba(46, 125, 50, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: title,
                        font: {
                            size: 14,
                            weight: 'bold'
                        }
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: { 
                            display: true, 
                            text: 'Besucher' 
                        },
                        beginAtZero: true
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: { 
                            display: true, 
                            text: 'Page Views' 
                        },
                        grid: { 
                            drawOnChartArea: false 
                        },
                        beginAtZero: true
                    }
                },
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                }
            }
        });
    }

    // 1. Tägliche Besucher (30 Tage)
    createDailyVisitorsChart(
        'dailyVisitorsChart30d', 
        chartData.daily_visitors_30d,
        'Tägliche Besucher (30 Tage)'
    );
    
    // 2. Tägliche Besucher (7 Tage)
    createDailyVisitorsChart(
        'dailyVisitorsChart7d', 
        chartData.daily_visitors_7d,
        'Tägliche Besucher (7 Tage)'
    );
    


    
    // 2. Geräteverteilung
    if (chartData.device_distribution) {
        new Chart(document.getElementById('deviceDistributionChart'), {
            type: 'doughnut',
            data: {
                labels: chartData.device_distribution.map(d => d.device_type),
                datasets: [{
                    data: chartData.device_distribution.map(d => d.count),
                    backgroundColor: ['#2196F3', '#4CAF50', '#FF9800', '#9C27B0', '#607D8B']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }
    
    // 3. Browser-Verteilung
    if (chartData.browser_distribution) {
        new Chart(document.getElementById('browserDistributionChart'), {
            type: 'pie',
            data: {
                labels: chartData.browser_distribution.map(d => d.browser_name),
                datasets: [{
                    data: chartData.browser_distribution.map(d => d.count),
                    backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }
    
    // 4. Traffic-Quellen
    if (chartData.traffic_sources) {
        new Chart(document.getElementById('trafficSourcesChart'), {
            type: 'doughnut',
            data: {
                labels: chartData.traffic_sources.map(d => d.source),
                datasets: [{
                    data: chartData.traffic_sources.map(d => d.count),
                    backgroundColor: ['#4CAF50', '#2196F3', '#FF9800', '#9C27B0', '#607D8B']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }
    
    // 5. Top Deutsche Städte
    if (chartData.german_cities) {
        new Chart(document.getElementById('germanCitiesChart'), {
            type: 'bar',
            data: {
                labels: chartData.german_cities.map(d => d.city),
                datasets: [{
                    label: 'Besucher',
                    data: chartData.german_cities.map(d => d.count),
                    backgroundColor: '#2196F3'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                scales: {
                    x: { beginAtZero: true }
                }
            }
        });
    }
    
    // 6. Seiten Performance
    if (chartData.page_performance) {
        new Chart(document.getElementById('pagePerformanceChart'), {
            type: 'bar',
            data: {
                labels: chartData.page_performance.map(d => 
                    d.page_title.length > 30 ? d.page_title.substring(0, 30) + '...' : d.page_title
                ),
                datasets: [
                    {
                        label: 'Aufrufe',
                        data: chartData.page_performance.map(d => d.views),
                        backgroundColor: '#2196F3',
                        yAxisID: 'y'
                    },
                    {
                        label: 'Ø Verweildauer (s)',
                        data: chartData.page_performance.map(d => Math.round(d.avg_time_on_page)),
                        type: 'line',
                        borderColor: '#FF9800',
                        backgroundColor: 'rgba(255, 152, 0, 0.1)',
                        borderWidth: 2,
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
                        title: { display: true, text: 'Aufrufe' }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: { display: true, text: 'Verweildauer (s)' },
                        grid: { drawOnChartArea: false }
                    }
                }
            }
        });
    }

    // 7. Horizontales Balkendiagramm für Heatmap
    if (chartData.visit_heatmap && chartData.visit_heatmap.length > 0) {
        const daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        const germanDays = {
            'Monday': 'Montag',
            'Tuesday': 'Dienstag', 
            'Wednesday': 'Mittwoch',
            'Thursday': 'Donnerstag',
            'Friday': 'Freitag',
            'Saturday': 'Samstag',
            'Sunday': 'Sonntag'
        };
        
        // Gruppiere Daten nach Tagen
        const dayData = daysOfWeek.map(day => {
            const dayVisits = chartData.visit_heatmap
                .filter(item => item.day === day)
                .reduce((sum, item) => sum + parseInt(item.visits), 0);
            
            return {
                day: germanDays[day],
                totalVisits: dayVisits,
                hours: chartData.visit_heatmap
                    .filter(item => item.day === day)
                    .map(item => ({
                        hour: parseInt(item.hour),
                        visits: parseInt(item.visits)
                    }))
            };
        });

        // Sortiere nach Gesamtbesuchen (absteigend)
        dayData.sort((a, b) => b.totalVisits - a.totalVisits);

        new Chart(document.getElementById('visitHeatmapChart'), {
            type: 'bar',
            data: {
                labels: dayData.map(d => d.day),
                datasets: [{
                    label: 'Gesamt Besuche pro Tag',
                    data: dayData.map(d => d.totalVisits),
                    backgroundColor: '#2196F3',
                    borderColor: '#1976D2',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y', // Horizontale Balken
                scales: {
                    x: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Gesamt Besuche'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Wochentag'
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            afterBody: function(context) {
                                const dayIndex = context[0].dataIndex;
                                const day = dayData[dayIndex];
                                const topHours = day.hours
                                    .sort((a, b) => b.visits - a.visits)
                                    .slice(0, 3)
                                    .map(h => `${h.hour}:00 (${h.visits} Besuche)`)
                                    .join('\n');
                                
                                return ['\nTop Zeiten:', topHours];
                            }
                        }
                    }
                }
            }
        });
        
    } else {
        document.getElementById('visitHeatmapChart').innerHTML = 
            '<div style="display: flex; justify-content: center; align-items: center; height: 100%; color: #666;">Keine Heatmap-Daten verfügbar</div>';
    }
    
    // 1. Sterne-Verteilung Chart
    if (chartData.rating_distribution && chartData.rating_distribution.length > 0) {
        const ratingLabels = chartData.rating_distribution.map(r => `${r.rating} Sterne`);
        const ratingData = chartData.rating_distribution.map(r => r.count);
        const ratingColors = ['#ff6b6b', '#ffa726', '#ffee58', '#9ccc65', '#66bb6a'];
        
        new Chart(document.getElementById('ratingDistributionChart'), {
            type: 'doughnut',
            data: {
                labels: ratingLabels,
                datasets: [{
                    data: ratingData,
                    backgroundColor: ratingColors,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.raw / total) * 100).toFixed(1);
                                return `${context.label}: ${context.raw} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
    
    // 2. Bewertungs-Trends Chart (6 Monate)
    if (chartData.rating_trends_6m && chartData.rating_trends_6m.length > 0) {
        const trendsMonths = chartData.rating_trends_6m.map(t => t.month);
        const trendsRatings = chartData.rating_trends_6m.map(t => parseFloat(t.avg_rating));
        const trendsCount = chartData.rating_trends_6m.map(t => parseInt(t.review_count));
        
        new Chart(document.getElementById('ratingTrendsChart'), {
            type: 'line',
            data: {
                labels: trendsMonths,
                datasets: [
                    {
                        label: 'Durchschnittliche Bewertung',
                        data: trendsRatings,
                        borderColor: '#ffc107',
                        backgroundColor: 'rgba(255, 193, 7, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        yAxisID: 'y',
                        fill: true
                    },
                    {
                        label: 'Anzahl Bewertungen',
                        data: trendsCount,
                        borderColor: '#2196F3',
                        backgroundColor: 'rgba(33, 150, 243, 0.1)',
                        borderWidth: 2,
                        type: 'bar',
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
                        min: 1,
                        max: 5,
                        title: {
                            display: true,
                            text: 'Durchschnittliche Bewertung'
                        },
                        ticks: {
                            stepSize: 0.5
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Anzahl Bewertungen'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
    }
    
    // 3. Tägliche Bewertungen (30 Tage)
    if (chartData.reviews_daily_30d) {
        const dailyDates30d = Object.keys(chartData.reviews_daily_30d);
        const dailyReviews30d = dailyDates30d.map(date => chartData.reviews_daily_30d[date].reviews);
        const dailyRatings30d = dailyDates30d.map(date => chartData.reviews_daily_30d[date].avg_rating);
        
        new Chart(document.getElementById('dailyReviews30dChart'), {
            type: 'bar',
            data: {
                labels: dailyDates30d,
                datasets: [
                    {
                        label: 'Anzahl Bewertungen',
                        data: dailyReviews30d,
                        backgroundColor: 'rgba(33, 150, 243, 0.6)',
                        borderColor: '#2196F3',
                        borderWidth: 1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Ø Bewertung',
                        data: dailyRatings30d,
                        borderColor: '#ffc107',
                        backgroundColor: 'rgba(255, 193, 7, 0.1)',
                        borderWidth: 2,
                        type: 'line',
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
                        title: {
                            display: true,
                            text: 'Anzahl Bewertungen'
                        },
                        beginAtZero: true
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Ø Bewertung'
                        },
                        min: 1,
                        max: 5,
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
    }
    
    // 4. Tägliche Bewertungen (7 Tage)
    if (chartData.reviews_daily_7d) {
        const dailyDates7d = Object.keys(chartData.reviews_daily_7d);
        const dailyReviews7d = dailyDates7d.map(date => chartData.reviews_daily_7d[date].reviews);
        const dailyRatings7d = dailyDates7d.map(date => chartData.reviews_daily_7d[date].avg_rating);
        
        new Chart(document.getElementById('dailyReviews7dChart'), {
            type: 'bar',
            data: {
                labels: dailyDates7d,
                datasets: [
                    {
                        label: 'Anzahl Bewertungen',
                        data: dailyReviews7d,
                        backgroundColor: 'rgba(76, 175, 80, 0.6)',
                        borderColor: '#4CAF50',
                        borderWidth: 1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Ø Bewertung',
                        data: dailyRatings7d,
                        borderColor: '#ff9800',
                        backgroundColor: 'rgba(255, 152, 0, 0.1)',
                        borderWidth: 2,
                        type: 'line',
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
                        title: {
                            display: true,
                            text: 'Anzahl Bewertungen'
                        },
                        beginAtZero: true
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Ø Bewertung'
                        },
                        min: 1,
                        max: 5,
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
    }
    
    // 5. Monatliche Bewertungen (12 Monate)
    if (chartData.reviews_monthly_12m && chartData.reviews_monthly_12m.length > 0) {
        const monthlyMonths = chartData.reviews_monthly_12m.map(m => m.month);
        const monthlyReviews = chartData.reviews_monthly_12m.map(m => parseInt(m.total_reviews));
        const monthlyPositive = chartData.reviews_monthly_12m.map(m => parseInt(m.positive_reviews));
        
        new Chart(document.getElementById('monthlyReviewsChart'), {
            type: 'bar',
            data: {
                labels: monthlyMonths,
                datasets: [
                    {
                        label: 'Gesamt Bewertungen',
                        data: monthlyReviews,
                        backgroundColor: 'rgba(156, 39, 176, 0.6)',
                        borderColor: '#9C27B0',
                        borderWidth: 1
                    },
                    {
                        label: 'Positive Bewertungen (4-5⭐)',
                        data: monthlyPositive,
                        backgroundColor: 'rgba(76, 175, 80, 0.6)',
                        borderColor: '#4CAF50',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Anzahl Bewertungen'
                        }
                    }
                }
            }
        });
    }

    });
</script>