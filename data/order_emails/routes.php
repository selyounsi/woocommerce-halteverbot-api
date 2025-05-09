<?php

use Utils\PDF\Generator;

add_action('rest_api_init', function () {
    register_rest_route('wc/v3', '/email/offer', [
        'methods' => 'POST',
        'callback' => 'send_offer_email',
        'permission_callback' => function () {
            return current_user_can('manage_woocommerce');
        }
    ]);
    register_rest_route('wc/v3', '/email/invoice', [
        'methods' => 'POST',
        'callback' => 'send_invoice_email',
        'permission_callback' => function () {
            return current_user_can('manage_woocommerce');
        }
    ]);

    register_rest_route('wc/v3', '/notifications/(?P<type>offer|invoice)', [
        'methods' => 'POST',
        'callback' => 'send_document',
        'permission_callback' => function () {
            return current_user_can('manage_woocommerce');
        }
    ]);

    register_rest_route('wc/v3', '/orders/(?P<order_id>\d+)/email-notification', [
        'methods' => 'POST',
        'callback' => 'update_email_notification_status',
        'permission_callback' => function() {
            return current_user_can('manage_woocommerce');
        },
        'args' => [
            'status' => [
                'required' => true,
                'validate_callback' => function($param) {
                    return is_bool(filter_var($param, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE));
                },
            ],
        ],
    ]);
});

/**
 * SEND OFFER TO CLIENT
 *
 * @param WP_REST_Request $request
 * @return void
 */
function send_offer_email(WP_REST_Request $request) 
{
    $to = $request->get_param('to');
    $number = $request->get_param('number');

    // Überprüfen, ob die Parameter existieren und nicht leer sind
    if ( empty($to) ) {
        return new WP_Error('missing_to', 'The recipient email address is missing or empty.', ['status' => 400]);
    }

    if ( empty($number) ) {
        return new WP_Error('missing_number', 'The offer number is missing or empty.', ['status' => 400]);
    }


    // Hole die Dateiinformationen aus der Anfrage
    $attachment = $request->get_file_params();

    // Prüfe, ob die Datei vorhanden ist
    if ( ! empty($attachment['attachment']) ) {
        $attachment_tmp_path = $attachment['attachment']['tmp_name'];
        $attachment_name = $attachment['attachment']['name'];

        if ( ! file_exists( $attachment_tmp_path ) ) {
            return new WP_Error('file_not_found', 'Attachment file not found.', ['status' => 400]);
        }

        // Umbenennen der Datei (optional)
        $attachment_final_path = '/tmp/' . $attachment_name;
        if ( rename($attachment_tmp_path, $attachment_final_path) ) {
            $attachment_tmp_path = $attachment_final_path;
        } else {
            return new WP_Error('rename_failed', 'Failed to rename the attachment.', ['status' => 400]);
        }
    }

    // Neue Instanz der benutzerdefinierten E-Mail-Klasse
    $offer_email = new WC_Email_Offer();

    // Empfänger und Betreff werden hier gesetzt
    $offer_email->number = $number;
    $offer_email->recipient = $to;

    // Datei als Anhang hinzufügen, falls vorhanden
    $attachments = [];
    if ( ! empty( $attachment_tmp_path ) ) {
        $attachments[] = $attachment_tmp_path;
    }

    $send_offer = $offer_email->send_email($to, $attachments);

    // Überprüfen, ob die E-Mail erfolgreich gesendet wurde
    if ($send_offer) {
        // E-Mail erfolgreich gesendet
        return rest_ensure_response(['success' => true, 'message' => 'Email sent successfully!']);
    } else {
        // Fehler beim Senden der E-Mail
        return new WP_Error('send_failed', 'Failed to send email.', ['status' => 500]);
    }
}

/**
 * SEND OFFER TO CLIENT
 *
 * @param WP_REST_Request $request
 * @return void
 */
