<?php

// API-Routen registrieren
add_action('rest_api_init', function () {

    // GET – Alle Non-Setup-Daten (Dateien + Grund)
    register_rest_route('wc/v3', '/order-non-setup/(?P<order_id>\d+)', [
        'methods'             => 'GET',
        'callback'            => 'get_order_non_setup',
        'permission_callback' => function () {
            return current_user_can('manage_woocommerce');
        }
    ]);

    // POST – Info hinterlegen / aktualisieren
    register_rest_route('wc/v3', '/order-non-setup/(?P<order_id>\d+)/info', [
        'methods'             => 'POST',
        'callback'            => 'update_order_non_setup_info',
        'permission_callback' => function () {
            return current_user_can('manage_woocommerce');
        }
    ]);

    // DELETE – Info löschen
    register_rest_route('wc/v3', '/order-non-setup/(?P<order_id>\d+)/info', [
        'methods'             => 'DELETE',
        'callback'            => 'delete_order_non_setup_info',
        'permission_callback' => function () {
            return current_user_can('manage_woocommerce');
        }
    ]);

    // POST – Datei(en) hochladen
    register_rest_route('wc/v3', '/order-non-setup/(?P<order_id>\d+)/files', [
        'methods'             => 'POST',
        'callback'            => 'upload_order_non_setup_files',
        'permission_callback' => function () {
            return current_user_can('manage_woocommerce');
        }
    ]);

    // DELETE – Eine bestimmte Datei löschen
    register_rest_route('wc/v3', '/order-non-setup/(?P<order_id>\d+)/files', [
        'methods'             => 'DELETE',
        'callback'            => 'delete_order_non_setup_file',
        'permission_callback' => function () {
            return current_user_can('manage_woocommerce');
        }
    ]);
});

/**
 * GET /wc/v3/order-non-setup/{order_id}
 * Gibt Dateien und Info zurück.
 */
function get_order_non_setup(WP_REST_Request $request)
{
    $manager = new \Utils\OrderNonSetupManager((int) $request['order_id']);

    return rest_ensure_response($manager->getNonSetupData());
}

/**
 * POST /wc/v3/order-non-setup/{order_id}/info
 * Body: { "info": "Parkplatz blockiert" }
 */
function update_order_non_setup_info(WP_REST_Request $request)
{
    $params = $request->get_json_params();
    $info   = $params['info'] ?? '';

    $manager = new \Utils\OrderNonSetupManager((int) $request['order_id']);
    $manager->updateInfo($info);

    return rest_ensure_response([
        'message' => __('Info erfolgreich gespeichert.', WHA_TRANSLATION_KEY),
        'info'    => $info,
    ]);
}

/**
 * DELETE /wc/v3/order-non-setup/{order_id}/info
 * Löscht die gespeicherte Info.
 */
function delete_order_non_setup_info(WP_REST_Request $request)
{
    $manager = new \Utils\OrderNonSetupManager((int) $request['order_id']);
    $manager->deleteInfo();

    return rest_ensure_response(['message' => __('Info erfolgreich gelöscht.', WHA_TRANSLATION_KEY)]);
}

/**
 * POST /wc/v3/order-non-setup/{order_id}/files
 * Multipart-Upload: files[]
 */
function upload_order_non_setup_files(WP_REST_Request $request)
{
    if (empty($_FILES['files'])) {
        return new WP_Error('no_files', __('Keine Dateien hochgeladen.', WHA_TRANSLATION_KEY), ['status' => 400]);
    }

    $manager = new \Utils\OrderNonSetupManager((int) $request['order_id']);
    $result  = $manager->uploadFiles($_FILES['files']);

    if (is_wp_error($result)) {
        return new WP_Error($result->get_error_code(), $result->get_error_message(), ['status' => 400]);
    }

    return rest_ensure_response([
        'message'   => __('Datei(en) erfolgreich hochgeladen.', WHA_TRANSLATION_KEY),
        'file_urls' => $result,
    ]);
}

/**
 * DELETE /wc/v3/order-non-setup/{order_id}/files?file_url=https://...
 * Löscht eine bestimmte Datei anhand ihrer URL.
 */
function delete_order_non_setup_file(WP_REST_Request $request)
{
    $file_url = $request->get_param('file_url');

    $manager = new \Utils\OrderNonSetupManager((int) $request['order_id']);
    $result  = $manager->deleteFile($file_url);

    if (is_wp_error($result)) {
        return new WP_Error($result->get_error_code(), $result->get_error_message(), ['status' => $result->get_error_data('status') ?: 400]);
    }

    return rest_ensure_response(['message' => __('Datei erfolgreich gelöscht.', WHA_TRANSLATION_KEY)]);
}