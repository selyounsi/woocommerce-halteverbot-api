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
    <h3>Grundlegende Daten</h3>

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
                
                <!-- WC Overview -->
                <div style="display: flex; gap: 1rem; margin-bottom: 20px;">
                    <div style="flex: 1; text-align: center; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                        <h3 style="margin: 0 0 5px 0; font-size: 14px;">Conversion Rate</h3>
                        <div style="font-size: 24px; font-weight: bold; color: #2271b1;">
                            <?php echo $report['wc_metrics']['conversion_rate']; ?>%
                        </div>
                    </div>
                    <div style="flex: 1; text-align: center; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                        <h3 style="margin: 0 0 5px 0; font-size: 14px;">Umsatz (alle)</h3>
                        <div style="font-size: 24px; font-weight: bold; color: #2271b1;">
                            <?php echo number_format($report['wc_metrics']['revenue'], 2, ',', '.'); ?> €
                        </div>
                    </div>
                    <div style="flex: 1; text-align: center; padding: 15px; background: #e8f5e8; border-radius: 5px;">
                        <h3 style="margin: 0 0 5px 0; font-size: 14px;">Bestätigter Umsatz</h3>
                        <div style="font-size: 24px; font-weight: bold; color: #2e7d32;">
                            <?php echo number_format($report['wc_metrics']['confirmed_revenue'], 2, ',', '.'); ?> €
                        </div>
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

        <!-- Keywords -->
        <div class="postbox" style="width: 100%; flex-basis: 100%">
            <div class="inside">
                <h2 class="hndle" style="margin-bottom: 10px;"><span>Keywords</span></h2>
                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Keyword</th>
                            <th>Sessions</th>
                            <th>Anteil</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report["keywords"] as $keyword): ?>
                            <tr>
                                <td><?php echo esc_html($keyword["keywords"]); ?></td>
                                <td><strong><?php echo $keyword["count"]; ?></strong></td>
                                <td><?php echo $keyword["percentage"]; ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- GSC Keywords -->
        <div class="postbox" style="width: 100%; flex-basis: 100%">
            <div class="inside">
                <h2 class="hndle" style="margin-bottom: 10px;"><span>Google Search Console Keywords</span></h2>
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

<script>
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
