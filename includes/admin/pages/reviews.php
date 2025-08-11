<?php
if (!is_admin()) {
    return;
}

use \Utils\ReviewManager;
use Utils\VisitorTracker;
use Utils\WPStatisticsReport;

$manager = new ReviewManager();



// $report = new WPStatisticsReport();
// $topStats = $report->getTopReport('2025-01-01', '2025-08-07');

// echo '<pre>';
// print_r($topStats);
// echo '</pre>';


/**
 * GET ALL REVIEWS
 */
$reviews = $manager->getAll();
?>
<div class="wrap">

    <h1 class="wp-heading-inline">Bewertungen</h1>
    <hr class="wp-header-end">

    <h2 class="nav-tab-wrapper" style="margin-bottom: 20px;">
        <a href="#tab-stats" class="nav-tab nav-tab-active">Statistiken</a>
        <a href="#tab-reviews" class="nav-tab">Bewertungen</a>
    </h2>

    <div id="tab-stats" class="tab-content" style="display: block;">
        <?php 
        // Statistiken ausgeben
        $summary = $manager->getStatsSummary();
        $stars = $manager->getStarDistribution();
        $referrals = $manager->getReferralStats();
        ?>

        <div class="wp-list-table widefat fixed striped"> 

            <h3>Grundlegende Daten</h3>

            <div class="dashboard-widgets-wrap" style="display: flex; gap: 1rem; flex-wrap:wrap">

                <div class="postbox" style="flex: 1;">
                    
                    <div class="inside">
                        <h2 class="hndle" style="margin-bottom: 10px;"><span>Bewertungen im Überblick</span></h2>
                        <table class="widefat fixed striped">
                            <tbody>
                                <tr><td>Heute</td><td><strong><?php echo $summary['today']; ?></strong></td></tr>
                                <tr><td>Gestern</td><td><strong><?php echo $summary['yesterday']; ?></strong></td></tr>
                                <tr><td>Diese Woche</td><td><strong><?php echo $summary['this_week']; ?></strong></td></tr>
                                <tr><td>Dieser Monat</td><td><strong><?php echo $summary['this_month']; ?></strong></td></tr>
                                <tr><td>Letzter Monat</td><td><strong><?php echo $summary['last_month']; ?></strong></td></tr>
                                <tr><td>Dieses Jahr</td><td><strong><?php echo $summary['this_year']; ?></strong></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="postbox" style="flex: 1;">
                    
                    <div class="inside">
                        <h2 class="hndle" style="margin-bottom: 10px;"><span>Sterneverteilung</span></h2>
                        <table class="widefat fixed striped">
                            <tbody>
                                <?php foreach ($stars as $star => $count): ?>
                                    <tr>
                                        <td><?php echo $star; ?> Sterne</td>
                                        <td><strong><?php echo $count; ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="postbox" style="width: 100%; flex-basis: 100%">
                    
                    <div class="inside" style="max-height: 220px; overflow-y: auto;">
                        <h2 class="hndle" style="margin-bottom: 10px;"><span>Herkunft</span></h2>
                        <table class="widefat fixed striped">
                            <tbody>
                                <?php foreach ($referrals as $source => $count): ?>
                                    <tr>
                                        <td><?php echo esc_html($source); ?></td>
                                        <td><strong><?php echo $count; ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div id="tab-reviews" class="tab-content" style="display: none;">


        <div class="dashboard-widgets-wrap" style="display: flex; gap: 1rem; flex-wrap:wrap">

            <div class="postbox" style="flex: 1;">
                
                <div class="inside" style="padding: 0px 18px 4px;">
                    <h2 class="hndle" style="display: flex; justify-content: space-between;">
                        Alle Bewertungen:
                        <span><?php echo $manager->countAll(); ?></span>
                    </h2>
                </div>
            </div>

            <div class="postbox" style="flex: 1;">
                
                <div class="inside" style="padding: 0px 18px 4px;">
                    <h2 class="hndle" style="display: flex; justify-content: space-between;">
                        Angezeigte Bewertungen:
                        <span><?php echo $manager->countIsShown(); ?></span>
                    </h2>
                </div>
            </div>

            <div class="postbox" style="flex: 1;">
                
                <div class="inside" style="padding: 0px 18px 4px;">
                    <h2 class="hndle" style="display: flex; justify-content: space-between;">
                        Ausgeblendete Bewertungen:
                        <span><?php echo $manager->countIsNotShown(); ?></span>
                    </h2>
                </div>
            </div>
        </div>

        <?php if (empty($reviews)): ?>
            <p>Keine Bewertungen vorhanden.</p>
        <?php else: ?>
            <div class="reviews-list">
                <?php foreach($reviews as $review): ?>

                    <div class="postbox review-item" data-id="<?php echo $review->id; ?>"
                        style="border-left: 5px solid <?php echo $review->rating >= 4 ? '#46b450' : ($review->rating >= 3 ? '#ffb900' : '#dc3232'); ?>;">
                        <div class="inside">
                            <h2 class="hndle" style="margin: 0; display: flex; justify-content: space-between; align-items: center;">
                                <span>#<?php echo $review->id; ?> – <?php echo date('d.m.Y H:i', strtotime($review->created_at)); ?></span>
                                <span>
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <?php echo $i <= $review->rating ? '⭐' : '☆'; ?>
                                    <?php endfor; ?>
                                </span>
                            </h2>

                            <?php if (!empty($review->order) && $review->order instanceof WC_Order): ?>
                                <div style="margin-top: 15px; padding: 10px; border: 1px solid #ddd; background: #f9f9f9; border-radius: 5px;">
                                    <strong>Kundendaten zur Bestellung #<?php echo esc_html($review->order->get_id()); ?>:</strong>
                                    <ul style="list-style: none; padding-left: 0; margin: 5px 0 0 0;">
                                        <li><strong>Name:</strong> <?php echo esc_html($review->order->get_billing_first_name() . ' ' . $review->order->get_billing_last_name()); ?></li>
                                        <li><strong>E-Mail:</strong> <?php echo esc_html($review->order->get_billing_email()); ?></li>
                                        <li><strong>Telefon:</strong> <?php echo esc_html($review->order->get_billing_phone()); ?></li>
                                        <li><strong>Adresse:</strong> 
                                            <?php 
                                                echo esc_html(
                                                    trim(
                                                        $review->order->get_billing_address_1() . ' ' .
                                                        $review->order->get_billing_address_2() . ', ' .
                                                        $review->order->get_billing_postcode() . ' ' .
                                                        $review->order->get_billing_city()
                                                    )
                                                ); 
                                            ?>
                                        </li>
                                        <li style="margin-top: 8px;">
                                            <a href="<?php echo esc_url(admin_url('post.php?post=' . $review->order->get_id() . '&action=edit')); ?>" target="_blank" style="text-decoration: none; color: #0073aa;">
                                                🔗 Bestellung bearbeiten
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($review->review_text)): ?>
                                <p style="margin-top: 10px;"><?php echo esc_html($review->review_text); ?></p>
                            <?php else: ?>
                                <p style="color: #999; font-style: italic; margin-top: 10px;">Keine schriftliche Bewertung</p>
                            <?php endif; ?>

                            <?php if (!empty($review->referral_source)): ?>
                                <p style="margin-top: 10px; font-style: italic; color: #555;">
                                    Gefunden über: <strong><?php echo esc_html($review->referral_source); ?></strong>
                                </p>
                            <?php endif; ?>

                            <div style="margin-top: 10px; font-size: 12px; color: #666;">
                                Sichtbar: <span class="status-text"><?php echo $review->is_shown ? '✅ Ja' : '❌ Nein'; ?></span>
                            </div>

                            <div style="margin-top: 10px;">
                                <button class="button toggle-visibility"><?php echo $review->is_shown ? 'Ausblenden' : 'Anzeigen'; ?></button>
                                <button class="button button-danger delete-review">Löschen</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<link rel="stylesheet" href="<?php echo WHA_PLUGIN_ASSETS_URL ?>/plugins/notiflix/notiflix.min.css">
