<?php
// Verhindere direkten Zugriff
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Email_Review extends WC_Email 
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
        $this->id = 'review_email';
        $this->title = 'Bewertungs-E-Mail';
        $this->set_description_with_placeholders();

        // Holen der gespeicherten E-Mail-Einstellungen
        $review_email_settings = get_option( 'woocommerce_review_email_settings' );
        $review_email_settings = maybe_unserialize( $review_email_settings );
    
        // Absicherung mit Null-Coalescing Operator für jedes Feld
        $this->subject = (!empty($review_email_settings['subject'])) ? $review_email_settings['subject'] : 'Wie hat Ihnen unser Service gefallen?';
        $this->heading = (!empty($review_email_settings['heading'])) ? $review_email_settings['heading'] : 'Wir würden uns über Ihre Bewertung freuen!';
        $this->additional_content = $review_email_settings['additional_content'] ?? 
            'Vielen Dank, dass Sie unseren Service in Anspruch genommen haben! Ihre Meinung ist uns wichtig – bitte nehmen Sie sich einen Moment Zeit, um uns zu bewerten. 
            <br><br>
            {review_button}';

        $this->number = '0';
        $this->recipient = ''; // Hier könnte auch eine Option gesetzt werden, falls gewünscht
        $this->template_html = 'review-template-html.php';  
        $this->template_base = WHA_PLUGIN_PATH . '/data/order_emails/templates/';
        $this->bcc = $review_email_settings['bcc'] ?? '';
    
        parent::__construct();
    }

    public function output_review_email($valid_until = '') 
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
        $base_url = home_url('/reviews?nowprocket=1&order_id=' . $this->number);

        $placeholders = array(
            '{number}'       => $this->number,
            '{review_link}'  => $base_url,
            '{review_button}' => '<a href="' . esc_url($base_url) . '" target="_blank" style="color:#ffffff;background-color:#0071a1;padding:10px 20px;text-decoration:none;border-radius:4px;display: inline-block;">Jetzt bewerten</a>',
        );

        foreach ( $placeholders as $placeholder => $replacement ) {
            $content = str_replace( $placeholder, $replacement, $content );
        }

        return $content;
    }

    // E-Mail senden
    public function send_email($to, $attachments = []) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
    
        $subject = $this->replace_custom_placeholders($this->subject);
    
        // Lade die Nachricht aus dem HTML-Template
        ob_start(); 
        $this->output_review_email(true); 
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


    /**
     * Gibt alle verfügbaren Platzhalter zurück.
     *
     * @return array Schlüssel = Platzhalter, Wert = Beschreibung
     */
    public function get_placeholders() {
        return [
            '{number}'         => 'Bestellnummer',
            '{review_link}'    => 'Link zur Bewertungsseite',
            '{review_button}'  => 'Bewertungs-Link als Button',
            '{site_title}'     => 'Name der Webseite / Shop',
            '{site_url}'       => 'URL der Webseite',
            '{order_date}'     => 'Datum der Bestellung',
            '{order_number}'   => 'Bestellnummer (wie {number})',
            '{customer_name}'  => 'Name des Kunden',
            '{email_heading}'  => 'Überschrift der E-Mail',
            '{order_items}'    => 'Liste der bestellten Produkte',
            '{order_total}'    => 'Gesamtsumme der Bestellung',
            '{billing_address}'=> 'Rechnungsadresse',
            '{shipping_address}'=> 'Lieferadresse',
            '{payment_method}' => 'Zahlungsmethode',
        ];
    }

    /**
     * Gibt eine HTML-Liste aller Platzhalter mit Beschreibung aus.
     *
     * @return string HTML-String
     */
    public function get_placeholders_html() {
        $placeholders = $this->get_placeholders();

        $output = '<table style="width:100%; border-collapse: collapse;">';

        $cols = 3;
        $count = 0;
        foreach ($placeholders as $ph => $desc) {
            if ($count % $cols === 0) {
                $output .= '<tr>';
            }

            $output .= sprintf(
                '<td style="border:1px solid #ddd; padding:8px; vertical-align: top;">
                    <strong>%s</strong>: %s
                </td>', 
                esc_html($ph), 
                esc_html($desc)
            );

            $count++;
            if ($count % $cols === 0) {
                $output .= '</tr>';
            }
        }

        // Falls letzte Reihe nicht voll ist, schließe sie
        if ($count % $cols !== 0) {
            $output .= '</tr>';
        }

        $output .= '</table>';

        return $output;
    }

    // Beispiel wie du die HTML-Liste in der Beschreibung verwenden kannst
    public function set_description_with_placeholders() {
        $html = 'Ein benutzerdefiniertes E-Mail-Template für Bewertungen.<br><br>Verfügbare Platzhalter:';
        $html .= $this->get_placeholders_html();
        $this->description = $html;
    }
}
