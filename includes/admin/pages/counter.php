<?php
if (!is_admin()) {
    return;
}

use Utils\Tracker\Google\GoogleAuth;
use Utils\Tracker\Google\GoogleSearchConsole;
use Utils\Tracker\VisitorAnalytics;

$analyticsInstance = VisitorAnalytics::getAnalyticsInstance();

$report = $analyticsInstance->get_report_this_month();


$gsc = new GoogleSearchConsole();
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

            <?php var_dump($report); ?>

            <div class="dashboard-widgets-wrap" style="display: flex; gap: 1rem; flex-wrap:wrap">

                <div class="postbox" style="flex: 1;">
                    
                    <div class="inside">
                        <h2 class="hndle" style="margin-bottom: 10px;"><span>Besucher im Überblick</span></h2>
                        <table class="widefat fixed striped">
                            <tbody>
                                <tr><td>Heute</td><td><strong><?php echo $analyticsInstance->visitors_today(); ?></strong></td></tr>
                                <tr><td>Gestern</td><td><strong><?php echo $analyticsInstance->visitors_yesterday(); ?></strong></td></tr>
                                <tr><td>Diese Woche</td><td><strong><?php echo $analyticsInstance->visitors_this_week(); ?></strong></td></tr>
                                <tr><td>Dieser Monat</td><td><strong><?php echo $analyticsInstance->visitors_this_month(); ?></strong></td></tr>
                                <tr><td>Letzter Monat</td><td><strong><?php echo $analyticsInstance->visitors_this_month(); ?></strong></td></tr>
                                <tr><td>Dieses Jahr</td><td><strong><?php echo $analyticsInstance->visitors_this_year(); ?></strong></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="postbox" style="flex: 1;">
                    
                    <div class="inside">
                        <h2 class="hndle" style="margin-bottom: 10px;"><span>Sterneverteilung</span></h2>
                        <table class="widefat fixed striped">
                            <tbody>
                                <?php // foreach ($stars as $star => $count): ?>
                                    <tr>
                                        <td><?php // echo $star; ?> Sterne</td>
                                        <td><strong><?php // echo $count; ?></strong></td>
                                    </tr>
                                <?php // endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="postbox" style="width: 100%; flex-basis: 100%">
                    <div class="inside">
                        <h2 class="hndle" style="margin-bottom: 10px;"><span>Geräte</span></h2>
                        <?php var_dump($report["devices"]); ?>
                        <table class="widefat fixed striped">
                            <tbody>
                                <?php foreach ($report["devices"] as $key => $value): ?>
                                    <tr>
                                        <td><?php echo esc_html($value["device_type"]); ?></td>
                                        <td><strong><?php echo $value["count"]; ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>


                <div class="postbox" style="width: 100%; flex-basis: 100%">
                    <div class="inside">
                        <h2 class="hndle" style="margin-bottom: 10px;"><span>Browsers</span></h2>
                        <?php var_dump($report["browsers"]); ?>
                        <table class="widefat fixed striped">
                            <tbody>
                                <?php foreach ($report["browsers"] as $key => $value): ?>
                                    <tr>
                                        <td><?php echo esc_html($value["browser_name"]); ?></td>
                                        <td><strong><?php echo $value["count"]; ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="postbox" style="width: 100%; flex-basis: 100%">
                    <div class="inside">
                        <h2 class="hndle" style="margin-bottom: 10px;"><span>Herkunft</span></h2>
                        <?php var_dump($report["traffic_sources"]); ?>
                        <table class="widefat fixed striped">
                            <tbody>
                                <?php foreach ($report["traffic_sources"] as $key => $value): ?>
                                    <tr>
                                        <td><?php echo esc_html($value["source_name"]); ?></td>
                                        <td><strong><?php echo $value["count"]; ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>


                <div class="postbox" style="width: 100%; flex-basis: 100%">
                    <div class="inside">
                        <h2 class="hndle" style="margin-bottom: 10px;"><span>Suchmaschinen</span></h2>
                        <?php var_dump($report["search_engines"]); ?>
                        <table class="widefat fixed striped">
                            <tbody>
                                <?php foreach ($report["search_engines"] as $key => $value): ?>
                                    <tr>
                                        <td><?php echo esc_html($value["source_name"]); ?></td>
                                        <td><strong><?php echo $value["count"]; ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="postbox" style="width: 100%; flex-basis: 100%">
                    <div class="inside">
                        <h2 class="hndle" style="margin-bottom: 10px;"><span>Soziale Netzwerke</span></h2>
                        <?php var_dump($report["social_networks"]); ?>
                        <table class="widefat fixed striped">
                            <tbody>
                                <?php foreach ($report["social_networks"] as $key => $value): ?>
                                    <tr>
                                        <td><?php echo esc_html($value["source_name"]); ?></td>
                                        <td><strong><?php echo $value["count"]; ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
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
                                    
                                    $analyticsData = $gsc->getPrimaryDomainData($startDate, $endDate, ['query']);
                                    
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
                                                <?php foreach (array_slice($rows, 0, 10) as $row): ?>
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
                                        <p><em>Zeige Top 10 von <?php echo count($rows); ?> Suchbegriffen</em></p>
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
