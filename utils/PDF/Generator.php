<?php

namespace Utils\PDF;

use Dompdf\Dompdf;
use Dompdf\Options;
use \WPO\IPS\Documents\Invoice;

class Generator
{
    private Dompdf $pdf;
    private Invoice $wpo;

    public function __construct()
    {
        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $this->pdf = new Dompdf($options);
        $this->wpo = new Invoice();
    }

    public function generatePDF(array $data, $template_path = ""): void
    {
        $html = $this->buildInvoiceHtml($data, $template_path);
        $this->pdf->loadHtml($html);
        $this->pdf->setPaper('A4', 'portrait');
        $this->pdf->render();
    }

    private function buildInvoiceHtml(array $data, $template_path): string
    {
        // Hier übergeben wir die Klasse an das Template
        $template = $this;
    
        // Ausgabe zwischenspeichern
        ob_start();
        include $template_path;
        return ob_get_clean();
    }

    function getMetaValue(array $metaData, string $searchKey) 
    {
        foreach ($metaData as $meta) {
            if (isset($meta["key"]) && $meta["key"] === $searchKey) {
                return $meta["value"];
            }
        }
        return null;
    }

    public function getLineItemMeta(array $lineItems, string $metaKey): ?string
    {
        foreach ($lineItems as $item) {
            if (isset($item['meta_data'])) {
                foreach ($item['meta_data'] as $meta) {
                    if (isset($meta['key']) && $meta['key'] === $metaKey) {
                        return $meta['value'];
                    }
                }
            }
        }
        return null;
    }

    public function getHeaderLogo()
    {
        $logo_id = $this->wpo->get_header_logo_id();
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