<script src="<?php echo WHA_PLUGIN_ASSETS_URL ?>/plugins/notiflix/notiflix.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {


    const list = document.querySelector('.reviews-list');
    if (!list) return;

    // Ajax-URL aus PHP (muss in PHP als global JS-Variable gesetzt werden)
    const ajaxurl = window.ajaxurl || '/wp-admin/admin-ajax.php';

    list.addEventListener('click', async (e) => {

        if (e.target.classList.contains('toggle-visibility')) {
            const card = e.target.closest('.review-item');
            const id = card.dataset.id;

            const params = new URLSearchParams();
            params.append('action', 'wha_toggle_review');
            params.append('id', id);

            try {

                Notiflix.Loading.standard('Loading...');

                const res = await fetch(ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: params.toString(),
                });

                if (!res.ok) {
                    throw new Error(`HTTP-Fehler ${res.status}`);
                }

                const data = await res.json();

                if (data.success) {
                    const statusText = card.querySelector('.status-text');
                    if (data.data.new_status == 1) {
                        statusText.textContent = '✅ Ja';
                        e.target.textContent = 'Ausblenden';
                    } else {
                        statusText.textContent = '❌ Nein';
                        e.target.textContent = 'Anzeigen';
                    }
                } else {
                    alert(data.data || 'Fehler beim Aktualisieren');
                }
            } catch (err) {
                console.error(err);
                alert('Es ist ein Fehler aufgetreten: ' + err.message);
            } finally {
                Notiflix.Loading.remove();
            }
        }

        if (e.target.classList.contains('delete-review')) {

            const confirmed = await confirmAsync(
                'Bewertung löschen',
                'Diese Bewertung wirklich löschen?'
            );

            if (!confirmed) return;

            const card = e.target.closest('.review-item');
            const id = card.dataset.id;

            const params = new URLSearchParams();
            params.append('action', 'wha_delete_review');
            params.append('id', id);

            try {

                Notiflix.Loading.standard('Loading...');

                const res = await fetch(ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: params.toString(),
                });

                if (!res.ok) {
                    throw new Error(`HTTP-Fehler ${res.status}`);
                }

                const data = await res.json();

                if (data.success) {
                    card.remove();
                } else {
                    alert(data.data || 'Fehler beim Löschen');
                }
            } catch (err) {
                console.error(err);
                alert('Es ist ein Fehler aufgetreten: ' + err.message);
            } finally {
                Notiflix.Loading.remove();
            }
        }
    });

    /**
     * confirmAsync
     */
    function confirmAsync(title, message, okText = 'Ja', cancelText = 'Nein') {
        return new Promise((resolve) => {
            Notiflix.Confirm.show(
                title,
                message,
                okText,
                cancelText,
                () => resolve(true),
                () => resolve(false)
            );
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
