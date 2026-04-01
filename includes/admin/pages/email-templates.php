<?php
if (!is_admin()) return;

// Alle registrierten WooCommerce E-Mail Klassen laden
$mailer     = WC()->mailer();
$emails     = $mailer->get_emails();

// Nur bvos_custom_* E-Mails filtern
$custom_emails = array_filter($emails, function ($email) {
    return strpos($email->id, 'bvos_custom_') === 0;
});
?>

<div class="wrap woocommerce">
    <h1 class="wp-heading-inline">E-Mail Vorlagen</h1>
    <p class="description" style="margin-bottom: 20px;">Hier siehst du alle E-Mail Vorlagen der benutzerdefinierten Bestellstatus. Klicke auf "Verwalten", um eine Vorlage zu bearbeiten.</p>
    <hr class="wp-header-end">

    <?php if (empty($custom_emails)): ?>
        <div class="notice notice-warning"><p>Keine E-Mail Vorlagen für benutzerdefinierte Status gefunden.</p></div>
    <?php else: ?>

        <table class="wc_emails widefat" cellspacing="0">
            <thead>
                <tr>
                    <th class="wc-email-settings-table-name"><?php esc_html_e('Email', 'woocommerce'); ?></th>
                    <th class="wc-email-settings-table-description"><?php esc_html_e('Description', 'woocommerce'); ?></th>
                    <th class="wc-email-settings-table-recipient"><?php esc_html_e('Recipient(s)', 'woocommerce'); ?></th>
                    <th class="wc-email-settings-table-actions"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($custom_emails as $email): ?>
                    <?php
                    $manage_url = admin_url(
                        'admin.php?page=wc-settings&tab=email&section=' . $email->id
                    );
                    $is_enabled    = $email->is_enabled();
                    $recipient     = $email->get_recipient();
                    $email_type    = $email->get_email_type(); // html, plain, multipart
                    ?>
                    <tr>
                        <td class="wc-email-settings-table-name">
                            <?php if ($is_enabled): ?>
                                <span style="color:#46b450; margin-right: 6px;" title="Aktiv">●</span>
                            <?php else: ?>
                                <span style="color:#dc3232; margin-right: 6px;" title="Deaktiviert">●</span>
                            <?php endif; ?>
                            <strong><?php echo esc_html($email->get_title()); ?></strong>
                            <br>
                            <small style="color: #999;"><?php echo esc_html($email->id); ?></small>
                        </td>
                        <td class="wc-email-settings-table-description">
                            <?php echo wp_kses_post($email->get_description()); ?>
                            <br>
                            <small style="color: #999;">
                                Typ: <strong><?php echo esc_html($email_type); ?></strong>
                            </small>
                        </td>
                        <td class="wc-email-settings-table-recipient">
                            <?php
                            if ($email->is_customer_email()) {
                                echo '<span title="Kundenmail">👤 Kunde</span>';
                            } else {
                                echo esc_html($recipient ?: '—');
                            }
                            ?>
                        </td>
                        <td class="wc-email-settings-table-actions">
                            <a href="<?php echo esc_url($manage_url); ?>" class="button button-primary">
                                Verwalten
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <?php endif; ?>
</div>

<style>
    .wc_emails .wc-email-settings-table-name { width: 25%; }
    .wc_emails .wc-email-settings-table-description { width: 45%; }
    .wc_emails .wc-email-settings-table-recipient { width: 20%; }
    .wc_emails .wc-email-settings-table-actions { width: 10%; text-align: right; }
    .wc_emails tbody tr:hover td { background: #f9f9f9; }
    .wc_emails td, .wc_emails th { padding: 12px 10px; vertical-align: middle; }
</style>