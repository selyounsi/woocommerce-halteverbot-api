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
        <?php require __DIR__ . "/_stats.php" ?>
    </div>

    <div id="tab-gsc" class="tab-content" style="display: none;">
        <?php require __DIR__ . "/_gsc.php" ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
