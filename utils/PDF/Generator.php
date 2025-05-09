<?php

namespace Utils\PDF;

use Dompdf\Dompdf;
use Dompdf\Options;
use Utils\Order\OrderBuilder;

class Generator
{
    private Dompdf $pdf;

    public $type;
    public $data;
    public $order;
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

        $this->data     = $data;
        $this->order    = new OrderBuilder($data);
        $this->pdf      = new Dompdf($options);
    }

    public function generatePDF($template_type = ""): void
    {
        $this->type = $template_type;

        if (!$this->templates[$this->type]) {
            throw new \Exception("No template was found.");
        }

        $html = $this->buildInvoiceHtml($this->templates[$this->type]);
        $this->pdf->loadHtml($html);
        $this->pdf->setPaper('A4', 'portrait');
        $this->pdf->render();
    }

    public function getFileName($template_type = "")
    {
        $fileType = $template_type ? $template_type : $this->type;

        if($this->order->getOrder()->get_order_number()) {
            return "{$this->order->getOrder()->get_order_number()}-{$fileType}.pdf";
        } else if($this->order->getMetaValue("document_number")) {
            return "{$this->order->getMetaValue("document_number")}-{$fileType}.pdf";
        } else if($this->order->getMetaValue("_wcpdf_invoice_number")) {
            return "{$this->order->getMetaValue("_wcpdf_invoice_number")}-{$fileType}.pdf";
        } else {
            return "{$fileType}.pdf";
        }
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
