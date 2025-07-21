<?php

// REGIST NEW API ROUTES
add_action('rest_api_init', function () {
    // GET ALL LICENSES AND FILES
    register_rest_route('wc/v3', '/order-protocols/(?P<order_id>\d+)', [
        'methods' => 'GET',
        'callback' => 'get_order_protocols',
        'permission_callback' => function () {
            return current_user_can('manage_woocommerce');
        }
    ]);

    // UPDATE, ADD OR DELETE LICENSES
    register_rest_route('wc/v3', '/order-protocols/(?P<order_id>\d+)/licenses', [
        'methods' => 'POST',
        'callback' => 'update_order_licenses',
        'permission_callback' => function () {
            return current_user_can('manage_woocommerce');
        }
    ]);

    // DELETE A SPECIFIC LICENSE
    register_rest_route('wc/v3', '/order-protocols/(?P<order_id>\d+)/licenses/(?P<license_plate>[^/]+)', [
        'methods' => 'DELETE',
        'callback' => 'delete_order_license',
        'permission_callback' => function () {
            return current_user_can('manage_woocommerce');
        }
    ]);

    // UPLOAD OR UPDATE A FILE
    register_rest_route('wc/v3', '/order-protocols/(?P<order_id>\d+)/files', [
        'methods' => 'POST',
        'callback' => 'upload_or_update_order_file',
        'permission_callback' => function () {
            return current_user_can('manage_woocommerce');
        }
    ]);

    // DELETE A FILE
    register_rest_route('wc/v3', '/order-protocols/(?P<order_id>\d+)/files', [
        'methods' => 'DELETE',
        'callback' => 'delete_order_file',
        'permission_callback' => function () {
            return current_user_can('manage_woocommerce');
        }
    ]);
});

/**
 * get_order_protocols
 */
function get_order_protocols(WP_REST_Request $request)
{
    $order_id = $request['order_id'];
    $manager = new \Utils\OrderProtocolsManager($order_id);

    return rest_ensure_response($manager->getProtocols());
}

/**
 * update_order_licenses
 */
function update_order_licenses(WP_REST_Request $request)
{
    $order_id = $request['order_id'];
    $licenses = $request->get_json_params();
    $manager = new \Utils\OrderProtocolsManager($order_id);
    $result = $manager->updateLicenses($licenses);

    if (is_wp_error($result)) {
        return rest_ensure_response(['error' => $result->get_error_message()], $result->get_error_data('status') ?: 400);
    }

    return rest_ensure_response(['message' => __('Licenses updated successfully.', WHA_TRANSLATION_KEY)]);
}

/**
 * delete_order_license
 */
function delete_order_license(WP_REST_Request $request)
{
    $order_id = $request['order_id'];
    $license_plate = $request->get_param('license_plate');
    $manager = new \Utils\OrderProtocolsManager($order_id);
    $result = $manager->deleteLicense($license_plate);

    if (is_wp_error($result)) {
        return rest_ensure_response(['error' => $result->get_error_message()], $result->get_error_data('status') ?: 400);
    }

    return rest_ensure_response(['message' => __('License deleted successfully.', WHA_TRANSLATION_KEY)]);
}

/**
 * upload_or_update_order_file
 */
function upload_or_update_order_file(WP_REST_Request $request)
{
    $order_id = $request['order_id'];

    if (empty($_FILES['files'])) {
        return new WP_Error('no_files', __('No files uploaded.', WHA_TRANSLATION_KEY), ['status' => 400]);
    }

    $manager = new \Utils\OrderProtocolsManager($order_id);
    $result = $manager->uploadFiles($_FILES['files']);

    if (is_wp_error($result)) {
        return new WP_Error('no_files', $result->get_error_message(), ['status' => 400]);
    }

    return rest_ensure_response(['message' => __('File uploaded successfully.', WHA_TRANSLATION_KEY), 'file_urls' => $result]);
}

/**
 * delete_order_file
 */
function delete_order_file(WP_REST_Request $request)
{
    $order_id = $request['order_id'];
    $file_url = $request->get_param('file_url');
    $manager = new \Utils\OrderProtocolsManager($order_id);
    $result = $manager->deleteFile($file_url);

    if (is_wp_error($result)) {
        return rest_ensure_response(['error' => $result->get_error_message()], $result->get_error_data('status') ?: 400);
    }

    return rest_ensure_response(['message' => __('File deleted successfully.', WHA_TRANSLATION_KEY)]);
}