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
                            <?php echo $report['wc_metrics']['funnel']['view_to_cart']['percentage']; ?>%
                        </td>
                    </tr>
                    <tr>
                        <td>Cart → Checkout:</td>
                        <td style="text-align: right; font-weight: bold;">
                            <?php echo $report['wc_metrics']['funnel']['cart_to_checkout']['percentage']; ?>%
                        </td>
                    </tr>
                    <tr>
                        <td>Checkout → Order:</td>
                        <td style="text-align: right; font-weight: bold;">
                            <?php echo $report['wc_metrics']['funnel']['checkout_to_order']['percentage']; ?>%
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
                <div style="font-size: 12px; color: #666; margin-bottom: 10px; background: white; padding: 8px; border-radius: 4px; border-left: 3px solid #2196F3;">
                    <strong>Verlauf der Kaufabschlüsse:</strong> Zeigt, wie viele Besucher jede Stufe des Bestellprozesses erreichen
                </div>
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
                        <?php echo $report['wc_metrics']['funnel']['view_to_cart']['percentage']; ?>,
                        <?php echo $report['wc_metrics']['funnel']['cart_to_checkout']['percentage']; ?>,
                        <?php echo $report['wc_metrics']['funnel']['checkout_to_order']['percentage']; ?>
                    ],
                    backgroundColor: ['#4CAF50', '#2196F3', '#FF9800'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const percentages = [
                                    <?php echo $report['wc_metrics']['funnel']['view_to_cart']['percentage']; ?>,
                                    <?php echo $report['wc_metrics']['funnel']['cart_to_checkout']['percentage']; ?>,
                                    <?php echo $report['wc_metrics']['funnel']['checkout_to_order']['percentage']; ?>
                                ];
                                const fromCounts = [
                                    <?php echo $report['wc_metrics']['funnel']['view_to_cart']['from_count']; ?>,
                                    <?php echo $report['wc_metrics']['funnel']['cart_to_checkout']['from_count']; ?>,
                                    <?php echo $report['wc_metrics']['funnel']['checkout_to_order']['from_count']; ?>
                                ];
                                const conversionCounts = [
                                    <?php echo $report['wc_metrics']['funnel']['view_to_cart']['conversion_count']; ?>,
                                    <?php echo $report['wc_metrics']['funnel']['cart_to_checkout']['conversion_count']; ?>,
                                    <?php echo $report['wc_metrics']['funnel']['checkout_to_order']['conversion_count']; ?>
                                ];
                                
                                const index = context.dataIndex;
                                return [
                                    `Conversion Rate: ${percentages[index]}%`,
                                    `Absolute Zahlen: ${conversionCounts[index]} von ${fromCounts[index]}`
                                ];
                            }
                        }
                    }
                },
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
</script>