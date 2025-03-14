<?php

use Utils\PDF\Generator;

add_action('rest_api_init', function () {
    register_rest_route(WHA_ROUTE_PATH, '/docs/offer/base24', [
        'methods'  => 'POST',
        'callback' => 'getOfferBase64',
        'permission_callback' => function () {
            return current_user_can('manage_woocommerce');
        }
    ]);
});

add_action('rest_api_init', function () {
    register_rest_route(WHA_ROUTE_PATH, '/docs/invoice/base24', [
        'methods'  => 'POST',
        'callback' => 'getInvoiceBase64',
        'permission_callback' => function () {
            return current_user_can('manage_woocommerce');
        }
    ]);
});

/**
 * GET OFFER BASE64
 *
 * @param WP_REST_Request $request
 * @return void
 */
function getOfferBase64(WP_REST_Request $request) 
{
    $data = $request->get_json_params();
    
    try {

        $doc = new Generator();
        $doc->generatePDF($data, WHA_PLUGIN_PATH . "/data/order_docs/templates/app/offer.php");
        $base64 = $doc->getBase64();
        $number = $doc->getMetaValue($data["meta_data"], 'document_number');

        if (!$base64) {
            throw new Exception("PDF konnte nicht generiert werden.");
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'base64' => $base64,
                'mime_type' => 'application/pdf',
                'file_name' => $number ? "{$number}-offer.pdf" : 'offer.pdf'
            ]
        ], 200);

    } catch (Exception $e) {
        error_log("Fehler in getInvoicePDF: " . $e->getMessage());

        return new WP_REST_Response([
            'success' => false,
            'message' => "Fehler: " . $e->getMessage()
        ], 500);
    }
}

/**
 * GET INVOICE BASE64
 *
 * @param WP_REST_Request $request
 * @return void
 */
function getInvoiceBase64(WP_REST_Request $request) 
{
    $data = $request->get_json_params();
    
    try {

        $doc = new Generator();
        $doc->generatePDF($data, WHA_PLUGIN_PATH . "/data/order_docs/templates/app/invoice.php");
        $base64 = $doc->getBase64();
        $number = $doc->getMetaValue($data["meta_data"], 'document_number');

        if (!$base64) {
            throw new Exception("PDF konnte nicht generiert werden.");
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'base64' => $base64,
                'mime_type' => 'application/pdf',
                'file_name' => $number ? "{$number}-invoice.pdf" : 'invoice.pdf'
            ]
        ], 200);

    } catch (Exception $e) {
        error_log("Fehler in getInvoicePDF: " . $e->getMessage());

        return new WP_REST_Response([
            'success' => false,
            'message' => "Fehler: " . $e->getMessage()
        ], 500);
    }
}