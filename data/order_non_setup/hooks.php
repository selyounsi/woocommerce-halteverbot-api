<?php

/**
 * Fügt Non-Setup-Daten (Fotos + Grund) in die WooCommerce REST API Bestellantwort ein.
 */
add_filter('woocommerce_rest_prepare_shop_order_object', 'add_non_setup_to_order_response', 10, 3);

function add_non_setup_to_order_response($response, $object, $request)
{
    $manager = new \Utils\OrderNonSetupManager($object->get_id());

    $response->data['non_setup'] = $manager->getNonSetupData();

    return $response;
}