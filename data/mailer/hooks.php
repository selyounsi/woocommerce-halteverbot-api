<?php
/**
 * Mailer-Modul: globale Hooks
 *   - Action-Scheduler-Callback für den Hintergrundversand
 *   - AJAX-Endpunkt für Testmail
 *   - Helper für die Ziel-Zusammenfassung
 */

if (!defined('ABSPATH')) {
    exit;
}

use Utils\Mailer\MailerService;

/**
 * Lädt Media-Uploader & WooCommerce-Kundensuche (Select2) auf der Mailer-Seite
 * zum korrekten Zeitpunkt, damit WooCommerce seine Such-Nonces mitliefert.
 */
add_action('admin_enqueue_scripts', 'wha_mailer_admin_assets');
function wha_mailer_admin_assets($hook)
{
    if (strpos((string) $hook, 'halteverbot-app-mailer') === false) {
        return;
    }

    wp_enqueue_media();
    wp_enqueue_script('wc-enhanced-select');
    wp_enqueue_style('select2');
    wp_enqueue_style('woocommerce_admin_styles');
}

/**
 * Wird vom Action Scheduler je Empfänger aufgerufen.
 */
add_action(MailerService::AS_HOOK, 'wha_mailer_process_action', 10, 1);
function wha_mailer_process_action($log_id)
{
    if (!class_exists('\Utils\Mailer\MailerService')) {
        return;
    }

    (new MailerService())->processRecipient((int) $log_id);
}

/**
 * AJAX: Testmail an eine Adresse senden (sofort, im Sandbox-Layout).
 */
add_action('wp_ajax_wha_mailer_test', 'wha_mailer_ajax_test');
function wha_mailer_ajax_test()
{
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error('Keine Berechtigung.', 403);
    }

    check_ajax_referer('wha_mailer_test', 'nonce');

    $to      = sanitize_email(wp_unslash($_POST['to'] ?? ''));
    $subject = sanitize_text_field(wp_unslash($_POST['subject'] ?? ''));
    $body    = wp_kses_post(wp_unslash($_POST['body'] ?? ''));

    $attachments = array_filter(array_map('intval', explode(',', (string) ($_POST['attachment_ids'] ?? ''))));

    if (!is_email($to)) {
        wp_send_json_error('Ungültige Test-Adresse.');
    }
    if ($subject === '' || trim(wp_strip_all_tags($body)) === '') {
        wp_send_json_error('Betreff und Inhalt dürfen nicht leer sein.');
    }

    $ok = (new MailerService())->sendTest($to, $subject, $body, $attachments);

    if ($ok) {
        wp_send_json_success(['message' => 'Testmail an ' . $to . ' gesendet.']);
    }

    wp_send_json_error('Testmail konnte nicht gesendet werden.');
}

/**
 * Erzeugt eine kompakte, lesbare Zusammenfassung der Zielauswahl
 * (wird in der Kampagne gespeichert und im Verlauf angezeigt).
 */
if (!function_exists('wha_mailer_targeting_summary')) {
    function wha_mailer_targeting_summary(array $targeting): array
    {
        $summary = [];

        if (!empty($targeting['statuses'])) {
            $all    = function_exists('wc_get_order_statuses') ? wc_get_order_statuses() : [];
            $labels = [];
            foreach ($targeting['statuses'] as $status) {
                $labels[] = $all[$status] ?? ($all['wc-' . $status] ?? $status);
            }
            $summary['statuses'] = $labels;
        }

        if (!empty($targeting['all_customers'])) {
            $summary['all_customers'] = true;
        }
        if (!empty($targeting['customers'])) {
            $summary['customers'] = count($targeting['customers']);
        }
        if (!empty($targeting['manual'])) {
            $summary['manual'] = count($targeting['manual']);
        }

        return $summary;
    }
}

/**
 * Rendert die gespeicherte Ziel-Zusammenfassung als Text.
 */
if (!function_exists('wha_mailer_format_targeting')) {
    function wha_mailer_format_targeting($targetingJson): string
    {
        $t = is_array($targetingJson) ? $targetingJson : json_decode((string) $targetingJson, true);
        if (!is_array($t) || empty($t)) {
            return '—';
        }

        $parts = [];
        if (!empty($t['statuses'])) {
            $parts[] = 'Status: ' . implode(', ', (array) $t['statuses']);
        }
        if (!empty($t['all_customers'])) {
            $parts[] = 'Alle Kunden';
        }
        if (!empty($t['customers'])) {
            $parts[] = $t['customers'] . ' ausgewählte Kunden';
        }
        if (!empty($t['manual'])) {
            $parts[] = $t['manual'] . ' manuelle Adressen';
        }

        return $parts ? implode(' · ', $parts) : '—';
    }
}
