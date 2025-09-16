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
        'modify_checkout' => isset($_POST['modify_checkout']) ? true : false,
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

        </div>

        <p>
            <button type="submit" class="button button-primary">Speichern</button>
        </p>

    </form>
</div>