<?php

use Acowebs\WCPA\Config;
use Acowebs\WCPA\Form;
use Acowebs\WCPA\Product_Meta;

defined('ABSPATH') or die('No script kiddies please!');

/**
 * REGIST NEW API ROUTES
 */
add_action('rest_api_init', function () 
{
    /**
     * GET WPCA META
     */
    register_rest_route('wc/v3', '/wcpa/get_field/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'get_field',
        'permission_callback' => function () {
            return current_user_can('manage_woocommerce');
        }
    ));

    /**
     * GET WPCA META
     */
    register_rest_route('wc/v3', '/order/(?P<order_id>\d+)/wcpa/(?P<item_id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'get_order_items',
        'permission_callback' => function () {
            return current_user_can('manage_woocommerce');
        }
    ));

    /**
     * UPDATE WPCA META DATA
     */
    register_rest_route('wc/v3', '/order/(?P<order_id>\d+)/wcpa/(?P<item_id>\d+)', array(
        'methods' => 'POST',
        'callback' => 'save_order_items',
        'permission_callback' => function () {
            return current_user_can('manage_woocommerce');
        }
    ));

    /**
     * DELETE WPCA META DATA
     */
    register_rest_route('wc/v3', '/order/(?P<order_id>\d+)/wcpa/(?P<item_id>\d+)', array(
        'methods' => 'DELETE',
        'callback' => 'delete_order_items',
        'permission_callback' => function () {
            return current_user_can('manage_woocommerce');
        }
    ));

    /**
     * UPDATE META DATA
     */
    register_rest_route('wc/v3', '/order/(?P<order_id>\d+)/meta', array(
        'methods' => 'POST',
        'callback' => 'save_order_data',
        'permission_callback' => function () {
            return current_user_can('manage_woocommerce');
        }
    ));

    /**
     * FILTER ORDERS BY WPCA META POSTCODES
     */
    register_rest_route('wc/v3', '/orders-by-postcodes', array(
        'methods' => 'GET',
        'callback' => 'filter_orders_by_postcodes',
        'permission_callback' => function () {
            return current_user_can('manage_options'); // Berechtigungen anpassen
        },
        'args' => array(
            'postcodes' => array(
                'required' => true,
                'validate_callback' => function ($param, $request, $key) {
                    return is_array($param); // Sicherstellen, dass postcodes ein Array ist
                },
            ),
            'start_date' => array(
                'required' => false,
                'validate_callback' => function ($param, $request, $key) {
                    return strtotime($param) !== false; // Validierung für start_date
                },
            ),
            'end_date' => array(
                'required' => false,
                'validate_callback' => function ($param, $request, $key) {
                    return strtotime($param) !== false; // Validierung für end_date
                },
            ),
            'start_date_from' => array(
                'required' => false,
                'validate_callback' => function ($param, $request, $key) {
                    return strtotime($param) !== false; // Validierung für start_date_from
                },
            ),
            'start_date_to' => array(
                'required' => false,
                'validate_callback' => function ($param, $request, $key) {
                    return strtotime($param) !== false; // Validierung für start_date_to
                },
            ),
            'end_date_from' => array(
                'required' => false,
                'validate_callback' => function ($param, $request, $key) {
                    return strtotime($param) !== false; // Validierung für end_date_from
                },
            ),
            'end_date_to' => array(
                'required' => false,
                'validate_callback' => function ($param, $request, $key) {
                    return strtotime($param) !== false; // Validierung für end_date_to
                },
            ),
            'status' => array(
                'required' => false,
                'validate_callback' => function ($param, $request, $key) {
                    return is_array($param); // Sicherstellen, dass status ein Array ist
                },
            ),
        ),
    ));
});

/**
 * Retrieve metadata for a specific order item.
 *
 * @param WP_REST_Request $data The REST API request object containing parameters for the request.
 * @return WP_REST_Response The response object containing the metadata for the order item or an error message.
 */
function get_order_items($data)
{
    if (!isset($data['item_id'])) {
        return new WP_REST_Response(false, 400);
    }

    $item_id = $data['item_id'];
    $order = new Acowebs\WCPA\Order();
    $fields = $order->getOrderMeta($item_id);

    return new WP_REST_Response($fields, 200);
}

/**
 * Retrieve metadata for a specific order item.
 *
 * @param WP_REST_Request $data The REST API request object containing parameters for the request.
 * @return WP_REST_Response The response object containing the metadata for the order item or an error message.
 */
