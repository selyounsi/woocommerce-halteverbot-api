<?php

/**
 * Inserts protocol data into the order object using OrderProtocolsManager.
 */
add_filter('woocommerce_rest_prepare_shop_order_object', 'add_protocols_to_order_response', 10, 3);

function add_protocols_to_order_response($response, $object, $request) 
{
    // Instantiate the OrderProtocolsManager with the order ID
    $manager = new \Utils\OrderProtocolsManager($object->get_id());
    
    // Get protocols using the manager
    $protocols = $manager->getProtocols();

    // Add protocols to the response
    $response->data['protocols'] = $protocols;

    return $response;
}