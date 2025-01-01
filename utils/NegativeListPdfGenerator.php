<?php

namespace Utils;

use DateTime;
use Dompdf\Dompdf;
use Utils\WPCAFields;

/**
 * Class NegativeListPdfGenerator
 *
 * Generates a negative list PDF for a WooCommerce order.
 *
 * @package Utils
 */
class NegativeListPdfGenerator 
{
    /**
     * WooCommerce Order object.
     *
     * @var \WC_Order
     */
    public $order;

    /**
     * Date of installation.
     *
     * @var string
     */
    public $date;

    /**
     * Installer name.
     *
     * @var string
     */
    public $installer;


    /**
     * Order status.
     *
     * @var string
     */
    public $status;


    /**
     * Constructor to initialize the generator with the order.
     *
     * @param \WC_Order $order The WooCommerce order object.
     * @param string $installer The name of the installer (optional).
     * @param string $date The date associated with the order (optional).
     */
    public function __construct($order, $installer = "", $date = "", $status = "")
    {
        $this->order        = $order;
        $this->date         = $date;
        $this->status       = $status;
        $this->installer    = $installer;
    }

    /**
     * Generate the negative list PDF and save it to the uploads directory.
     *
     * @param string $installer The name of the installer (optional).
     * @param string $date The installation date (optional).
     * @return array The PDF URL, date, and installer.
     */
    public function generatePdf() 
    {
        // Initialize Dompdf and generate the PDF
        $dompdf = new Dompdf(['enable_remote' => true]);
        $dompdf->loadHtml($this->getTemplate());
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Define paths
        $upload_dir = wp_upload_dir();
        $order_id = esc_html($this->order->get_id());
        $timestamp = time();
        $pdf_filename = "order_{$order_id}_{$timestamp}.pdf";
        $pdf_path = $upload_dir['basedir'] . '/negative_list/' . $pdf_filename;

        // Ensure directory exists
        if (!file_exists(dirname($pdf_path))) {
            mkdir(dirname($pdf_path), 0755, true);
        }

        // Delete the previous file if it exists
        $old_pdf_url = get_post_meta($this->order->get_id(), '_file_upload_negativliste', true);
        if ($old_pdf_url) {
            $old_pdf_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $old_pdf_url);
            if (file_exists($old_pdf_path)) {
                unlink($old_pdf_path); // Delete the old file
            }
        }

        // Save the new PDF to the file system
        file_put_contents($pdf_path, $dompdf->output());

        // Create the URL for the PDF
        $pdf_url = $upload_dir['baseurl'] . '/negative_list/' . $pdf_filename;

        // Update WooCommerce order meta
        update_post_meta($this->order->get_id(), '_file_upload_negativliste', $pdf_url);
        update_post_meta($this->order->get_id(), '_file_upload_negativliste_date', $this->date);
        update_post_meta($this->order->get_id(), '_file_upload_negativliste_installer', $this->installer);

        // Optional: Update the order status if a status is set
        if (!empty($this->status)) {
            $this->order->update_status($this->status, __('Negative list PDF generated and status updated.', 'text-domain'));
        }

        $new_status = $this->order->get_status();