function get_field($data)
{
    if (!isset($data['id'])) {
        return new WP_REST_Response(false, 400);
    }
    $form_id = $data['id'];
    $form = new Form();
    $fields = $form->get_fields($form_id);

    return new WP_REST_Response($fields, 200);
}

/**
 * Save metadata for a specific order item.
 * @param WP_REST_Request $data The REST API request object containing parameters for the request.
 * @return WP_REST_Response The response object indicating the success or failure of the update operation.
 */
function save_order_items($data) 
{
    if (!isset($data['item_id'])) {
        return new WP_REST_Response(false, 400);
    }

    $item_id = $data['item_id'];
    $post_data = $data->get_params();

    $order = new Acowebs\WCPA\Order();
    $res = $order->saveOrderMeta($item_id, $post_data['fields']);

    $order = wc_get_order($data['order_id']);
    $order->calculate_totals();

    // Aktualisierung der `customer_note`, falls vorhanden
    if (isset($post_data["order"]["customer_note"])) {
        $customer_note = $post_data["order"]["customer_note"];
        $order->set_customer_note($customer_note);
    }

    // Sticker-Daten speichern, falls vorhanden
    if (isset($post_data["order"]["sticker_info"])) {
        $sticker_info = $post_data["order"]["sticker_info"];
        $order_id = $order->get_id();
        
        if (isset($sticker_info['continuous'])) {
            update_post_meta($order_id, '_sticker_continuous', sanitize_text_field($sticker_info['continuous']));
        }
        if (isset($sticker_info['period_type'])) {
            update_post_meta($order_id, '_sticker_period_type', sanitize_text_field($sticker_info['period_type']));
        }
        if (isset($sticker_info['week_day_start'])) {
            update_post_meta($order_id, '_sticker_week_day_start', sanitize_text_field($sticker_info['week_day_start']));
        }
        if (isset($sticker_info['week_day_end'])) {
            update_post_meta($order_id, '_sticker_week_day_end', sanitize_text_field($sticker_info['week_day_end']));
        }
    }

    $order->save();

    return new WP_REST_Response($res, 200);
}

/**
 * Save metadata for a specific order item.
 * @param WP_REST_Request $data The REST API request object containing parameters for the request.
 * @return WP_REST_Response The response object indicating the success or failure of the update operation.
 */
