<?php
if (!is_admin()) {
    return;
}

use Utils\Mailer\MailerService;
use Utils\Mailer\MailerLog;
use Utils\Mailer\MailerSettings;

$mailerLog = new MailerLog();
$errors    = [];
$old       = [
    'subject'      => '',
    'body'         => '',
    'sandbox'      => true,
    'test_address' => MailerSettings::getTestAddress(),
    'statuses'     => [],
    'manual'       => '',
    'all_customers'=> false,
];

/* =====================================================================
 * SAVE / SEND HANDLER
 * ================================================================== */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['action'])
    && $_POST['action'] === 'wha_mailer_compose'
) {
    if (!current_user_can('manage_woocommerce')) {
        wp_die('Keine Berechtigung.');
    }
    check_admin_referer('wha_mailer_compose');

    $subject       = sanitize_text_field(wp_unslash($_POST['subject'] ?? ''));
    $body          = wp_kses_post(wp_unslash($_POST['body'] ?? ''));
    $sandbox       = isset($_POST['sandbox']);
    $test_address  = sanitize_email(wp_unslash($_POST['test_address'] ?? ''));
    $attachments   = array_filter(array_map('intval', explode(',', (string) ($_POST['attachment_ids'] ?? ''))));

    $statuses      = array_values(array_filter(array_map('sanitize_text_field', (array) ($_POST['statuses'] ?? []))));
    $customers     = array_values(array_filter(array_map('intval', (array) ($_POST['recipients_customers'] ?? []))));
    $manual_raw    = trim(wp_unslash($_POST['manual_emails'] ?? ''));
    $manual        = array_values(array_filter(array_map('trim', preg_split('/[\s,;]+/', $manual_raw))));
    $all_customers = isset($_POST['all_customers']);

    // Werte für Re-Population merken
    $old = [
        'subject'       => $subject,
        'body'          => $body,
        'sandbox'       => $sandbox,
        'test_address'  => $test_address ?: MailerSettings::getTestAddress(),
        'statuses'      => $statuses,
        'manual'        => $manual_raw,
        'all_customers' => $all_customers,
    ];

    // Validierung
    if ($subject === '') {
        $errors[] = 'Bitte einen Betreff angeben.';
    }
    if (trim(wp_strip_all_tags($body)) === '') {
        $errors[] = 'Bitte einen Inhalt angeben.';
    }
    if (empty($statuses) && empty($customers) && empty($manual) && !$all_customers) {
        $errors[] = 'Bitte mindestens eine Empfängergruppe auswählen.';
    }
    if ($sandbox && !is_email($test_address)) {
        $test_address = MailerSettings::getTestAddress();
    }

    if (empty($errors)) {
        if ($sandbox && is_email($test_address)) {
            MailerSettings::setTestAddress($test_address);
        }

        $targeting = [
            'statuses'      => $statuses,
            'customers'     => $customers,
            'manual'        => $manual,
            'all_customers' => $all_customers,
        ];

        $result = (new MailerService())->createAndSend([
            'subject'           => $subject,
            'body'              => $body,
            'attachments'       => $attachments,
            'sandbox'           => $sandbox,
            'test_address'      => $test_address,
            'targeting'         => $targeting,
            'targeting_summary' => function_exists('wha_mailer_targeting_summary')
                ? wha_mailer_targeting_summary($targeting)
                : [],
        ]);

        if (!empty($result['success'])) {
            wp_redirect(add_query_arg([
                'page'     => 'halteverbot-app-mailer',
                'sent'     => !empty($result['sandbox']) ? 'sandbox' : '1',
                'count'    => (int) ($result['count'] ?? 0),
                'campaign' => (int) ($result['campaign_id'] ?? 0),
            ], admin_url('admin.php')));
            exit;
        }

        $errors[] = $result['message'] ?? 'Versand fehlgeschlagen.';
    }
}

/* =====================================================================
 * ASSETS
 * ================================================================== */
wp_enqueue_media();
wp_enqueue_script('wc-enhanced-select');
wp_enqueue_style('select2');
wp_enqueue_style('woocommerce_admin_styles');

$test_nonce = wp_create_nonce('wha_mailer_test');

/* =====================================================================
 * DETAIL-ANSICHT EINER KAMPAGNE
 * ================================================================== */