        // Return the PDF URL as a response
        return [
            "url" => $pdf_url,
            "date" => $this->date,
            "installer" => $this->installer,
            "status" => $new_status
        ];
    }

    /**
     * Generate the HTML template for the negative list PDF.
     *
     * @return string The generated HTML string.
     */
    public function getTemplate()
    {
        // Fetch custom fields using WPCAFields
        $wpca = new WPCAFields($this->order);
        $wpcaFields = $wpca->getMetaFieldsets();

        // Prepare image in Base64
        $image_path = HALTEVERBOT_APP_API_PATH . '/data/assets/images/halteverbot_branding.png';
        if (file_exists($image_path)) {
            $image_data = file_get_contents($image_path);
            $base64_image = 'data:image/png;base64,' . base64_encode($image_data);
            $html = '<img src="' . $base64_image . '" style="max-width:100%; height:auto;">';
        } else {
            $html = '<p>Image not found!</p>';
        }

        // Build the HTML content
        $html .= '<h1>Negativliste für den Auftrag ' . esc_html($this->order->get_order_number()) . '</h1>';

        foreach($wpcaFields as $fields) 
        {
            $html .= '<table border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse: collapse; margin-bottom: 30px; border: none;">';
            $html .= '<tbody>';
            
            // Add fields to the table
            $html .= '<tr><td style="font-weight:bold; border: none;">' . esc_html__('Ort der Aufstellung') . ':</td>';
            $html .= '<td style="border: none;">' . esc_html($fields['address'] . ', ' . $fields['postalcode'] . ' ' . $fields['place']) . '</td></tr>';
            $html .= '<tr><td style="font-weight:bold; border: none;">' . esc_html__('Länge HVZ') . ':</td>';
            $html .= '<td style="border: none;">' . esc_html($fields['distance_unit'] ?? '') . '</td></tr>';
            $html .= '<tr><td style="font-weight:bold; border: none;">' . esc_html__('Kunde') . ':</td>';
            $html .= '<td style="border: none;">' . esc_html($fields['client_fname'] . ' ' . $fields['client_lname']) . '</td></tr>';
            $html .= '<tr><td style="font-weight:bold; border: none;">' . esc_html__('Ausstellungsgrund') . ':</td>';
            $html .= '<td style="border: none;">' . esc_html($fields['reason'] ?? '') . '</td></tr>';
            
            // Add date and time information
            $startDate = $fields['startdate'] ?? '';
            $startTime = $fields['starttime'] ?? '';
            $endDate = $fields['enddate'] ?? '';
            $endTime = $fields['endtime'] ?? '';
            $html .= '<tr><td style="font-weight:bold; border: none;">' . esc_html__('Aufstellungsdatum') . ':</td>';
            $html .= '<td style="border: none;">' . ($startDate === $endDate 
                ? esc_html($this->formatToGermanDate($startDate) . ' ' . $startTime . ' - ' . $endTime) 
                : esc_html($this->formatToGermanDate($startDate) . ' ' . $startTime . ' - ' . $this->formatToGermanDate($endDate) . ' ' . $endTime)) . '</td></tr>';
    
            if($this->installer) {
                $html .= '<tr><td style="font-weight:bold; border: none;">' . esc_html__('Aufsteller') . ':</td>';
                $html .= '<td style="border: none;">' . esc_html($this->installer ?? '') . '</td></tr>';
            }
    
            if($this->date) {
                $html .= '<tr><td style="font-weight:bold; border: none;">' . esc_html__('Aufgestellt am') . ':</td>';
                $html .= '<td style="border: none;">' . esc_html($this->formatToGermanDate($this->date) ?? '') . '</td></tr>';
            }
    
            $html .= '</tbody></table>';
        }

        // Fetch measures from order meta
        $measures = get_post_meta($this->order->get_id(), '_traffic_measures', true);

        $Z286 = "";
        $Z283 = "";
        $Z286_Z283 = "";
        $Z283_Z283 = "";

        foreach($measures as $measure) 
        {
            if($measure["main"] == "286-50" || $measure["main"] == "286") {
                $Z286 = "checked";
            }
            if($measure["main"] == "283-50" || $measure["main"] == "283") {
                $Z283 = "checked";
            }
            if($measure["main"] == "286-50-ggue-283-50" || $measure["main"] == "286-ggue-283") {
                $Z286_Z283 = "checked";
            }
            if($measure["main"] == "283-10-ggue-283-20" || $measure["main"] == "283-ggue-283") {
                $Z283_Z283 = "checked";
            }
        }

        $html .= '<table border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse: collapse; margin-bottom: 30px; border: none;">';
        $html .= '<tbody>';
        
        // Add fields to the table
        $html .= '<tr><td style="border: none;"><input type="checkbox" '.$Z286.' style="border: none; position: relative; top: 6px;"> Z 286 StVO</td>';
        $html .= '<td style="border: none;"><input type="checkbox" '.$Z283.' style="border: none; position: relative; top: 6px;"> Z 283 StVO</td></tr>';

        $html .= '<tr><td style="border: none;"><input type="checkbox" '.$Z286_Z283.' style="border: none; position: relative; top: 6px;"> Z 286 StVO und gegenüber Z 283 StVO</td>';
        $html .= '<td style="border: none;"><input type="checkbox" '.$Z283_Z283.' style="border: none; position: relative; top: 6px;"> Z 283 StVO und gegenüber Z 283 StVO</td></tr>';
        
        $html .= '</tbody></table>';

        // Fetch license protocols from order meta
        $license_protocols = get_post_meta($this->order->get_id(), '_order_license_protocols', true);

        // Ensure $license_protocols is an array before processing
        if (!is_array($license_protocols)) {
            $license_protocols = []; // Default to an empty array if it's not an array
        }

        // Add license protocol table
        $html .= '<table border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse: collapse;">';
        $html .= '<thead><tr><th>Kennzeichen</th><th>Fahrzeugtyp</th><th>Farbe</th></tr></thead><tbody>';
        $maxRows = 10;
        $protocolCount = count($license_protocols);
        for ($i = 0; $i < max($maxRows, $protocolCount); $i++) {
            $license_data = $license_protocols[$i] ?? [];
            $license_plate = esc_html($license_data['license_plate'] ?? '&nbsp;');
            $vehicle_type = esc_html($license_data['vehicle_type'] ?? '&nbsp;');
            $color = esc_html($license_data['color'] ?? '&nbsp;');
            $html .= "<tr><td>{$license_plate}</td><td>{$vehicle_type}</td><td>{$color}</td></tr>";
        }
        $html .= '</tbody></table>';

        return $html;
    }

    /**
     * Konvertiert ein Datum von 'Y-m-d', 'Y-m-d H:i' oder 'Y-m-d H:i:s' in 'd.m.Y' oder 'd.m.Y H:i'.
     *
     * @param string $date Das ursprüngliche Datum im Format 'Y-m-d', 'Y-m-d H:i' oder 'Y-m-d H:i:s'.
     * @return string|null Das formatierte Datum im deutschen Format oder null, wenn die Konvertierung fehlschlägt.
     */
    function formatToGermanDate(string $date): ?string
    {
        // Mögliche Formate für die Eingabe
        $formats = ['Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d'];

        foreach ($formats as $format) {
            $dateTime = DateTime::createFromFormat($format, $date);

            if ($dateTime) {
                // Format entsprechend der Eingabe ausgeben
                return $dateTime->format(str_contains($date, ':') ? 'd.m.Y H:i' : 'd.m.Y');
            }
        }

        return null;
    }
}
