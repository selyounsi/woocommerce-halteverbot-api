<?php

use Utils\FileHanlder;
use Utils\PDF\PDFHelper; 

// Register the custom REST API endpoint for reading and updating file uploads
add_action('rest_api_init', function () 
{
    register_rest_route('wc/v3', '/order-files/(?P<order_id>\d+)', [
        'methods' => 'GET',
        'callback' => 'get_order_files',
        'permission_callback' => function () {
            return current_user_can('manage_woocommerce');
        }
    ]);

    register_rest_route('wc/v3', '/order-files/(?P<order_id>\d+)', [
        'methods' => 'POST',
        'callback' => 'update_order_files',
        'permission_callback' => function () {
            return current_user_can('manage_woocommerce');
        }
    ]);  
});

/**
 * Get file URLs for an order
 */
function get_order_files($request) 
{
    $order_id = $request->get_param('order_id');

    // Retrieve file URLs from the order meta
    $application_file = get_post_meta($order_id, '_file_upload_application', true);
    $approval_file = get_post_meta($order_id, '_file_upload_approval', true);
    $rejection_file = get_post_meta($order_id, '_file_upload_rejection', true);
    $negativliste_file = get_post_meta($order_id, '_file_upload_negativliste', true);

    return [
        'application' => $application_file ? esc_url($application_file) : '',
        'approval' => $approval_file ? esc_url($approval_file) : '',
        'rejection' => $rejection_file ? esc_url($rejection_file) : '',
        'negativliste' => $negativliste_file ? esc_url($negativliste_file) : '',
    ];
}

/**
 * Update file URLs for an order, either all or some of them
 */
function update_order_files($request) 
{
    $order_id = $request->get_param('order_id'); 

    // Check if the order exists
    $order = wc_get_order($order_id);
    if (!$order) {
        return new WP_Error('invalid_order', 'Invalid order ID', ['status' => 404]);
    }

    // Load the required file for wp_handle_upload function
    require_once ABSPATH . 'wp-admin/includes/file.php';

    // Supported file keys for upload
    $supported_files = ['application', 'approval', 'rejection', 'negativliste'];
    $file_urls = [];

    // Iterate through the supported file keys
    foreach ($supported_files as $file_key) {
        // Check if the file is being uploaded
        if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
            // Get the file path
            $file_path = $_FILES[$file_key]['tmp_name'];

            // Check if the PDF is encrypted
            // if (PDFHelper::isPdfEncrypted($file_path)) {
            //     return new WP_Error('encrypted_pdf', __('The uploaded PDF is encrypted and cannot be processed.', WHA_TRANSLATION_KEY), ['status' => 400]);
            // }

            $result = FileHanlder::upload(
                $_FILES[ $file_key ],
                WHA_UPLOAD_PATH . "{$order_id}"
            );

            if ( is_wp_error( $result ) ) {
                return new WP_Error('upload_error', $result, ['status' => 500]);
            }

            $file_urls[ $file_key ] = $result['url'];

            // Meta speichern
            update_post_meta(
                $order_id,
                '_file_upload_' . $file_key,
                $result['url']
            );
        }
    }

    // Check if any files were successfully uploaded
    if (empty($file_urls)) {
        return new WP_Error('no_file', 'No files uploaded or file upload error occurred', ['status' => 400]);
    }

    return [
        'message' => 'Order files updated successfully',
        'order_id' => $order_id,
        'order_number' => $order->get_order_number(),
        'files' => $file_urls
    ];
}