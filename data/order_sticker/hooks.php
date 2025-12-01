<?php

/**
 * Add Sticker information to the API response
 */
add_filter('woocommerce_rest_prepare_shop_order_object', 'add_sticker_info_to_order_response', 10, 3);
function add_sticker_info_to_order_response($response, $object, $request) 
{
    $order_id = $object->get_id();
    
    // Get values from post meta or use defaults
    $continuous = get_post_meta($order_id, '_sticker_continuous', true);
    $period_type = get_post_meta($order_id, '_sticker_period_type', true);
    $week_day_start = get_post_meta($order_id, '_sticker_week_day_start', true);
    $week_day_end = get_post_meta($order_id, '_sticker_week_day_end', true);
    
    // Add sticker fields to meta_data array
    // Check if meta_data exists and convert WC_Meta_Data objects to arrays
    $meta_data_array = [];
    
    if (isset($response->data['meta_data']) && is_array($response->data['meta_data'])) {
        foreach ($response->data['meta_data'] as $meta) {
            // Handle both WC_Meta_Data objects and arrays
            if (is_object($meta) && method_exists($meta, 'get_data')) {
                $meta_data = $meta->get_data();
                $meta_data_array[] = [
                    'key' => $meta_data['key'] ?? '',
                    'value' => $meta_data['value'] ?? ''
                ];
            } elseif (is_array($meta)) {
                $meta_data_array[] = $meta;
            }
        }
    }
    
    // Check if these meta keys already exist
    $existing_keys = [];
    foreach ($meta_data_array as $meta) {
        if (isset($meta['key'])) {
            $existing_keys[$meta['key']] = true;
        }
    }
    
    // Add only if not already present
    if (!isset($existing_keys['_sticker_continuous'])) {
        $meta_data_array[] = [
            'key' => '_sticker_continuous',
            'value' => !empty($continuous) ? $continuous : 'no'
        ];
    }
    
    if (!isset($existing_keys['_sticker_period_type'])) {
        $meta_data_array[] = [
            'key' => '_sticker_period_type',
            'value' => !empty($period_type) ? $period_type : 'until'
        ];
    }
    
    if (!isset($existing_keys['_sticker_week_day_start'])) {
        $meta_data_array[] = [
            'key' => '_sticker_week_day_start',
            'value' => !empty($week_day_start) ? $week_day_start : 'Mo'
        ];
    }
    
    if (!isset($existing_keys['_sticker_week_day_end'])) {
        $meta_data_array[] = [
            'key' => '_sticker_week_day_end',
            'value' => !empty($week_day_end) ? $week_day_end : 'Fr'
        ];
    }
    
    // Update the response meta_data
    $response->data['meta_data'] = $meta_data_array;
    
    return $response;
}