function save_order_data($data) 
{
    // JSON-Parameter abrufen
    $post_data = $data->get_json_params();

    // Validierung: Order ID vorhanden?
    if (!isset($data['order_id'])) {
        return new WP_REST_Response(['success' => false, 'message' => 'Order ID is required.'], 400);
    }

    // Validierung: Daten vorhanden?
    if (!isset($post_data["data"]) || !is_array($post_data["data"])) {
        return new WP_REST_Response(['success' => false, 'message' => 'Data is required and must be an array.'], 400);
    }

    $order_id = $data['order_id'];
    $order = wc_get_order($order_id);

    // Validierung: Bestellobjekt existiert?
    if (!$order) {
        return new WP_REST_Response(['success' => false, 'message' => 'Order not found.'], 404);
    }

    // Aktualisierung der `customer_note`, falls vorhanden
    if (isset($post_data["order"]["customer_note"])) {
        $customer_note = $post_data["order"]["customer_note"];
        $order->set_customer_note($customer_note);
        $order->save();
    }

    // Sticker-Daten speichern, falls vorhanden
    if (isset($post_data["order"]["sticker_info"])) {
        $sticker_info = $post_data["order"]["sticker_info"];
        
        if (isset($sticker_info['continuous'])) {
            update_post_meta($order_id, '_sticker_continuous', sanitize_text_field($sticker_info['continuous']));
        }
        if (isset($sticker_info['period_type'])) {
            update_post_meta($order_id, '_sticker_period_type', sanitize_text_field($sticker_info['period_type']));
        }
        if (isset($sticker_info['week_day_start'])) {
            update_post_meta($order_id, '_sticker_week_day_start', sanitize_text_field($sticker_info['week_day_start']));
        }
        if (isset($sticker_info['week_day_end'])) {
            update_post_meta($order_id, '_sticker_week_day_end', sanitize_text_field($sticker_info['week_day_end']));
        }
    }

    // Alle Line Items der Bestellung abrufen
    foreach ($order->get_items() as $item_id => $item) 
    {
        foreach ($post_data['data'] as $meta_data) 
        {
            if (!isset($meta_data['id']) || !isset($meta_data['key']) || !isset($meta_data['value'])) {
                continue; 
            }

            $meta_id = $meta_data['id'];
            $meta_key = $meta_data['key'];
            $meta_value = $meta_data['value'];

            $meta_found = false;

            // Zuerst nach ID suchen
            foreach ($item->get_meta_data() as $meta) {
                if ((int)$meta->id === (int)$meta_id) {
                    $meta_found = true;
                    // Meta-Wert aktualisieren
                    $item->update_meta_data($meta_key, $meta_value);
                    break;
                }
            }

            // Wenn die ID nicht gefunden wurde, nach Key suchen
            if (!$meta_found) {
                $meta_updated = false;

                // Prüfen, ob der Key bereits existiert
                foreach ($item->get_meta_data() as $meta) {
                    if ($meta->key === $meta_key) {
                        $meta_updated = true;
                        // Meta-Wert aktualisieren
                        $item->update_meta_data($meta_key, $meta_value);
                        break;
                    }
                }

                // Wenn der Key nicht existiert, neuen Meta-Eintrag erstellen
                if (!$meta_updated) {
                    $item->add_meta_data($meta_key, $meta_value);
                }
            }
        }

        // Änderungen für das aktuelle Item speichern
        $item->save();
    }

    // Order neu laden, um aktualisierte Daten zu erhalten
    $order = wc_get_order($order_id);
    
    // API-ready Order-Objekt vorbereiten (inklusive sticker_info)
    $order_data = $order->get_data();
    
    // Sticker-Daten zum Order-Objekt hinzufügen
    $continuous = get_post_meta($order_id, '_sticker_continuous', true);
    $period_type = get_post_meta($order_id, '_sticker_period_type', true);
    $week_day_start = get_post_meta($order_id, '_sticker_week_day_start', true);
    $week_day_end = get_post_meta($order_id, '_sticker_week_day_end', true);
    
    $order_data['sticker_info'] = [
        'continuous' => !empty($continuous) ? $continuous : 'no',
        'period_type' => !empty($period_type) ? $period_type : 'until',
        'week_day_start' => !empty($week_day_start) ? $week_day_start : 'Mo',
        'week_day_end' => !empty($week_day_end) ? $week_day_end : 'Fr'
    ];
    
    // Meta-Daten hinzufügen
    $order_data['meta_data'] = array();
    foreach ($order->get_meta_data() as $meta) {
        $order_data['meta_data'][] = [
            'id' => $meta->id,
            'key' => $meta->key,
            'value' => $meta->value
        ];
    }
    
    // Line Items mit Meta-Daten hinzufügen
    $order_data['line_items'] = array();
    foreach ($order->get_items() as $item_id => $item) {
        $item_data = $item->get_data();
        $item_data['meta_data'] = array();
        
        foreach ($item->get_meta_data() as $meta) {
            $item_data['meta_data'][] = [
                'id' => $meta->id,
                'key' => $meta->key,
                'value' => $meta->value
            ];
        }
        
        $order_data['line_items'][] = $item_data;
    }

    return new WP_REST_Response([
        'success' => true, 
        'message' => 'Meta data processed successfully.',
        'order' => $order_data
    ], 200);
}

/**
 * Delete a specific line item from an order.
 * 
 * @param WP_REST_Request $data The REST API request object containing parameters for the request.
 * @return WP_REST_Response The response object indicating the success or failure of the delete operation.
 */
function delete_order_items($data) 
{
    if (!isset($data['order_id'])) {
        return new WP_REST_Response(['success' => false, 'message' => 'Order ID is required.'], 400);
    }
    if (!isset($data['item_id'])) {
        return new WP_REST_Response(['success' => false, 'message' => 'Item ID is required.'], 400);
    }

    $order_id = (int) $data['order_id'];
    $item_id = (int) $data['item_id'];

    // Get the order object
    $order = wc_get_order($order_id);
    if (!$order) {
        return new WP_REST_Response(['success' => false, 'message' => 'Order not found.'], 404);
    }

    // Use the WPCAFields class
    $wpcaFields = new \Utils\WPCAFields($order);
    $response = $wpcaFields->deleteLineItem($item_id);

    return new WP_REST_Response($response, $response['success'] ? 200 : 400);
}

