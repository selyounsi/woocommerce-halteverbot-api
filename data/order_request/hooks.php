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

    if($negativliste_file) {
        $installer_date = get_post_meta($object->get_id(), '_file_upload_negativliste_date', true);
        $installer_name = get_post_meta($object->get_id(), '_file_upload_negativliste_installer', true);
    } else {
        $installer_date = get_post_meta($object->get_id(), 'installer_date', true);
        $installer_name = get_post_meta($object->get_id(), 'installer_name', true);
    }

    $response->data['negativlist'] = [
        'url' => $negativliste_file ? esc_url($negativliste_file) : '',
        'date' => $installer_date ? $installer_date : '',
        'installer' => $installer_name ? $installer_name : ''
    ];

    return $response;
}