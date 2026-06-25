<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Email_Invoice_Reminder extends WC_Email
{
    public $id;
    public $title;
    public $description;
    public $heading;
    public $subject;
    public $recipient;
    public $bcc;
    public $additional_content;
    public $number;

    public $template_html;
    public $template_base;

    public function __construct()
    {
        $this->id = 'invoice_reminder_email';
        $this->title = 'Zahlungserinnerung [APP]';
        $this->description = 'E-Mail-Vorlage für die Zahlungserinnerung zu einer offenen Rechnung.';

        $invoice_reminder_email_settings = get_option( 'woocommerce_invoice_reminder_email_settings' );
        $invoice_reminder_email_settings = maybe_unserialize( $invoice_reminder_email_settings );

        $this->subject = $invoice_reminder_email_settings['subject'] ?? 'Zahlungserinnerung - RN{number}';
        $this->heading = $invoice_reminder_email_settings['heading'] ?? 'Zahlungserinnerung - RN{number}';
        $this->additional_content = $invoice_reminder_email_settings['additional_content'] ?? "Sehr geehrte Damen und Herren,\n\nleider konnten wir bei der oben genannten Rechnung noch keinen Zahlungseingang verzeichnen.\nSicher haben Sie es nur vergessen. Die Rechnung liegt diesem Schreiben in Kopie bei.\nBitte überweisen Sie den Betrag innerhalb von 10 Tagen und schicken uns einen Zahlungsnachweis zu.\n\nSollten Sie die Zahlung bereits veranlasst haben, betrachten Sie dieses Schreiben bitte als gegenstandslos.\n\nMit freundlichen Grüßen";

        $this->number = '0';
        $this->recipient = '';
        $this->template_html = 'invoice-reminder-template-html.php';
        $this->template_base = WHA_PLUGIN_PATH . '/data/order_emails/templates/';
        $this->bcc = $invoice_reminder_email_settings['bcc'] ?? '';

        parent::__construct();
    }

    public function output_invoice_reminder_email( $valid_until = '' )
    {
        wc_get_template(
            $this->template_html,
            array(
                'email_heading'      => $this->heading,
                'valid_until'        => $valid_until,
                'additional_content' => $this->additional_content,
            ),
            '',
            $this->template_base
        );
    }

    function replace_custom_placeholders( $content )
    {
        $placeholders = array(
            '{number}' => $this->number,
        );

        foreach ( $placeholders as $placeholder => $replacement ) {
            $content = str_replace( $placeholder, $replacement, $content );
        }

        return $content;
    }

    public function send_email( $to, $attachments = [] ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $subject = $this->replace_custom_placeholders( $this->subject );

        ob_start();
        $this->output_invoice_reminder_email( true );
        $message = ob_get_clean();
        $message = $this->replace_custom_placeholders( $message );

        $mailer = WC()->mailer();

        $headers = array( 'Content-Type: text/html; charset=UTF-8' );
        if ( ! empty( $this->bcc ) ) {
            $headers[] = 'BCC: ' . $this->bcc;
        }

        if ( ! empty( $attachments ) ) {
            return $mailer->send( $to, $subject, $message, $headers, $attachments );
        } else {
            return $mailer->send( $to, $subject, $message, $headers );
        }
    }
}
