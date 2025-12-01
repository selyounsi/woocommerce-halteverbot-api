<?php
defined('ABSPATH') or die('No script kiddies please!');

/**
 * REGIST NEW API ROUTES FOR STICKER DATA
 */
add_action('rest_api_init', function () {
    /**
     * UPDATE STICKER DATA BY ORDER ID
     */
    register_rest_route('wc/v3', '/order-sticker/(?P<order_id>\d+)', array(
        'methods' => 'POST',
        'callback' => 'save_sticker_data',
        'permission_callback' => function () {
            return current_user_can('manage_woocommerce');
        }
    ));
});

/**
 * SAVE STICKER DATA FOR AN ORDER
 * 
 * @param WP_REST_Request $request The REST API request containing the order ID and sticker data.
 * @return WP_REST_Response Success message or error if data is invalid.
 */
function save_sticker_data($request) 
{
    $order_id = (int) $request['order_id'];
    $sticker_data = $request->get_json_params();

    // Validate order exists
    $order = wc_get_order($order_id);
    if (!$order) {
        return new WP_Error('order_not_found', __('Bestellung nicht gefunden.', 'woocommerce'), array('status' => 404));
    }

    // Validate required fields exist
    $required_fields = ['continuous', 'period_type', 'week_day_start', 'week_day_end'];
    foreach ($required_fields as $field) {
        if (!isset($sticker_data[$field])) {
            return new WP_Error('missing_field', 
                sprintf(__('Fehlendes Feld: %s', 'woocommerce'), $field), 
                array('status' => 400)
            );
        }
    }

    // Sanitize and validate values
    $continuous = sanitize_text_field($sticker_data['continuous']);
    $period_type = sanitize_text_field($sticker_data['period_type']);
    $week_day_start = sanitize_text_field($sticker_data['week_day_start']);
    $week_day_end = sanitize_text_field($sticker_data['week_day_end']);

    // Validate allowed values
    $allowed_continuous = ['yes', 'no'];
    $allowed_period_type = ['until', 'and'];
    $allowed_week_days = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];

    if (!in_array($continuous, $allowed_continuous)) {
        return new WP_Error('invalid_value', 
            __('Ungültiger Wert für continuous. Erlaubt: yes, no', 'woocommerce'), 
            array('status' => 400)
        );
    }

    if (!in_array($period_type, $allowed_period_type)) {
        return new WP_Error('invalid_value', 
            __('Ungültiger Wert für period_type. Erlaubt: until, and', 'woocommerce'), 
            array('status' => 400)
        );
    }

    if (!in_array($week_day_start, $allowed_week_days)) {
        return new WP_Error('invalid_value', 
            sprintf(__('Ungültiger Wert für week_day_start. Erlaubt: %s', 'woocommerce'), implode(', ', $allowed_week_days)), 
            array('status' => 400)
        );
    }

    if (!in_array($week_day_end, $allowed_week_days)) {
        return new WP_Error('invalid_value', 
            sprintf(__('Ungültiger Wert für week_day_end. Erlaubt: %s', 'woocommerce'), implode(', ', $allowed_week_days)), 
            array('status' => 400)
        );
    }

    // Save to post meta
    update_post_meta($order_id, '_sticker_continuous', $continuous);
    update_post_meta($order_id, '_sticker_period_type', $period_type);
    update_post_meta($order_id, '_sticker_week_day_start', $week_day_start);
    update_post_meta($order_id, '_sticker_week_day_end', $week_day_end);

    // Return success response
    return rest_ensure_response([
        'success' => true,
        'message' => __('Sticker-Daten erfolgreich gespeichert.', 'woocommerce'),
        'data' => [
            'continuous' => $continuous,
            'period_type' => $period_type,
            'week_day_start' => $week_day_start,
            'week_day_end' => $week_day_end
        ]
    ]);
}
