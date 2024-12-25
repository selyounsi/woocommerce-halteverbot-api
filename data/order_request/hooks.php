<?php

/**
 * Add Negativliste to the API response
 */
add_filter('woocommerce_rest_prepare_shop_order_object', 'add_request_to_order_response', 10, 3);
function add_request_to_order_response($response, $object, $request) 
{
    $application_file = get_post_meta($object->get_id(), '_file_upload_application', true);
    $approval_file = get_post_meta($object->get_id(), '_file_upload_approval', true);
    $rejection_file = get_post_meta($object->get_id(), '_file_upload_rejection', true);

    $response->data['request'] = [
        'application' => $application_file ? esc_url($application_file) : '',
        'approval' => $approval_file ? esc_url($approval_file) : '',
        'rejection' => $rejection_file ? esc_url($rejection_file) : ''
    ];

    $negativliste_file = get_post_meta($object->get_id(), '_file_upload_negativliste', true);
    $negativliste_date = get_post_meta($object->get_id(), '_file_upload_negativliste_date', true);
    $negativliste_installer = get_post_meta($object->get_id(), '_file_upload_negativliste_installer', true);

    $response->data['negativlist'] = [
        'url' => $negativliste_file ? esc_url($negativliste_file) : '',
        'date' => $negativliste_date ? $negativliste_date : '',
        'installer' => $negativliste_installer ? $negativliste_installer : ''
    ];

    return $response;
}