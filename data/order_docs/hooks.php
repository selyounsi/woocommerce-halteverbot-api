<?php

use Utils\PDF\Generator;
use Utils\PDF\Invoice\Positions;

/**
 * Set Positions into all Invoices
 */
Positions::setPositions();


add_filter('wpo_wcpdf_template_file', 'custom_wcpdf_template_path', 10, 3);
function custom_wcpdf_template_path($template_path, $document_type, $order) 
{
    if($order->get_meta('invoice_data')) {     
        $custom_template_path = WHA_PLUGIN_PATH . '/data/order_docs/templates/wha-invoice.php';
    } else if ($document_type === 'invoice') {
        $custom_template_path = WHA_PLUGIN_PATH . '/data/order_docs/templates/woo-invoice.php';  
    }

    if (isset($custom_template_path) && file_exists($custom_template_path)) {
        return $custom_template_path;
    } else {
        return $template_path;
    }
}