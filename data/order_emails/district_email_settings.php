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
        .wha-container {
            background: #f8f9fa;
            border: 1px solid #e2e4e7;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
        }
        .wha-form-row {
            display: flex;
            gap: 12px;
            align-items: end;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .wha-form-group {
            flex: 1;
            min-width: 200px;
        }
        .wha-form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #1d2327;
        }
        .wha-form-group select,
        .wha-form-group input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #8c8f94;
            border-radius: 4px;
            font-size: 14px;
        }
        .wha-add-btn {
            background: #2271b1;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            height: 38px;
        }
        .wha-add-btn:hover {
            background: #135e96;
        }
        .wha-add-btn:disabled {
            background: #a7aaad;
            cursor: not-allowed;
        }
        .wha-districts-list {
            margin-top: 20px;
        }
        .wha-district-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: white;
            border: 1px solid #dcdcde;
            border-radius: 4px;
            margin-bottom: 8px;
            transition: all 0.2s ease;
        }
        .wha-district-item:hover {
            border-color: #2271b1;
        }
        .wha-district-name {
            font-weight: 600;
            color: #1d2327;
            min-width: 180px;
        }
        .wha-email-input {
            flex: 1;
            padding: 6px 8px;
            border: 1px solid #dcdcde;
            border-radius: 4px;
            font-size: 14px;
            max-width: 300px;
        }
        .wha-email-input:focus {
            border-color: #2271b1;
            outline: none;
            box-shadow: 0 0 0 1px #2271b1;
        }
        .wha-delete-btn {
            background: #d63638;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        .wha-delete-btn:hover {
            background: #b32d2e;
        }
        .wha-empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #646970;
            font-style: italic;
        }
        .wha-success-message {
            background: #d1e7dd;
            color: #0f5132;
            padding: 8px 12px;
            border-radius: 4px;
            margin-bottom: 15px;
            display: none;
        }
        .wha-error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 8px 12px;
            border-radius: 4px;
            margin-bottom: 15px;
            display: none;
        }
        .wha-loading {
            opacity: 0.6;
            pointer-events: none;
        }
    </style>

    <div class="wha-container">
        <div id="wha-success-message" class="wha-success-message"></div>
        <div id="wha-error-message" class="wha-error-message"></div>

        <div class="wha-form-row">
            <div class="wha-form-group">
                <label for="wha_district_select">Bezirk auswählen</label>
                <select id="wha_district_select">
                    <option value="">— Bezirk auswählen —</option>
                    <?php foreach ($districts as $district): ?>
                        <?php if (!isset($map[$district])): ?>
                            <option value="<?php echo esc_attr($district); ?>"><?php echo esc_html($district); ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="wha-form-group">
                <label for="wha_district_email">CC-E-Mail</label>
                <input type="text" id="wha_district_email" placeholder="email@example.com">
            </div>
            
            <div class="wha-form-group">
                <button type="button" class="wha-add-btn" id="wha_add_btn">Hinzufügen</button>
            </div>
        </div>

        <div class="wha-districts-list">
            <h4>Gespeicherte Bezirke</h4>
            
            <div id="wha-district-list">
                <?php if (!empty($map)): ?>
                    <?php foreach ($map as $district => $email): ?>
                        <div class="wha-district-item" data-district="<?php echo esc_attr($district); ?>">
                            <span class="wha-district-name"><?php echo esc_html($district); ?></span>
                            <input type="text" value="<?php echo esc_attr($email); ?>" class="wha-email-input" placeholder="CC-E-Mail">
                            <button type="button" class="wha-delete-btn">Löschen</button>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="wha-empty-state">Noch keine Einträge vorhanden</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        (function($){
            const email_type = '<?php echo esc_js($email_type); ?>';
            let isProcessing = false;

            // Nachrichten anzeigen
            function showMessage(message, type = 'success') {
                const messageEl = type === 'success' ? $('#wha-success-message') : $('#wha-error-message');
                const otherMessageEl = type === 'success' ? $('#wha-error-message') : $('#wha-success-message');
                
                otherMessageEl.hide();
                messageEl.text(message).show();
                
                setTimeout(() => {
                    messageEl.fadeOut();
                }, 3000);
            }

            // UI aktualisieren nach Änderungen
            function updateUI() {
                const $list = $('#wha-district-list');
                const $select = $('#wha_district_select');
                
                // AJAX Call um aktuelle Daten zu holen
                $.post(ajaxurl, {
                    action: 'wha_get_district_cc',
                    email_type: email_type,
                    _wpnonce: '<?php echo wp_create_nonce("wha_district_cc_nonce"); ?>'
                }, function(response) {
                    if (response.success) {
                        const map = response.data;
                        $list.empty();
                        
                        if (Object.keys(map).length === 0) {
                            $list.append('<div class="wha-empty-state">Noch keine Einträge vorhanden</div>');
                        } else {
                            Object.entries(map).forEach(([district, email]) => {
                                const item = $(`
                                    <div class="wha-district-item" data-district="${district}">
                                        <span class="wha-district-name">${district}</span>
                                        <input type="text" value="${email}" class="wha-email-input" placeholder="CC-E-Mail">
                                        <button type="button" class="wha-delete-btn">Löschen</button>
                                    </div>
                                `);
                                $list.append(item);
                            });
                        }
                        
                        // Select Optionen aktualisieren
                        $select.find('option').not(':first').remove();
                        const allDistricts = <?php echo json_encode($districts); ?>;
                        allDistricts.forEach(district => {
                            if (!map.hasOwnProperty(district)) {
                                $select.append(`<option value="${district}">${district}</option>`);
                            }
                        });
                        
                        bindEventHandlers();
                    }
                });
            }

            // Event Handler binden
            function bindEventHandlers() {
                // Löschen
                $('.wha-delete-btn').off('click').on('click', function(){
                    if (isProcessing) return;
                    
                    const $item = $(this).closest('.wha-district-item');
                    const district = $item.data('district');
                    
                    isProcessing = true;
                    $item.addClass('wha-loading');
                    
                    $.post(ajaxurl, {
                        action: 'wha_remove_district_cc',
                        district: district,
                        email_type: email_type,
                        _wpnonce: '<?php echo wp_create_nonce("wha_district_cc_nonce"); ?>'
                    }, function(response) {
                        isProcessing = false;
                        $item.removeClass('wha-loading');
                        
                        if (response.success) {
                            showMessage('Bezirk erfolgreich entfernt');
                            updateUI();
                        } else {
                            showMessage('Fehler beim Entfernen des Bezirks', 'error');
                        }
                    }).fail(function() {
                        isProcessing = false;
                        $item.removeClass('wha-loading');
                        showMessage('Netzwerkfehler', 'error');
                    });
                });

                // Bearbeiten
                $('.wha-email-input').off('change').on('change', function() {
                    if (isProcessing) return;
                    
                    const $item = $(this).closest('.wha-district-item');
                    const district = $item.data('district');
                    const email = $(this).val();
                    
                    if (!email || !isValidEmail(email)) {
                        showMessage('Bitte eine gültige E-Mail-Adresse eingeben', 'error');
                        $(this).val('');
                        return;
                    }
                    
                    isProcessing = true;
                    $item.addClass('wha-loading');
                    
                    $.post(ajaxurl, {
                        action: 'wha_update_district_cc',
                        district: district,
                        email: email,
                        email_type: email_type,
                        _wpnonce: '<?php echo wp_create_nonce("wha_district_cc_nonce"); ?>'
                    }, function(response) {
                        isProcessing = false;
                        $item.removeClass('wha-loading');
                        
                        if (response.success) {
                            showMessage('E-Mail erfolgreich aktualisiert');
                        } else {
                            showMessage('Fehler beim Aktualisieren der E-Mail', 'error');
                        }
                    }).fail(function() {
                        isProcessing = false;
                        $item.removeClass('wha-loading');
                        showMessage('Netzwerkfehler', 'error');
                    });
                });
            }

            // E-Mail Validierung
            function isValidEmail(email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(email);
            }

            // Hinzufügen
            $('#wha_add_btn').on('click', function() {
                if (isProcessing) return;
                
                const district = $('#wha_district_select').val();
                const email = $('#wha_district_email').val();

                if (!district) {
                    showMessage('Bitte einen Bezirk auswählen', 'error');
                    return;
                }

                if (!email || !isValidEmail(email)) {
                    showMessage('Bitte eine gültige E-Mail-Adresse eingeben', 'error');
                    return;
                }

                isProcessing = true;
                $(this).prop('disabled', true).text('Wird hinzugefügt...');
                
                $.post(ajaxurl, {
                    action: 'wha_add_district_cc',
                    district: district,
                    email: email,
                    email_type: email_type,
                    _wpnonce: '<?php echo wp_create_nonce("wha_district_cc_nonce"); ?>'
                }, function(response) {
                    isProcessing = false;
                    $('#wha_add_btn').prop('disabled', false).text('Hinzufügen');
                    
                    if (response.success) {
                        showMessage('Bezirk erfolgreich hinzugefügt');
                        $('#wha_district_email').val('');
                        $('#wha_district_select').val('');
                        updateUI();
                    } else {
                        showMessage('Fehler beim Hinzufügen des Bezirks', 'error');
                    }
                }).fail(function() {
                    isProcessing = false;
                    $('#wha_add_btn').prop('disabled', false).text('Hinzufügen');
                    showMessage('Netzwerkfehler', 'error');
                });
            });

            // Initial Event Handler binden
            bindEventHandlers();

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

    if (!$district || !$email) {
        wp_send_json_error('Fehlende Daten');
    }

    $map = get_option("wha_district_email_map_{$email_type}", []);
    $map[$district] = $email;

    update_option("wha_district_email_map_{$email_type}", $map);

    wp_send_json_success();
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
        wp_send_json_success();
    }

    wp_send_json_error('Bezirk nicht gefunden');
});

/**
 * AJAX: Eintrag aktualisieren (editable)
 */
add_action('wp_ajax_wha_update_district_cc', function() {
    check_ajax_referer('wha_district_cc_nonce');

    $district = sanitize_text_field($_POST['district']);
    $email    = sanitize_email($_POST['email']);
    $email_type = sanitize_text_field($_POST['email_type'] ?? 'default_email');

    if (!$district || !$email) {
        wp_send_json_error('Fehlende Daten');
    }

    $map = get_option("wha_district_email_map_{$email_type}", []);
    $map[$district] = $email;

    update_option("wha_district_email_map_{$email_type}", $map);

    wp_send_json_success();
});

/**
 * AJAX: Daten abrufen
 */
add_action('wp_ajax_wha_get_district_cc', function() {
    check_ajax_referer('wha_district_cc_nonce');

    $email_type = sanitize_text_field($_POST['email_type'] ?? 'default_email');
    $map = get_option("wha_district_email_map_{$email_type}", []);

    wp_send_json_success($map);
});