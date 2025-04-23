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
    
    if($object->get_meta('invoice_data')) {  
        $invoice->generatePDF("invoice");   
        $base64_pdf = $invoice->getBase64();
    } else  {
        $invoice_wc = wcpdf_get_document('invoice', $object, true);
        $base64_pdf = base64_encode($invoice_wc->get_pdf());
    }

    $positions = $object->get_meta('wpo_wcpdf_invoice_positions', true) ?? [];
    $invoiceData = $object->get_meta('invoice_data', true) ?? [];
    
    $response->data['invoice'] = [
        "type" => "invoice",
        "status" => $object->get_status(),
        "customer_note" => $object->get_customer_note(),
        "document_note" => $object->get_meta('document_note', true),
        "created" => $object->get_meta('document_created', true),
        "pdf" => [
            'base64' => $base64_pdf,
            'mime_type' => 'application/pdf',
            'file_name' => $invoice->getFileName("invoice")
        ],
        "positions" => $positions,
        "customer" => [
            'company'    => $object->get_billing_company(),
            'first_name' => $object->get_billing_first_name(),
            'last_name'  => $object->get_billing_last_name(),
            'address_1'  => $object->get_billing_address_1(),
            'city'       => $object->get_billing_city(),
            'postcode'   => $object->get_billing_postcode(),
            'phone'      => $object->get_billing_phone(),
            'email'      => $object->get_billing_email(),
        ],
        "number" => $object->get_meta('_wcpdf_invoice_number'),
        "order_number" => $object->get_order_number(),
        "discountRate" => 0
    ];

    // Set Invoice Data
    if(is_array($invoiceData)) {
        foreach($invoiceData as $key => $value) 
        {
            $response->data['invoice']['price'][$key] = $value;

            if($key === "discount_percentage") {
                $response->data['invoice']['discountRate'] = $value;
            }
        }
    }

    // Set order details – nur erster Line Item
    $items = $object->get_items();
    $firstItem = reset($items); // holt den ersten Eintrag aus dem Array

    if ($firstItem) {

        $lineItemId = $firstItem->get_id();
        $response->data['invoice']["details"]['line_item_id'] = $lineItemId;

        foreach ($firstItem->get_meta_data() as $meta) {
            if (!empty($meta->value) && $meta->key !== WCPA_ORDER_META_KEY) {
                $response->data['invoice']["details"][$meta->key] = $meta->value;
            }
        }
    }

    $response->data['invoice']["details"]["time_duration"] = $object->get_meta('order_time_duration', true) ?? "0";
    $response->data['invoice']["details"]["selected_time_type"] = $object->get_meta('order_time_type', true) ?? "range";

    return $response;
}