function send_invoice_email(WP_REST_Request $request) 
{
    $to = $request->get_param('to');
    $number = $request->get_param('number');

    // Überprüfen, ob die Parameter existieren und nicht leer sind
    if ( empty($to) ) {
        return new WP_Error('missing_to', 'The recipient email address is missing or empty.', ['status' => 400]);
    }

    if ( empty($number) ) {
        return new WP_Error('missing_number', 'The invoice number is missing or empty.', ['status' => 400]);
    }

    // Hole die Dateiinformationen aus der Anfrage
    $attachment = $request->get_file_params();

    // Prüfe, ob die Datei vorhanden ist
    if ( ! empty($attachment['attachment']) ) {
        $attachment_tmp_path = $attachment['attachment']['tmp_name'];
        $attachment_name = $attachment['attachment']['name'];

        if ( ! file_exists( $attachment_tmp_path ) ) {
            return new WP_Error('file_not_found', 'Attachment file not found.', ['status' => 400]);
        }

        // Umbenennen der Datei (optional)
        $attachment_final_path = '/tmp/' . $attachment_name;
        if ( rename($attachment_tmp_path, $attachment_final_path) ) {
            $attachment_tmp_path = $attachment_final_path;
        } else {
            return new WP_Error('rename_failed', 'Failed to rename the attachment.', ['status' => 400]);
        }
    }

    // Neue Instanz der benutzerdefinierten E-Mail-Klasse
    $invoice_email = new WC_Email_Invoice();

    // Empfänger und Betreff werden hier gesetzt
    $invoice_email->number = $number;
    $invoice_email->recipient = $to;

    // Datei als Anhang hinzufügen, falls vorhanden
    $attachments = [];
    if ( ! empty( $attachment_tmp_path ) ) {
        $attachments[] = $attachment_tmp_path;
    }

    $send_invoice = $invoice_email->send_email($to, $attachments);

    // Überprüfen, ob die E-Mail erfolgreich gesendet wurde
    if ($send_invoice) {
        // E-Mail erfolgreich gesendet
        return rest_ensure_response(['success' => true, 'message' => 'Email sent successfully!']);
    } else {
        // Fehler beim Senden der E-Mail
        return new WP_Error('send_failed', 'Failed to send email.', ['status' => 500]);
    }
}

/**
 * Update the email notification status and handle recipient email.
 */
function update_email_notification_status($data) 
{
    $order_id = $data['order_id'];
    $email_notification = filter_var($data['status'], FILTER_VALIDATE_BOOLEAN);

    $order = wc_get_order($order_id);
    if (!$order) {
        return new WP_REST_Response('Order not found', 404);
    }

    // Get the recipient email from _email_recipient if it exists
    $recipient_email = get_post_meta($order_id, '_email_recipient', true);

    if ($email_notification) {
        // If email notification is enabled, restore the recipient email to billing email
        if ($recipient_email) {
            // Restore the email from _email_recipient to billing email
            $order->set_billing_email($recipient_email);
            $order->save();  // Save the order with the restored email

            // Delete _email_recipient as it's no longer needed
            delete_post_meta($order_id, '_email_recipient');
        }
    } else {
        // If email notification is disabled, store the billing email in _email_recipient
        if ($order->get_billing_email()) {
            // Store the current billing email in _email_recipient before deleting it
            update_post_meta($order_id, '_email_recipient', $order->get_billing_email());

            // Clear the billing email to stop email notifications
            $order->set_billing_email('');  // Delete the billing email
            $order->save();  // Save the order with the cleared email
        }
    }

    // Update the email notification status in the order meta
    update_post_meta($order_id, '_email_notification', $email_notification ? 'true' : 'false');

    return new WP_REST_Response([
        'message' => 'Email notification ' . ($email_notification ? 'enabled' : 'disabled'),
        'email_notification' => $email_notification,
        'recipient_email' => $recipient_email,
    ], 200);
}

/**
 * Send document per E-mail
 *
 * @param WP_REST_Request $request
 * @return void
 */
function send_document(WP_REST_Request $request) 
{
    $data = $request->get_json_params();
    $type = $request->get_param('type');

    $doc = new Generator($data);

    if ($type === 'offer') {
        $wc_email = new WC_Email_Offer();
        $doc->generatePDF("offer");
    } elseif ($type === 'invoice') {

        $wc_email = new WC_Email_Invoice();
        $doc->generatePDF("invoice");
    }

    // Empfänger und Betreff für die E-Mail
    $recipient = $data["billing"]['email'];

    // Dateinamen erstellen (z.B. 'offer_001.pdf')
    $file_name = $doc->getFileName();
    // Temporäre Datei für das PDF erstellen
    $pdfFilePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $file_name;
    // PDF-Inhalt in die Datei schreiben
    file_put_contents($pdfFilePath, $doc->getBlob());

    // Setze die Nummer und den Empfänger
    $wc_email->number = $doc->order->getMetaValue('_wcpdf_invoice_number');
    $wc_email->recipient = $recipient;

    // Anhänge (PDF) zur E-Mail hinzufügen
    $attachments = [$pdfFilePath];

    // E-Mail senden
    $send_email = $wc_email->send_email($recipient, $attachments);

    // Überprüfen, ob die E-Mail erfolgreich gesendet wurde
    if ($send_email) {
        // E-Mail erfolgreich gesendet
        return rest_ensure_response(['success' => true, 'message' => $doc->templates]);
    } else {
        // Fehler beim Senden der E-Mail
        return new WP_Error('send_failed', 'Failed to send email.', ['status' => 500]);
    }
}
