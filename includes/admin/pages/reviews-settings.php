<?php

use Utils\ReviewsSettings;

if (!is_admin()) return;

/**
 * SAVE HANDLER
 */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['action'])
    && $_POST['action'] === 'save_halteverbot_reviews_settings'
) {
    if (!current_user_can('manage_options')) wp_die();
    check_admin_referer('halteverbot_reviews_settings');

    // Nur validierte Eingabewerte vorbereiten
    $input = [
        'enabled' => isset($_POST['enabled']) ? true : false,
        'status' => isset($_POST['status']) && is_array($_POST['status'])
            ? array_map('sanitize_text_field', $_POST['status'])
            : [],
        'card_rating' => [
            'headline' => sanitize_text_field($_POST['card_rating_headline'] ?? ''),
            'show_textarea' => isset($_POST['card_rating_show_textarea']),
            'textarea_placeholder' => sanitize_text_field($_POST['card_rating_textarea_placeholder'] ?? ''),
            'submit_button_text' => sanitize_text_field($_POST['card_rating_submit_button_text'] ?? ''),
            'referral_source' => isset($_POST['card_rating_referral_source']) && is_array($_POST['card_rating_referral_source'])
                ? array_map('sanitize_text_field', $_POST['card_rating_referral_source'])
                : [],
        ],
        'card_google' => [
            'headline' => sanitize_text_field($_POST['card_google_headline'] ?? ''),
            'text' => wp_kses_post($_POST['card_google_text'] ?? ''),
            'button_link' => esc_url_raw($_POST['card_google_button_link'] ?? ''),
            'button_text' => sanitize_text_field($_POST['card_google_button_text'] ?? ''),
        ],
        'card_end' => [
            'headline' => sanitize_text_field($_POST['card_end_headline'] ?? ''),
            'text' => sanitize_textarea_field($_POST['card_end_text'] ?? ''),
        ],
    ];

    // Speicherung über die Klasse (führt intern das Merging mit Defaults durch)
    ReviewsSettings::updateSettings($input);

    // Redirect nach Speichern
    wp_redirect(add_query_arg('updated', '1', $_SERVER['REQUEST_URI']));
    exit;
}

/**
 * GET SETTINGS
 */
$settings = ReviewsSettings::getSettings();

/**
 * GET ALL STATUSES
 */
$statuses = wc_get_order_statuses();


/**
 * TINYMCE Settings
 */
add_filter('tiny_mce_before_init', function ($init) {

    // Erste Toolbar-Reihe: wichtigste Formatierungen + Undo/Redo
    $init['toolbar1'] = 'formatselect,fontsizeselect,forecolor,backcolor,bold,italic,underline,removeformat,bullist,numlist,outdent,indent,alignleft,aligncenter,alignright,link,unlink,hr,charmap,undo,redo';

    // Schriftgrößen-Auswahl
    $init['fontsize_formats'] = '10px 12px 14px 16px 18px 24px 36px';

    // Schriftarten-Auswahl (optional)
    $init['font_formats'] = 'Arial=arial,helvetica,sans-serif;Times New Roman=times new roman,times;Roboto=roboto,sans-serif;';

    // Eigene Formatvorlagen (optional, z. B. für Info-Boxen oder Buttons)
    $init['style_formats'] = json_encode(array(
        array('title' => 'Info Box', 'block' => 'div', 'classes' => 'info-box'),
        array('title' => 'Hinweis', 'block' => 'div', 'classes' => 'hint-box'),
        array('title' => 'Call-to-Action Button', 'selector' => 'a', 'classes' => 'cta-button'),
    ));

    return $init;
});

