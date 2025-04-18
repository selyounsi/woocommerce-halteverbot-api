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


/**
 * Inserts invoice into the order object
 */
add_filter('woocommerce_rest_prepare_shop_order_object', 'add_invoice_to_order_response', 10, 3);
function add_invoice_to_order_response($response, $object, $request) 
{
    $invoice = new Generator($object);
    $invoice->generatePDF("invoice");
    
    if($object->get_meta('invoice_data')) {     
        $base64_pdf = $invoice->getBase64();
    } else  {
        $invoice_wc = wcpdf_get_document('invoice', $object, true);
        $base64_pdf = base64_encode($invoice_wc->get_pdf());
    }

    $response->data['invoice'] = [
        'base64' => $base64_pdf,
        'mime_type' => 'application/pdf',
        'file_name' => $invoice->getFileName()
    ];

    return $response;
}