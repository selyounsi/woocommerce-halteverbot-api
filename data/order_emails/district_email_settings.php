<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Dynamische Bezirks-CC Einstellungen pro WooCommerce Email Template
 */
add_filter('woocommerce_get_settings_email', 'wha_add_dynamic_district_settings');

function wha_add_dynamic_district_settings($settings)
{
    $new = [];

    // Aktuelles E-Mail Template aus GET
    $email_type = sanitize_text_field($_GET['section'] ?? 'default_email');

    $new[] = [
        'name' => 'Bezirksabhängige CC-Einstellungen',
        'type' => 'title',
        'desc' => 'Für einzelne Berliner Bezirke können eigene CC-E-Mails hinterlegt werden.<br>Bezirk auswählen, E-Mail eintragen und „Hinzufügen“ klicken. Änderungen werden direkt übernommen.',
        'id'   => 'wha_district_cc_settings'
    ];

    // Custom HTML-Feld → dynamische UI
    $new[] = [
        'type' => 'wha_district_cc_ui',
        'id'   => 'wha_district_email_map',
        'email_type' => $email_type
    ];

    $new[] = [
        'type' => 'sectionend',
        'id'   => 'wha_district_cc_settings'
    ];

    return array_merge($settings, $new);
}

/**
 * Renderer für das Custom-Feld
 */
add_action('woocommerce_admin_field_wha_district_cc_ui', 'wha_render_district_cc_ui');

function wha_render_district_cc_ui($field)
{
    $districts = \Utils\Districts\BerlinDistricts::getAllDistricts();
    $email_type = $field['email_type'] ?? 'default_email';
    $map = get_option("wha_district_email_map_{$email_type}", []);

    ?>
    <style>
        .wha-row { display:flex; align-items:center; margin-bottom:8px; }
        .wha-row span { width:200px; font-weight:bold; }
        .wha-row button { margin-left: 15px; }
        #wha-district-list { margin-top:15px; }
    </style>

    <h4>Neuen Bezirk hinzufügen</h4>

    <select id="wha_district_select">
        <option value="">— Bezirk auswählen —</option>
        <?php foreach ($districts as $district): ?>
            <option value="<?php echo esc_attr($district); ?>"><?php echo esc_html($district); ?></option>
        <?php endforeach; ?>
    </select>

    <input type="text" id="wha_district_email" placeholder="CC-E-Mail eintragen" style="min-width:260px;">
    <button type="button" class="button button-primary" id="wha_add_btn">Hinzufügen</button>

    <div id="wha-district-list">
        <h4>Gespeicherte Bezirke</h4>

        <?php if (!empty($map)): ?>
            <?php foreach ($map as $district => $email): ?>
                <div class="wha-row" data-district="<?php echo esc_attr($district); ?>">
                    <span><?php echo esc_html($district); ?></span>
                    <input type="text" value="<?php echo esc_attr($email); ?>" class="wha-email-input">
                    <button type="button" class="button wha-del-btn">Löschen</button>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Noch keine Einträge vorhanden.</p>
        <?php endif; ?>
    </div>

    <script>
        (function($){
            const email_type = '<?php echo esc_js($email_type); ?>';

            // Hinzufügen
            $('#wha_add_btn').on('click', function() {
                const district = $('#wha_district_select').val();
                const email = $('#wha_district_email').val();

                if (!district || !email) {
                    alert('Bezirk und E-Mail müssen ausgefüllt sein.');
                    return;
                }

                $.post(ajaxurl, {
                    action: 'wha_add_district_cc',
                    district: district,
                    email: email,
                    email_type: email_type,
                    _wpnonce: '<?php echo wp_create_nonce("wha_district_cc_nonce"); ?>'
                }, function(){
                    location.reload();
                });
            });

            // Löschen
            $('.wha-del-btn').on('click', function(){
                const row = $(this).closest('.wha-row');
                const district = row.data('district');

                $.post(ajaxurl, {
                    action: 'wha_remove_district_cc',
                    district: district,
                    email_type: email_type,
                    _wpnonce: '<?php echo wp_create_nonce("wha_district_cc_nonce"); ?>'
                }, function(){
                    location.reload();
                });
            });

            // Bearbeiten
            $('.wha-email-input').on('change', function() {
                const row = $(this).closest('.wha-row');
                const district = row.data('district');
                const email = $(this).val();

                $.post(ajaxurl, {
                    action: 'wha_update_district_cc',
                    district: district,
                    email: email,
                    email_type: email_type,
                    _wpnonce: '<?php echo wp_create_nonce("wha_district_cc_nonce"); ?>'
                });
            });

        })(jQuery);
    </script>
    <?php
}

/**
 * AJAX: Eintrag hinzufügen
 */
add_action('wp_ajax_wha_add_district_cc', function() {
    check_ajax_referer('wha_district_cc_nonce');

    $district = sanitize_text_field($_POST['district']);
    $email    = sanitize_email($_POST['email']);
    $email_type = sanitize_text_field($_POST['email_type'] ?? 'default_email');

    if (!$district || !$email) wp_die();

    $map = get_option("wha_district_email_map_{$email_type}", []);
    $map[$district] = $email;

    update_option("wha_district_email_map_{$email_type}", $map);

    wp_die();
});

/**
 * AJAX: Eintrag löschen
 */
add_action('wp_ajax_wha_remove_district_cc', function() {
    check_ajax_referer('wha_district_cc_nonce');

    $district = sanitize_text_field($_POST['district']);
    $email_type = sanitize_text_field($_POST['email_type'] ?? 'default_email');

    $map = get_option("wha_district_email_map_{$email_type}", []);

    if (isset($map[$district])) {
        unset($map[$district]);
        update_option("wha_district_email_map_{$email_type}", $map);
    }

    wp_die();
});

/**
 * AJAX: Eintrag aktualisieren (editable)
 */
add_action('wp_ajax_wha_update_district_cc', function() {
    check_ajax_referer('wha_district_cc_nonce');

    $district = sanitize_text_field($_POST['district']);
    $email    = sanitize_email($_POST['email']);
    $email_type = sanitize_text_field($_POST['email_type'] ?? 'default_email');

    if (!$district || !$email) wp_die();

    $map = get_option("wha_district_email_map_{$email_type}", []);
    $map[$district] = $email;

    update_option("wha_district_email_map_{$email_type}", $map);

    wp_die();
});