?>
<div class="wrap">
    <h1 class="wp-heading-inline">Bewertungen</h1>

    <?php if (isset($_GET['updated'])): ?>
        <div class="notice notice-success is-dismissible"><p>Gespeichert.</p></div>
    <?php endif; ?>

    <form method="post" action="">

        <?php // var_dump(ReviewsSettings::isEnabled()); ?>
        <p>
            <label>
                <input type="checkbox" name="enabled" <?php checked($settings['enabled'], true); ?>>
                Bewertungsseite aktivieren
            </label>
        </p>

        <?php wp_nonce_field('halteverbot_reviews_settings'); ?>
        <input type="hidden" name="action" value="save_halteverbot_reviews_settings">

        <div class="postbox-container widefat">

            <!-- Card Rating -->
            <div class="postbox">
                <div class="inside">
                    <h2 class="hndle"><span>Card: Bewertung</span></h2>

                    <p>
                        <label>
                            <b>Überschrift</b>
                            <br><input type="text" name="card_rating_headline" value="<?php echo esc_attr($settings['card_rating']['headline']); ?>" class="regular-text">
                        </label>
                    </p>
                    <p><label><input type="checkbox" name="card_rating_show_textarea" <?php checked($settings['card_rating']['show_textarea']); ?>> Textarea anzeigen</label></p>
                    <p><label><b>Textarea Placeholder</b><br><input type="text" name="card_rating_textarea_placeholder" value="<?php echo esc_attr($settings['card_rating']['textarea_placeholder']); ?>" class="regular-text"></label></p>
                    <p><label><b>Absende-Button Text</b><br><input type="text" name="card_rating_submit_button_text" value="<?php echo esc_attr($settings['card_rating']['submit_button_text']); ?>" class="regular-text"></label></p>

                    <!-- Deine anderen Inputs ... -->

                    <hr style="margin: 20px 0;">

                    <label><b>Wie haben Sie uns gefunden?</b></label>
                    <div id="referral-source-list">
                        <?php foreach ($settings['card_rating']['referral_source'] as $index => $source): ?>
                            <div class="referral-source-item" style="margin-bottom: 5px;">
                                <input type="text" name="card_rating_referral_source[]" value="<?php echo esc_attr($source); ?>" class="regular-text" style="width: 80%;">
                                <button type="button" class="button referral-source-remove">Entfernen</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="button" id="referral-source-add">Weitere hinzufügen</button>

                    <hr style="margin: 20px 0;">

                    <label><b>Bestellstatus auswählen, bei denen eine Bewertungs-E-Mail gesendet werden soll:</b></label>
                    <div id="status-list">
                        <?php foreach ($settings['status'] as $selected_status): ?>
                            <div class="status-item" style="margin-bottom: 5px;">
                                <select name="status[]" class="regular-text">
                                    <?php foreach ($statuses as $status_key => $status_label): ?>
                                        <option value="<?php echo esc_attr($status_key); ?>" <?php selected($selected_status, $status_key); ?>>
                                            <?php echo esc_html($status_label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="button status-remove">Entfernen</button>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <button type="button" class="button" id="status-list-add">Status hinzufügen</button>

                    <!-- Rest deines Formulars -->
                </div>
            </div>

            <!-- Card Google -->
            <div class="postbox">
                <div class="inside">
                    <h2 class="hndle"><span>Card: Google</span></h2>
                    <p><label>Überschrift<br><input type="text" name="card_google_headline" value="<?php echo esc_attr($settings['card_google']['headline']); ?>" class="regular-text"></label></p>
                    <p>
                        <?php
                        $editor_id = 'card_google_text';
                        $content = $settings['card_google']['text'];
                        $editor_settings = array(
                            'textarea_name' => 'card_google_text',
                            'textarea_rows' => 5,
                            'media_buttons' => false,
                            'teeny' => false, // Wichtig: teeny muss false sein für erweiterte Formatierungsoptionen
                            'quicktags' => true,
                            'toolbar1' => 'formatselect,fontsizeselect,bold,italic,underline,bullist,numlist,alignleft,aligncenter,alignright,link,unlink,undo,redo',
                        );
                        wp_editor($content, $editor_id, $editor_settings);
                        ?>
                    </p>
                    <p><label>Button Link<br><input type="url" name="card_google_button_link" value="<?php echo esc_attr($settings['card_google']['button_link']); ?>" class="regular-text"></label></p>
                    <p class="description" style="font-size: 12px; color: #555;">
                        Um den Link zur Google-Bewertung zu erstellen, benötigen Sie Ihre <strong>Google Place ID</strong>.  
                        Diese finden Sie mit dem <a href="https://developers.google.com/maps/documentation/javascript/examples/places-placeid-finder" target="_blank" rel="noopener noreferrer">Place ID Finder Tool</a> von Google.  
                        Die vollständige URL zum Bewertungsformular lautet dann:  
                        <code>https://search.google.com/local/writereview?placeid=IHRE_PLACE_ID</code>  
                        Ersetzen Sie <code>IHRE_PLACE_ID</code> durch die ermittelte ID.
                    </p>
                    <p><label>Button Text<br><input type="text" name="card_google_button_text" value="<?php echo esc_attr($settings['card_google']['button_text']); ?>" class="regular-text"></label></p>
                </div>
            </div>

            <!-- Card End -->
            <div class="postbox">
                <div class="inside">
                    <h2 class="hndle"><span>Card: Ende</span></h2>
                    <p><label>Überschrift<br><input type="text" name="card_end_headline" value="<?php echo esc_attr($settings['card_end']['headline']); ?>" class="regular-text"></label></p>
                    <p><label>Text<br><textarea name="card_end_text" rows="3" class="large-text"><?php echo esc_textarea($settings['card_end']['text']); ?></textarea></label></p>
                </div>
            </div>

        </div>

        <p>
            <button type="submit" class="button button-primary">Speichern</button>
            <a href="<?php echo site_url('/reviews?nowprocket=1'); ?>" class="button" target="_blank">Vorschau</a>
        </p>

    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {


    /**
     * 
     */
    const list = document.getElementById('referral-source-list');
    const addBtn = document.getElementById('referral-source-add');

    addBtn.addEventListener('click', function() {
        const div = document.createElement('div');
        div.classList.add('referral-source-item');
        div.style.marginBottom = '5px';

        const input = document.createElement('input');
        input.type = 'text';
        input.name = 'card_rating_referral_source[]';
        input.className = 'regular-text';
        input.style.width = '80%';

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'button referral-source-remove';
        removeBtn.textContent = 'Entfernen';

        removeBtn.addEventListener('click', function() {
            div.remove();
        });

        div.appendChild(input);
        div.appendChild(removeBtn);
        list.appendChild(div);
    });

    document.querySelectorAll('.referral-source-remove').forEach(function(button) {
        button.addEventListener('click', function() {
            button.parentElement.remove();
        });
    });

    /**
     * ORDER STATUS
     */
    const statusList = document.getElementById('status-list');
    const addStatusBtn = document.getElementById('status-list-add');

    // Vorlage für ein neues Select
    const statusOptionsHtml = `<?php foreach ($statuses as $status_key => $status_label): ?>
        <option value="<?php echo esc_attr($status_key); ?>"><?php echo esc_html($status_label); ?></option>
    <?php endforeach; ?>`;

    addStatusBtn.addEventListener('click', function() {
        const div = document.createElement('div');
        div.classList.add('status-item');
        div.style.marginBottom = '5px';

        const select = document.createElement('select');
        select.name = 'status[]';
        select.className = 'regular-text';
        select.innerHTML = statusOptionsHtml;

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'button status-remove';
        removeBtn.textContent = 'Entfernen';
        removeBtn.addEventListener('click', function() {
            div.remove();
        });

        div.appendChild(select);
        div.appendChild(removeBtn);
        statusList.appendChild(div);
    });

    document.querySelectorAll('.status-remove').forEach(function(button) {
        button.addEventListener('click', function() {
            button.parentElement.remove();
        });
    });
});
</script>