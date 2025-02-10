<?php
// Sicherstellen, dass das Skript nicht direkt aufgerufen wird
defined('ABSPATH') or die('No script kiddies please!');

/**
 * REGIST NEW API ROUTES
 */
add_action('rest_api_init', function () {
    /**
     * GET MEASURES BY ORDER ID
     */
    register_rest_route('wc/v3', '/order-traffic-measures/(?P<order_id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'get_traffic_measures',
        'permission_callback' => function () {
            return current_user_can('manage_woocommerce');
        }
    ));
 
    /**
     * UPDATE MEASURES BY ORDER ID
     */
    register_rest_route('wc/v3', '/order-traffic-measures/(?P<order_id>\d+)', array(
        'methods' => 'POST',
        'callback' => 'save_traffic_measures',
        'permission_callback' => function () {
            return current_user_can('manage_woocommerce');
        }
    ));

    // UPLOAD OR UPDATE A FILE
    register_rest_route('wc/v3', '/order-traffic-measures/(?P<order_id>\d+)/files', [
        'methods' => 'POST',
        'callback' => 'upload_or_update_order_measures_file',
        'permission_callback' => function () {
            return current_user_can('manage_woocommerce');
        }
    ]);

    // DELETE A FILE
    register_rest_route('wc/v3', '/order-traffic-measures/(?P<order_id>\d+)/files', [
        'methods' => 'DELETE',
        'callback' => 'delete_order_measures_file',
        'permission_callback' => function () {
            return current_user_can('manage_woocommerce');
        }
    ]);

});

/**
 * Retrieves traffic measures for an order.
 *
 * @param WP_REST_Request $request The REST API request containing the order ID.
 * @return WP_REST_Response The traffic measures or an empty array if none exist.
 */
function get_traffic_measures($request) 
{
    $order_id = (int) $request['order_id'];
    $measures = get_post_meta($order_id, '_traffic_measures', true);
    
    if (empty($measures)) {
        $measures = [];
    }

    return rest_ensure_response($measures);
}

/**
 * Saves traffic measures for an order.
 *
 * @param WP_REST_Request $request The REST API request containing the order ID and measures.
 * @return WP_REST_Response|WP_Error Success message or error if data is invalid.
 */
function save_traffic_measures($request) 
{
    $order_id = (int) $request['order_id'];
    $measures = $request->get_json_params();

    if (!isset($measures) || !is_array($measures)) {
        return new WP_Error('invalid_data', __('Ungültige Daten.', WHA_TRANSLATION_KEY), array('status' => 400));
    }

    $sanitized_measures = [];

    foreach ($measures as $measure) {
        $main = sanitize_text_field($measure['main']);
        $count = isset($measure['count']) ? intval($measure['count']) : 0; // Zähler für Hauptmaßnahme
        $sub_measures = isset($measure['sub_measures']) ? array_map(function($sub_measure) {
            return [
                'measure' => sanitize_text_field($sub_measure['measure']),
                'count' => isset($sub_measure['count']) ? intval($sub_measure['count']) : 0, // Zähler für Zusatzmaßnahme
            ];
        }, $measure['sub_measures']) : [];

        $sanitized_measures[] = [
            'main' => $main,
            'count' => $count,
            'sub_measures' => $sub_measures,
        ];
    }

    update_post_meta($order_id, '_traffic_measures', $sanitized_measures);

    return rest_ensure_response(__('Verkehrssicherungsmaßnahmen wurden erfolgreich gespeichert.', WHA_TRANSLATION_KEY));
}

// UPLOAD OR UPDATE A FILE
function upload_or_update_order_measures_file($request) 
{
    $order_id = $request['order_id'];

    if (empty($_FILES['file'])) {
        return new WP_Error('no_file', __('No file uploaded.', WHA_TRANSLATION_KEY), ['status' => 400]);
    }

    // Load the required file for wp_handle_upload function
    require_once ABSPATH . 'wp-admin/includes/file.php';

    $file = $_FILES['file'];
    $upload = wp_handle_upload($file, ['test_form' => false]);

    if (isset($upload['error'])) {
        return new WP_Error('upload_error', $upload['error'], ['status' => 500]);
    }

    $file_url = esc_url($upload['url']);

    // Retrieve files and ensure it is an array
    $files = get_post_meta($order_id, '_traffic_measures_files', true);
    if (!is_array($files)) {
        $files = []; // Initialize as an empty array if not already an array
    }

    // Add the new file URL
    $files[] = $file_url;

    // Update the post meta
    update_post_meta($order_id, '_traffic_measures_files', $files);

    return rest_ensure_response([
        'message' => __('File uploaded successfully.', WHA_TRANSLATION_KEY),
        'file_url' => $file_url
    ]);
} 

// DELETE A FILE
function delete_order_measures_file($request) {
    $order_id = $request['order_id'];
    $file_url_to_delete = $request->get_param('file_url');

    if (empty($file_url_to_delete)) {
        return new WP_Error('invalid_file', __('File URL is required.', WHA_TRANSLATION_KEY), ['status' => 400]);
    }

    $files = get_post_meta($order_id, '_traffic_measures_files', true);

    // Überprüfe, ob keine Dateien vorhanden sind
    if (empty($files)) {
        return new WP_Error('no_files', __('No files found for this order.', WHA_TRANSLATION_KEY), ['status' => 404]);
    }

    // Bereinige und extrahiere den Dateinamen aus der URL, die gelöscht werden soll
    $cleaned_file_url_to_delete = esc_url(trim($file_url_to_delete));
    $file_name_to_delete = basename(parse_url($cleaned_file_url_to_delete, PHP_URL_PATH));

    $found = false;

    // Durchlaufe die Liste der Dateien und entferne die Datei, wenn der Name übereinstimmt
    foreach ($files as $key => $file) {
        // Bereinige und extrahiere den Dateinamen aus der Datei im Array
        $cleaned_file = esc_url(trim($file));
        $file_name = basename(parse_url($cleaned_file, PHP_URL_PATH));

        // Vergleiche nur die Dateinamen
        if ($file_name_to_delete == $file_name) {
            unset($files[$key]); // Entferne das Element aus dem Array
            $found = true;
            break; // Beende die Schleife, sobald die Datei entfernt wurde
        }
    }

    // Falls die Datei nicht gefunden wurde, gib einen Fehler zurück
    if (!$found) {
        return new WP_Error('file_not_found', __('File not found.', WHA_TRANSLATION_KEY), ['status' => 404]);
    }

    // Update die Post-Metadaten, nachdem das Array aktualisiert wurde
    update_post_meta($order_id, '_traffic_measures_files', array_values($files));

    // Erfolgreiche Antwort zurückgeben
    return rest_ensure_response(__('File deleted successfully.', WHA_TRANSLATION_KEY));
}

