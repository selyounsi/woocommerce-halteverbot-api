<?php

namespace Utils\PDF;

use Dompdf\Dompdf;
use Dompdf\Options;
use Utils\Order\OrderBuilder;
use \WPO\IPS\Documents\Invoice;

class Generator
{
    private Dompdf $pdf;
    private Invoice $wpo;
    public OrderBuilder $order;

    public $data;
    public $templates = [
        "offer"         => WHA_PLUGIN_PATH . "/data/order_docs/templates/wha-offer.php",
        "invoice"       => WHA_PLUGIN_PATH . "/data/order_docs/templates/wha-invoice.php",
        "negativlist"   => WHA_PLUGIN_PATH . "/data/order_docs/templates/wha-negativlist.php"
    ];

    public function __construct($data = [])
    {
        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $this->data = $data;
        $this->order = new OrderBuilder($data);
        $this->pdf = new Dompdf($options);
        $this->wpo = new Invoice();
    }

    public function generatePDF($template_name = ""): void
    {
        if (!$this->templates[$template_name]) {
            throw new \Exception("No template was found.");
        }

        $html = $this->buildInvoiceHtml($this->templates[$template_name]);
        $this->pdf->loadHtml($html);
        $this->pdf->setPaper('A4', 'portrait');
        $this->pdf->render();
    }

    private function buildInvoiceHtml($template_path): string
    {
        // Hier übergeben wir die Klasse an das Template
        $template = $this;
    
        // Ausgabe zwischenspeichern
        ob_start();
        include $template_path;
        return ob_get_clean();
    }

    public function getHeaderLogo($logo_id = null)
    {
        $logo_id = $logo_id ? $logo_id : $this->wpo->get_header_logo_id();
        $logo_path = get_attached_file($logo_id);

        $logo_mime = mime_content_type($logo_path); 
        $logo_data = base64_encode(file_get_contents($logo_path));
        return 'data:' . $logo_mime . ';base64,' . $logo_data;
    }

    public function getBlob(): string
    {
        return $this->pdf->output();
    }

    public function saveToFile(string $filePath): void
    {
        file_put_contents($filePath, $this->pdf->output());
    }

    public function getBase64(): string
    {
        return base64_encode($this->pdf->output());
    }
}
