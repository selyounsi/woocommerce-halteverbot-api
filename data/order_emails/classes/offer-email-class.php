<?php
// Verhindere direkten Zugriff
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Email_Offer extends WC_Email 
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

    // Constructor
    public function __construct() 
    {
        $this->id = 'offer_email';
        $this->title = 'Angebots-E-Mail [APP]';
        $this->description = 'Ein benutzerdefiniertes E-Mail-Template für Sonderangebote.';
    
        // Holen der gespeicherten E-Mail-Einstellungen
        $offer_email_settings = get_option( 'woocommerce_offer_email_settings' );
        $offer_email_settings = maybe_unserialize( $offer_email_settings );
    
        // Absicherung mit Null-Coalescing Operator für jedes Feld
        $this->subject = $offer_email_settings['subject'] ?? 'AN{number} - Unser Angebot für Sie!';
        $this->heading = $offer_email_settings['heading'] ?? 'AN{number} - Unser Angebot für Sie!';
        $this->additional_content = $offer_email_settings['additional_content'] ?? 'Sehr geehrte Damen und Herren, im Anhang finden Sie unser Angebot. Wir freuen uns auf Ihre Rückmeldung und stehen Ihnen für Rückfragen jederzeit zur Verfügung.';
    
        $this->number = '0';
        $this->recipient = ''; // Hier könnte auch eine Option gesetzt werden, falls gewünscht
        $this->template_html = 'offer-template-html.php';  
        $this->template_base = HALTEVERBOT_APP_API_PATH . '/data/order_emails/templates/';
        $this->bcc = $offer_email_settings['bcc'] ?? '';
    
        parent::__construct();
    }

    public function output_offer_email($valid_until = '') 
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
    public function send_offer_email($to, $attachments = []) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
    
        $subject = $this->replace_custom_placeholders($this->subject);
    
        // Lade die Nachricht aus dem HTML-Template
        ob_start(); 
        $this->output_offer_email(true); 
        $message = ob_get_clean();
        $message = $this->replace_custom_placeholders($message);
    
        // Erstelle die E-Mail über den WooCommerce-Mailer
        $mailer = WC()->mailer();
    
        // BCC hinzufügen, wenn vorhanden
        $headers = array('Content-Type: text/html; charset=UTF-8');
        if (!empty($this->bcc)) {
            $headers[] = 'BCC: ' . $this->bcc;
        }
    
        // Anhänge hinzufügen, falls vorhanden
        if ( ! empty($attachments) ) {
            return $mailer->send($to, $subject, $message, $headers, $attachments);
        } else {
            return $mailer->send($to, $subject, $message, $headers);
        }
    }
}
