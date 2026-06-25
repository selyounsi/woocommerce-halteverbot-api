<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Email_Offer_Reminder extends WC_Email
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
        $this->id = 'offer_reminder_email';
        $this->title = 'Angebots-Erinnerung [APP]';
        $this->description = 'E-Mail-Vorlage für die Erinnerung an ein offenes Angebot.';

        $offer_reminder_email_settings = get_option( 'woocommerce_offer_reminder_email_settings' );
        $offer_reminder_email_settings = maybe_unserialize( $offer_reminder_email_settings );

        $this->subject = $offer_reminder_email_settings['subject'] ?? 'Erinnerung Angebot - AN{number}';
        $this->heading = $offer_reminder_email_settings['heading'] ?? 'Erinnerung Angebot - AN{number}';
        $this->additional_content = $offer_reminder_email_settings['additional_content'] ?? "Sehr geehrte Damen und Herren,\n\nvor einiger Zeit haben wir Ihnen unser Angebot zugesendet und möchten uns erkundigen, ob Sie bereits Gelegenheit hatten, dieses zu prüfen.\n\nWir sind überzeugt, Ihnen nicht nur eine passende Lösung, sondern auch einen zuverlässigen Partner für eine langfristige Zusammenarbeit bieten zu können. Dabei legen wir großen Wert auf Qualität, persönliche Betreuung und eine partnerschaftliche Zusammenarbeit, die sich nachhaltig für Sie auszahlt.\n\nGerne stehen wir Ihnen für Fragen oder eine individuelle Abstimmung zur Verfügung. Wir würden uns freuen, Sie bald zu unserem Kundenkreis zählen zu dürfen.\n\nÜber eine kurze Rückmeldung freuen wir uns sehr.";

        $this->number = '0';
        $this->recipient = '';
        $this->template_html = 'offer-reminder-template-html.php';
        $this->template_base = WHA_PLUGIN_PATH . '/data/order_emails/templates/';
        $this->bcc = $offer_reminder_email_settings['bcc'] ?? '';

        parent::__construct();
    }

    public function output_offer_reminder_email( $valid_until = '' )
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
        $this->output_offer_reminder_email( true );
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
