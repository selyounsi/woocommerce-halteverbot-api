<?php

use Utils\PDF\Generator;
use Utils\PDF\Invoice\Positions;

/**
 * Set Positions into all Invoices
 */
Positions::setPositions();


add_filter('wpo_wcpdf_template_file', 'custom_wcpdf_template_path', 10, 3);
function custom_wcpdf_template_path($template_path, $template_type, $order) {
    // Überprüfe, ob es sich um eine Rechnung handelt
    if ($template_type === 'invoicX') {
        // Definiere den Pfad zu deiner benutzerdefinierten Template-Datei
        $custom_template_path = WHA_PLUGIN_PATH . '/data/order_docs/templates/woo-invoice.php';  
        if (file_exists($custom_template_path)) {
            return $custom_template_path;
        }
    }

    // Gib den Standardpfad zurück, falls keine benutzerdefinierte Datei gefunden wurde
    return $template_path;
}

add_filter('wpo_wcpdf_template_file', 'custom_invoice_template_based_on_meta', 10, 3);
function custom_invoice_template_based_on_meta($file_path, $document_type, $order) 
{
    if($order->get_id() === 6077) {    
        // Definiere den Pfad zu deiner benutzerdefinierten Template-Datei
        $custom_template_path = WHA_PLUGIN_PATH . '/data/order_docs/templates/wha-invoice.php';
        if (file_exists($custom_template_path)) {
            return $custom_template_path;
        }
    }

    // Gib den Standardpfad zurück, falls keine benutzerdefinierte Datei gefunden wurde
    return $file_path;
}