$view_campaign = isset($_GET['campaign'], $_GET['view']) && $_GET['view'] === 'detail'
    ? $mailerLog->getCampaign((int) $_GET['campaign'])
    : null;

/**
 * Kleiner Helfer: farbiges Status-Badge.
 */
if (!function_exists('wha_mailer_badge')):
function wha_mailer_badge($status)
{
    $map = [
        'sent'      => ['#46b450', 'Gesendet'],
        'failed'    => ['#dc3232', 'Fehler'],
        'queued'    => ['#999', 'In Warteschlange'],
        'sandbox'   => ['#e0a800', 'Sandbox'],
        'completed' => ['#46b450', 'Abgeschlossen'],
        'processing'=> ['#0073aa', 'Läuft'],
    ];
    [$color, $label] = $map[$status] ?? ['#777', ucfirst((string) $status)];
    return '<span style="display:inline-block;padding:2px 8px;border-radius:10px;font-size:11px;color:#fff;background:' . $color . ';">' . esc_html($label) . '</span>';
}
endif;
?>

<div class="wrap">

    <h1 class="wp-heading-inline">E-Mail Versand</h1>
    <hr class="wp-header-end">

    <?php if (isset($_GET['sent'])): ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php if ($_GET['sent'] === 'sandbox'): ?>
                    ✅ <strong>Sandbox:</strong> Es wurde eine Test-Mail an deine Test-Adresse gesendet.
                    <?php echo isset($_GET['count']) ? (int) $_GET['count'] . ' Empfänger wurden protokolliert (kein echter Versand).' : ''; ?>
                <?php else: ?>
                    ✅ Versand gestartet an <strong><?php echo isset($_GET['count']) ? (int) $_GET['count'] : 0; ?></strong> Empfänger.
                    Der Versand läuft im Hintergrund – den Fortschritt siehst du im Verlauf.
                <?php endif; ?>
            </p>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="notice notice-error">
            <p><strong>Bitte korrigieren:</strong></p>
            <ul style="list-style:disc;margin-left:20px;">
                <?php foreach ($errors as $e): ?>
                    <li><?php echo esc_html($e); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($view_campaign): ?>

        <!-- ================= DETAIL ================= -->
        <p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=halteverbot-app-mailer#tab-history')); ?>" class="button">&larr; Zurück zum Verlauf</a>
        </p>

        <div class="postbox">
            <div class="inside">
                <h2 class="hndle"><span><?php echo esc_html($view_campaign->subject); ?></span></h2>
                <table class="widefat striped" style="margin-bottom:15px;">
                    <tbody>
                        <tr><td style="width:200px;"><strong>Datum</strong></td><td><?php echo esc_html(date('d.m.Y H:i', strtotime($view_campaign->created_at))); ?></td></tr>
                        <tr><td><strong>Status</strong></td><td><?php echo wha_mailer_badge($view_campaign->status); ?></td></tr>
                        <tr><td><strong>Modus</strong></td><td><?php echo $view_campaign->sandbox ? '🧪 Sandbox (Test)' : '📨 Live-Versand'; ?></td></tr>
                        <tr><td><strong>Zielauswahl</strong></td><td><?php echo esc_html(wha_mailer_format_targeting($view_campaign->targeting)); ?></td></tr>
                        <tr><td><strong>Empfänger gesamt</strong></td><td><?php echo (int) $view_campaign->total; ?></td></tr>
                        <tr><td><strong>Gesendet / Fehler</strong></td><td><span style="color:#46b450;"><?php echo (int) $view_campaign->sent; ?></span> / <span style="color:#dc3232;"><?php echo (int) $view_campaign->failed; ?></span></td></tr>
                    </tbody>
                </table>

                <h3>Empfänger</h3>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>E-Mail</th>
                            <th>Name</th>
                            <th>Bestellung</th>
                            <th>Status</th>
                            <th>Zeitpunkt</th>
                            <th>Fehler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mailerLog->getRecipients((int) $view_campaign->id) as $r): ?>
                            <tr>
                                <td><?php echo esc_html($r->email); ?></td>
                                <td><?php echo esc_html($r->name); ?></td>
                                <td>
                                    <?php if ((int) $r->order_id > 0): ?>
                                        <a href="<?php echo esc_url(admin_url('post.php?post=' . (int) $r->order_id . '&action=edit')); ?>" target="_blank">#<?php echo (int) $r->order_id; ?></a>
                                    <?php else: ?>—<?php endif; ?>
                                </td>
                                <td><?php echo wha_mailer_badge($r->status); ?></td>
                                <td><?php echo $r->processed_at ? esc_html(date('d.m.Y H:i', strtotime($r->processed_at))) : '—'; ?></td>
                                <td style="color:#dc3232;font-size:12px;"><?php echo esc_html((string) $r->error); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php else: ?>

        <h2 class="nav-tab-wrapper" style="margin-bottom:20px;">
            <a href="#tab-compose" class="nav-tab nav-tab-active">Verfassen</a>
            <a href="#tab-history" class="nav-tab">Verlauf</a>
        </h2>

        <!-- ================= COMPOSE ================= -->
        <div id="tab-compose" class="tab-content" style="display:block;">
            <form method="post" action="">
                <?php wp_nonce_field('wha_mailer_compose'); ?>
                <input type="hidden" name="action" value="wha_mailer_compose">

                <div style="display:flex;gap:20px;flex-wrap:wrap;align-items:flex-start;">

                    <!-- Linke Spalte: Inhalt -->
                    <div class="postbox" style="flex:2;min-width:480px;">
                        <div class="inside">
                            <h2 class="hndle"><span>Nachricht</span></h2>

                            <p>
                                <label for="wha_subject"><strong>Betreff</strong></label><br>
                                <input type="text" id="wha_subject" name="subject" class="large-text"
                                       value="<?php echo esc_attr($old['subject']); ?>"
                                       placeholder="z. B. Wichtige Info zu Ihrer Bestellung">
                            </p>

                            <p><label><strong>Inhalt</strong></label></p>
                            <?php
                            wp_editor(
                                $old['body'],
                                'wha_body',
                                [
                                    'textarea_name' => 'body',
                                    'media_buttons' => false,
                                    'textarea_rows' => 12,
                                    'teeny'         => false,
                                ]
                            );
                            ?>
                            <p class="description" style="margin-top:8px;">
                                Platzhalter: <code>{name}</code>, <code>{first_name}</code>, <code>{email}</code>,
                                <code>{order_number}</code> werden je Empfänger ersetzt.
                            </p>

                            <hr>

                            <p><label><strong>Anhänge</strong></label></p>
                            <input type="hidden" name="attachment_ids" id="wha_attachment_ids" value="">
                            <ul id="wha_attachment_list" style="margin:0 0 10px;"></ul>
                            <button type="button" class="button" id="wha_add_attachment">📎 Datei(en) hinzufügen</button>
                        </div>
                    </div>

                    <!-- Rechte Spalte: Empfänger + Versand -->
                    <div class="postbox" style="flex:1;min-width:320px;">
                        <div class="inside">
                            <h2 class="hndle"><span>Empfänger</span></h2>

                            <p><strong>Nach Bestellstatus</strong></p>
                            <div style="max-height:220px;overflow:auto;border:1px solid #e0e0e0;border-radius:4px;padding:8px 10px;margin-bottom:14px;">
                                <?php
                                $statuses = function_exists('wc_get_order_statuses') ? wc_get_order_statuses() : [];
                                if (empty($statuses)) {
                                    echo '<em>WooCommerce nicht aktiv.</em>';
                                }
                                foreach ($statuses as $key => $label):
                                    $clean = (strpos($key, 'wc-') === 0) ? substr($key, 3) : $key;
                                    $count = function_exists('wc_orders_count') ? (int) wc_orders_count($clean) : 0;
                                ?>
                                    <label style="display:block;margin-bottom:4px;">
                                        <input type="checkbox" name="statuses[]" value="<?php echo esc_attr($key); ?>"
                                            <?php echo in_array($key, $old['statuses'], true) ? 'checked' : ''; ?>>
                                        <?php echo esc_html($label); ?>
                                        <span style="color:#888;">(<?php echo $count; ?>)</span>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                            <p><strong>Bestimmte Kunden</strong></p>
                            <select class="wc-customer-search" multiple="multiple" name="recipients_customers[]"
                                    data-placeholder="Kunde suchen (Name oder E-Mail)…"
                                    data-action="woocommerce_json_search_customers"
                                    style="width:100%;margin-bottom:14px;"></select>

                            <p><strong>Manuelle Adressen</strong></p>
                            <textarea name="manual_emails" rows="3" class="widefat"
                                      placeholder="eine E-Mail pro Zeile oder per Komma getrennt"
                                      style="margin-bottom:14px;"><?php echo esc_textarea($old['manual']); ?></textarea>

                            <label style="display:block;margin-bottom:14px;">
                                <input type="checkbox" name="all_customers" value="1" <?php checked($old['all_customers']); ?>>
                                <strong>Alle Kunden</strong> (alle bisherigen Besteller)
                            </label>

                            <hr>

                            <h2 class="hndle"><span>Versand</span></h2>

                            <label style="display:block;margin-bottom:10px;">
                                <input type="checkbox" name="sandbox" id="wha_sandbox" value="1" <?php checked($old['sandbox']); ?>>
                                🧪 <strong>Sandbox / Testmodus</strong>
                            </label>
                            <p class="description" style="margin-top:0;">
                                Im Testmodus geht <strong>nichts an echte Kunden</strong> – es wird nur eine Beispiel-Mail
                                an die Test-Adresse gesendet, die Empfängerliste aber protokolliert.
                            </p>

                            <p id="wha_test_address_wrap">
                                <label for="wha_test_address"><strong>Test-Adresse</strong></label><br>
                                <input type="email" id="wha_test_address" name="test_address" class="widefat"
                                       value="<?php echo esc_attr($old['test_address']); ?>">
                            </p>

                            <p style="margin-top:18px;display:flex;gap:8px;flex-wrap:wrap;">
                                <button type="submit" class="button button-primary button-hero" style="flex:1;">Senden</button>
                                <button type="button" class="button" id="wha_send_test" title="Sofortige Testmail an die Test-Adresse">Testmail</button>
                            </p>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- ================= HISTORY ================= -->
        <div id="tab-history" class="tab-content" style="display:none;">
            <?php $campaigns = $mailerLog->getCampaigns(100); ?>
            <?php if (empty($campaigns)): ?>
                <p>Noch keine E-Mails versendet.</p>
            <?php else: ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>Datum</th>
                            <th>Betreff</th>
                            <th>Ziel</th>
                            <th>Empfänger</th>
                            <th>Gesendet</th>
                            <th>Fehler</th>
                            <th>Modus</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($campaigns as $c): ?>
                            <tr>
                                <td><?php echo esc_html(date('d.m.Y H:i', strtotime($c->created_at))); ?></td>
                                <td><strong><?php echo esc_html($c->subject); ?></strong></td>
                                <td style="font-size:12px;color:#555;"><?php echo esc_html(wha_mailer_format_targeting($c->targeting)); ?></td>
                                <td><?php echo (int) $c->total; ?></td>
                                <td style="color:#46b450;"><?php echo (int) $c->sent; ?></td>
                                <td style="color:<?php echo ((int) $c->failed > 0) ? '#dc3232' : '#999'; ?>;"><?php echo (int) $c->failed; ?></td>
                                <td><?php echo $c->sandbox ? '🧪' : '📨'; ?></td>
                                <td><?php echo wha_mailer_badge($c->status); ?></td>
                                <td>
                                    <a class="button button-small"
                                       href="<?php echo esc_url(admin_url('admin.php?page=halteverbot-app-mailer&view=detail&campaign=' . (int) $c->id)); ?>">
                                        Details
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    <?php endif; ?>
</div>

