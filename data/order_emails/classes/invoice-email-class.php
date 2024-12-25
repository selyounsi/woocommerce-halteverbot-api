<?php
// Verhindere direkten Zugriff
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Email_Invoice extends WC_Email 
{
    public $id;
    public $title;
    public $description;
    public $heading;
    public $subject;
    public $recipient;
    public $additional_content;
    public $number;

    public $template_html;
    public $template_base;

    // Constructor
    public function __construct() 
    {
        $this->id = 'invoice_email';
        $this->title = 'Rechnungs-E-Mail [APP]';
        $this->description = 'Ein benutzerdefiniertes E-Mail-Template für Sonderangebote.';
    
        // Holen der gespeicherten E-Mail-Einstellungen
        $invoice_email_settings = get_option( 'woocommerce_invoice_email_settings' );
        $invoice_email_settings = maybe_unserialize( $invoice_email_settings );
    
        // Absicherung mit Null-Coalescing Operator für jedes Feld
        $this->subject = $invoice_email_settings['subject'] ?? 'RN{number} - Ihre Rechnung für Baustellenabsicherung!';
        $this->heading = $invoice_email_settings['heading'] ?? 'RN{number} - Ihre Rechnung für Baustellenabsicherung!';
        $this->additional_content = $invoice_email_settings['additional_content'] ?? 'Sehr geehrte Damen und Herren, anbei erhalten Sie die Rechnung für die erbrachten Leistungen. Wir bitten um fristgerechte Begleichung und stehen Ihnen bei Rückfragen gerne zur Verfügung.';
    
        $this->number = '0';
        $this->recipient = ''; // Hier könnte auch eine Option gesetzt werden, falls gewünscht
        $this->template_html = 'invoice-template-html.php';  
        $this->template_base = HALTEVERBOT_APP_API_PATH . '/data/order_emails/templates/';
    
        parent::__construct();
    }


    public function output_invoice_email($valid_until = '') 
    {
        wc_get_template(
            $this->template_html, 
            array( 
                'email_heading'         => $this->heading, 
                'valid_until'           => $valid_until,
                'additional_content'    => $this->additional_content,  
            ),
            '',
            $this->template_base  
        );
    }


    function replace_custom_placeholders($content) 
    {
        $placeholders = array(
            '{number}' => $this->number,  
        );
    
        // Ersetze die Platzhalter im Inhalt
        foreach ( $placeholders as $placeholder => $replacement ) {
            $content = str_replace( $placeholder, $replacement, $content );
        }
    
        return $content;
    }

    // E-Mail senden
    public function send_invoice_email($to, $attachments = []) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $subject = $this->replace_custom_placeholders($this->subject);

        // Lade die Nachricht aus dem HTML-Template
        ob_start(); 
        $this->output_invoice_email(true); 
        $message = ob_get_clean();
        $message = $this->replace_custom_placeholders($message);

        // Erstelle die E-Mail über den WooCommerce-Mailer
        $mailer = WC()->mailer();

        // Anhänge hinzufügen, falls vorhanden
        if ( ! empty($attachments) ) {
            return $mailer->send($to, $subject, $message, '', $attachments);
        } else {
            return $mailer->send($to, $subject, $message);
        }
    }
}
