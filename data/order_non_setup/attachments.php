<?php

use Utils\OrderNonSetupManager;

/**
 * Hängt Fotos an E-Mails für nicht aufgestellte Aufträge an.
 * Gilt für alle Status mit dem Prefix: bvos_custom_na_
 *
 * Status:
 *  - bvos_custom_na_work
 *  - bvos_custom_na_no_stop
 *  - bvos_custom_na_not_found
 *  - bvos_custom_na_taxi_bus
 */
add_filter('woocommerce_email_attachments', 'attach_non_setup_files_to_email', 10, 3);
function attach_non_setup_files_to_email($attachments, $email_id, $order)
{
    if (!str_contains($email_id, 'bvos_custom_na_') || !$order instanceof WC_Order) {
        return $attachments;
    }

    $manager = new OrderNonSetupManager($order->get_id());
    $data    = $manager->getNonSetupData();

    // Fotos anhängen
    if (!empty($data['files'])) {
        foreach ($data['files'] as $file_url) {
            if ($file_url && strpos($file_url, home_url()) === 0) {
                $file_path = ABSPATH . str_replace(home_url(), '', $file_url);

                if (file_exists($file_path)) {
                    $attachments[] = $file_path;
                } else {
                    error_log('[NonSetup] Foto nicht gefunden: ' . $file_path);
                }
            }
        }
    }

    return $attachments;
}

/**
 * -----------------------------------------------------------------------------
 * OPTIONAL: Grund der Nicht-Aufstellung direkt in den E-Mail-Body einfügen.
 * Einkommentieren wenn gewünscht.
 * -----------------------------------------------------------------------------
 *
 * add_filter('woocommerce_email_order_details', 'append_non_setup_info_to_email', 20, 4);
 * function append_non_setup_info_to_email($order, $sent_to_admin, $plain_text, $email)
 * {
 *     if (!str_contains($email->id, 'bvos_custom_na_') || !$order instanceof WC_Order) {
 *         return;
 *     }
 *
 *     $manager = new OrderNonSetupManager($order->get_id());
 *     $data    = $manager->getNonSetupData();
 *
 *     if (empty($data['info'])) {
 *         return;
 *     }
 *
 *     if ($plain_text) {
 *         echo "\n\nHinweis zur Nicht-Aufstellung:\n" . $data['info'] . "\n";
 *     } else {
 *         echo '<p><strong>Hinweis zur Nicht-Aufstellung:</strong><br>' . nl2br(esc_html($data['info'])) . '</p>';
 *     }
 * }
 */