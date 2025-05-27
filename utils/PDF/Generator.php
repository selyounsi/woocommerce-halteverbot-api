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
    public $templates;

    public function __construct($data = [])
    {
        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $this->data     = $data;
        $this->order    = new OrderBuilder($data);
        $this->pdf      = new Dompdf($options);

        $this->templates = [
            "offer" => [
                "template" => WHA_PLUGIN_PATH . "/data/order_docs/templates/wha-offer.php",
                "fileName" => __('Angebot_', WHA_TRANSLATION_KEY)
            ],
            "invoice" => [
                "template" => WHA_PLUGIN_PATH . "/data/order_docs/templates/wha-invoice.php",
                "fileName" => __('Rechnung_', WHA_TRANSLATION_KEY)
            ],
            "negativlist" => [
                "template" => WHA_PLUGIN_PATH . "/data/order_docs/templates/wha-negativlist.php",
                "fileName" => __('Negativliste_', WHA_TRANSLATION_KEY)
            ]
        ];
    }

    public function generatePDF($template_type = ""): void
    {
        $this->type = $template_type;

        if (!$this->templates[$this->type]["template"]) {
            throw new \Exception("No template was found.");
        }

        $html = $this->buildInvoiceHtml($this->templates[$this->type]["template"]);
        $this->pdf->loadHtml($html);
        $this->pdf->setPaper('A4', 'portrait');
        $this->pdf->render();
    }

    public function getFileName($template_type = "")
    {
        $type = $template_type ? $template_type : $this->type;

        if (!isset($this->templates[$type])) {
            return "document.pdf"; // fallback
        }

        $prefix = $this->templates[$type]['fileName'] ?? $type;

        if($this->order->getOrder()->get_order_number()) {
            return "{$prefix}{$this->order->getOrder()->get_order_number()}.pdf";
        } else if($this->order->getMetaValue("document_number")) {
            return "{$prefix}{$this->order->getMetaValue("document_number")}.pdf";
        } else if($this->order->getMetaValue("_wcpdf_invoice_number")) {
            return "{$prefix}{$this->order->getMetaValue("_wcpdf_invoice_number")}.pdf";
        } else {
            return "{$prefix}.pdf";
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