/**
 * Filter WooCommerce orders by postcodes, status, and optionally by a date range from custom fields.
 *
 * @param WP_REST_Request $request The REST API request object.
 * @return WP_REST_Response|WP_Error The filtered orders as a REST response or an error if the input is invalid.
 */
function filter_orders_by_postcodes($request) 
{
    // Get postcodes and date parameters from the request
    $postcodes = $request->get_param('postcodes');
    $start_date = $request->get_param('start_date'); // Range filter start
    $end_date = $request->get_param('end_date');     // Range filter end
    $start_date_from = $request->get_param('start_date_from'); // Start date range start
    $start_date_to = $request->get_param('start_date_to');     // Start date range end
    $end_date_from = $request->get_param('end_date_from');     // End date range start
    $end_date_to = $request->get_param('end_date_to');         // End date range end
    $status = $request->get_param('status');

    // If no postcodes are provided, return an empty response
    if (empty($postcodes)) {
        return rest_ensure_response([]);
    }

    // Ensure postcodes are in an array format
    if (!is_array($postcodes)) {
        return new WP_Error('invalid_postcodes', 'Postcodes should be an array', array('status' => 400));
    }

    // Prepare arguments for the order query
    $query_args = [
        'limit' => -1
    ];

    // Add status to the query if provided, otherwise fetch all statuses
    if ($status) {
        $query_args['status'] = $status;
    } else {
        $query_args['status'] = 'any'; // Fetch all statuses if none are provided
    }

    // Query WooCommerce orders
    $query = new WC_Order_Query($query_args);
    $orders = $query->get_orders();

    // Array to hold filtered orders
    $filtered_orders = array();

    // Loop through all retrieved orders
    foreach ($orders as $order) 
    {
        $count_wpca_fieldsets = 0;
        foreach ($order->get_items() as $item_id => $item) 
        {
            $sections = $item->get_meta('_WCPA_order_meta_data', true);
            foreach ($sections as $section) 
            {
                $count_wpca_fieldsets++;
            }
        }

        $WPCAFields = new \Utils\WPCAFields($order);
        $getFields = $WPCAFields->getMetaFields();

        foreach ($getFields as $field) {

            $postalcode = $field['Postleitzahl'] ?? null;

            // Prüfen, ob die Postleitzahl im Filter-Array enthalten ist
            if ($postalcode && in_array($postalcode, $postcodes)) {

                $item_start_date = $field['Startdatum'] ?? '';
                $item_end_date   = $field['Enddatum'] ?? '';

                $item_start_timestamp = strtotime($item_start_date);
                $item_end_timestamp   = strtotime($item_end_date);

                // Standardmäßig auf true setzen
                $date_range_condition = true;

                // Filter nach Startdatum / Enddatum (kompletter Range)
                if ($start_date && $end_date) {
                    if ($item_start_timestamp < strtotime($start_date) || $item_start_timestamp > strtotime($end_date)) {
                        $date_range_condition = false;
                    }
                }

                // Filter nach Startdatum von / bis
                if ($start_date_from && $item_start_timestamp < strtotime($start_date_from)) {
                    $date_range_condition = false;
                }
                if ($start_date_to && $item_start_timestamp > strtotime($start_date_to)) {
                    $date_range_condition = false;
                }

                // Filter nach Enddatum von / bis
                if ($end_date_from && $item_end_timestamp < strtotime($end_date_from)) {
                    $date_range_condition = false;
                }
                if ($end_date_to && $item_end_timestamp > strtotime($end_date_to)) {
                    $date_range_condition = false;
                }

                // Wenn alle Bedingungen passen, zum Ergebnis hinzufügen
                if ($date_range_condition) {
                    $filtered_orders[] = [
                        "id"         => $order->get_id(),
                        "status"     => $order->get_status(),
                        "startdate"  => $item_start_date,
                        "starttime"  => $field['Anfangszeit'] ?? '',
                        "enddate"    => $item_end_date,
                        "endtime"    => $field['Endzeit'] ?? '',
                        "days"       => $field['Anzahl der Tage'] ?? '',
                        "address"    => $field['Straße + Hausnummer'] ?? '',
                        "postalcode" => $postalcode,
                        "place"      => $field['Ort'] ?? '',
                        "addresses"  => count($getFields)
                    ];
                }
            }
        }
    }

    // Ensure an array is always returned
    if (empty($filtered_orders)) {
        return rest_ensure_response([]);
    }

    // Return the filtered orders as JSON
    return rest_ensure_response($filtered_orders);
}