<link rel="stylesheet" href="<?php echo WHA_PLUGIN_ASSETS_URL; ?>/plugins/notiflix/notiflix.min.css">
<script src="<?php echo WHA_PLUGIN_ASSETS_URL; ?>/plugins/notiflix/notiflix.min.js"></script>

<script>
(function () {
    const ajaxurl   = window.ajaxurl || '/wp-admin/admin-ajax.php';
    const testNonce = '<?php echo esc_js($test_nonce); ?>';

    /* ---------------- Tabs ---------------- */
    const tabs     = document.querySelectorAll('.nav-tab-wrapper .nav-tab');
    const contents = document.querySelectorAll('.tab-content');
    function activateTab(hash) {
        let target = hash || (tabs[0] && tabs[0].getAttribute('href'));
        if (!target) return;
        tabs.forEach(t => t.classList.remove('nav-tab-active'));
        contents.forEach(c => c.style.display = 'none');
        const tab = Array.from(tabs).find(t => t.getAttribute('href') === target);
        const content = document.querySelector(target);
        if (tab && content) { tab.classList.add('nav-tab-active'); content.style.display = 'block'; }
    }
    tabs.forEach(tab => tab.addEventListener('click', function (e) {
        e.preventDefault();
        const target = this.getAttribute('href');
        activateTab(target);
        history.replaceState(null, '', target);
    }));
    if (tabs.length) activateTab(window.location.hash);

    /* ---------------- Sandbox-Feld ---------------- */
    const sandbox = document.getElementById('wha_sandbox');
    const testWrap = document.getElementById('wha_test_address_wrap');
    function toggleTest() {
        if (sandbox && testWrap) testWrap.style.opacity = sandbox.checked ? '1' : '0.5';
    }
    if (sandbox) { sandbox.addEventListener('change', toggleTest); toggleTest(); }

    /* ---------------- Anhänge (Media Library) ---------------- */
    const addBtn   = document.getElementById('wha_add_attachment');
    const idsInput = document.getElementById('wha_attachment_ids');
    const list     = document.getElementById('wha_attachment_list');
    const selected = new Map();
    let frame;

    function render() {
        idsInput.value = Array.from(selected.keys()).join(',');
        list.innerHTML = '';
        selected.forEach((title, id) => {
            const li = document.createElement('li');
            li.style.cssText = 'display:flex;justify-content:space-between;align-items:center;padding:4px 8px;background:#f6f7f7;border:1px solid #e0e0e0;border-radius:3px;margin-bottom:4px;';
            li.innerHTML = '<span>📄 ' + title + '</span>';
            const rm = document.createElement('a');
            rm.href = '#'; rm.textContent = '✕'; rm.style.cssText = 'color:#dc3232;text-decoration:none;font-weight:bold;';
            rm.addEventListener('click', ev => { ev.preventDefault(); selected.delete(id); render(); });
            li.appendChild(rm);
            list.appendChild(li);
        });
    }

    if (addBtn) {
        addBtn.addEventListener('click', function (e) {
            e.preventDefault();
            if (frame) { frame.open(); return; }
            frame = wp.media({ title: 'Anhänge wählen', multiple: true, button: { text: 'Hinzufügen' } });
            frame.on('select', function () {
                frame.state().get('selection').toJSON().forEach(a => selected.set(a.id, a.filename || a.title || ('#' + a.id)));
                render();
            });
            frame.open();
        });
    }

    /* ---------------- Testmail ---------------- */
    const testBtn = document.getElementById('wha_send_test');
    if (testBtn) {
        testBtn.addEventListener('click', async function () {
            const to = (document.getElementById('wha_test_address') || {}).value || '';
            const subject = (document.getElementById('wha_subject') || {}).value || '';
            let body = '';
            if (window.tinymce && window.tinymce.get('wha_body')) {
                body = window.tinymce.get('wha_body').getContent();
            } else {
                const ta = document.getElementById('wha_body');
                body = ta ? ta.value : '';
            }

            const params = new URLSearchParams();
            params.append('action', 'wha_mailer_test');
            params.append('nonce', testNonce);
            params.append('to', to);
            params.append('subject', subject);
            params.append('body', body);
            params.append('attachment_ids', idsInput ? idsInput.value : '');

            try {
                Notiflix.Loading.standard('Sende Testmail…');
                const res = await fetch(ajaxurl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: params.toString(),
                });
                const data = await res.json();
                if (data.success) {
                    Notiflix.Notify.success(data.data.message || 'Testmail gesendet.');
                } else {
                    Notiflix.Notify.failure(typeof data.data === 'string' ? data.data : 'Fehler beim Senden.');
                }
            } catch (err) {
                Notiflix.Notify.failure('Fehler: ' + err.message);
            } finally {
                Notiflix.Loading.remove();
            }
        });
    }
})();
</script>
