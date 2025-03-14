<?php

use Utils\PDF\Invoice\Positions;

/**
 * Set Positions into all Invoices
 */
Positions::setPositions();


add_filter('wpo_wcpdf_template_file', 'custom_wcpdf_template_path', 10, 2);
function custom_wcpdf_template_path($template_path, $template_type) {
    // Überprüfe, ob es sich um eine Rechnung handelt
    if ($template_type === 'invoiceX') {
        // Definiere den Pfad zu deiner benutzerdefinierten Template-Datei
        $custom_template_path = WHA_PLUGIN_PATH . '/data/order_docs/templates/web/invoice.php';  
        if (file_exists($custom_template_path)) {
            return $custom_template_path;
        }
    }
    // Gib den Standardpfad zurück, falls keine benutzerdefinierte Datei gefunden wurde
    return $template_path;
}
