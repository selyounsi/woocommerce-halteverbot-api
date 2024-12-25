<?php

/**
 * Inserts measures data into the order object
 */
add_filter('woocommerce_rest_prepare_shop_order_object', 'add_measures_to_order_response', 10, 3);
function add_measures_to_order_response($response, $object, $request) 
{
    $measures = get_post_meta($object->get_id(), '_traffic_measures', true);
    $files = get_post_meta($object->get_id(), '_traffic_measures_files', true);

    // Remove empty strings from files array
    $files = !empty($files) && is_array($files) ? array_filter($files, function ($file) {
        return $file !== '';
    }) : [];

    $response->data['traffic_measures'] = [
        'files' => $files,
        'measures' => empty($measures) ? [] : $measures
    ];

    return $response;
}