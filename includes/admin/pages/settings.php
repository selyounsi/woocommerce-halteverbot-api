<?php

use Utils\Settings\HalteverbotSettings;

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
        'modify_checkout'         => isset($_POST['modify_checkout']) ? true : false,
        'checkout_notice_enabled' => isset($_POST['checkout_notice_enabled']) ? true : false,
        'checkout_notice_title'   => sanitize_text_field(wp_unslash($_POST['checkout_notice_title'] ?? '')),
        'checkout_notice_text'    => wp_kses_post(wp_unslash($_POST['checkout_notice_text'] ?? '')),
    ];

    // Speicherung über die Klasse (führt intern das Merging mit Defaults durch)
    HalteverbotSettings::updateSettings($input);

    // Redirect nach Speichern
    wp_redirect(add_query_arg('updated', '1', $_SERVER['REQUEST_URI']));
    exit;
}

/**
 * GET SETTINGS
 */
$settings = HalteverbotSettings::getSettings();
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Halteverbot Management Einstellungen</h1>

    <?php if (isset($_GET['updated'])): ?>
        <div class="notice notice-success is-dismissible"><p>Gespeichert.</p></div>
    <?php endif; ?>

    <form method="post" action="">

        <?php wp_nonce_field('halteverbot_reviews_settings'); ?>
        <input type="hidden" name="action" value="save_halteverbot_reviews_settings">

        <div class="postbox-container widefat">

            <!-- Card Rating -->
            <div class="postbox">
                <div class="inside">
                    <h2 class="hndle"><span>Checkout</span></h2>
                    <p>
                        <label><input type="checkbox" name="modify_checkout" <?php checked($settings['modify_checkout']); ?>> Kassensystem modifizieren, <a href="<?php echo esc_url( wc_get_checkout_url() . '?nowprocket=1&preview=1' ); ?>" target="_blank">hier</a> siehst du die Vorschau</label>
                    </p>
                </div>
            </div>

            <!-- Card Checkout-Hinweis -->
            <div class="postbox">
                <div class="inside">
                    <h2 class="hndle"><span>Checkout-Hinweis</span></h2>
                    <p>
                        <label>
                            <input type="checkbox" name="checkout_notice_enabled" <?php checked($settings['checkout_notice_enabled']); ?>>
                            Hinweistext im Checkout &amp; auf der Bestellbestätigung anzeigen
                        </label>
                    </p>
                    <p>
                        <label for="checkout_notice_title"><strong>Überschrift</strong> (optional)</label><br>
                        <input type="text" id="checkout_notice_title" name="checkout_notice_title" class="regular-text"
                               value="<?php echo esc_attr($settings['checkout_notice_title']); ?>"
                               placeholder="z. B. Wichtiger Hinweis zur Zahlung">
                    </p>
                    <p>
                        <label for="checkout_notice_text"><strong>Hinweistext</strong></label><br>
                        <textarea id="checkout_notice_text" name="checkout_notice_text" rows="5" class="large-text"
                                  placeholder="z. B. Aktuell kommt es bei Banküberweisungen zu Verzögerungen. Bitte senden Sie uns den Überweisungsbeleg per E-Mail an info@…, damit die Bearbeitung nicht verzögert wird."><?php echo esc_textarea($settings['checkout_notice_text']); ?></textarea>
                        <span class="description">Einfache Formatierung (Links, Fett etc.) ist erlaubt. Zeilenumbrüche werden übernommen.</span>
                    </p>
                </div>
            </div>

        </div>

        <p>
            <button type="submit" class="button button-primary">Speichern</button>
        </p>

    </form>
</div>