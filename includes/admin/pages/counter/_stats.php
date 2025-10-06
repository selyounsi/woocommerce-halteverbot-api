<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        <!-- ORDER ANALYTICS SECTION -->                  
        <?php require __DIR__ . "/stats/order-analytics.php"; ?>


        <!-- VISITOR ANALYTICS CHARTS -->
        <?php require __DIR__ . "/stats/visitor-analytics.php"; ?>


        <!-- WooCommerce Metrics -->
        <?php require __DIR__ . "/stats/woocommerce-analytics.php"; ?>


        <!-- BEWERTUNGS ANALYTICS -->
        <?php require __DIR__ . "/stats/reviews-analytics.php"; ?>

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