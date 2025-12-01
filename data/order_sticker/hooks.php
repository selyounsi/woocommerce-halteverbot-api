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
    
    // Use WPCAFields to get date/time from first line item
    $start_date = '';
    $start_time = '';
    $end_date = '';
    $end_time = '';
    
    try {
        $wcpa_fields = new Utils\WPCAFields($object);
        $fieldsets = $wcpa_fields->getMetaFieldsets();
        
        if (!empty($fieldsets)) {
            $first_fieldset = reset($fieldsets);
            $start_date = $first_fieldset['startdate'] ?? '';
            $start_time = $first_fieldset['starttime'] ?? '';
            $end_date = $first_fieldset['enddate'] ?? '';
            $end_time = $first_fieldset['endtime'] ?? '';
        }
    } catch (Exception $e) {
        // Fallback
        $items = $object->get_items();
        if (!empty($items)) {
            $first_item = reset($items);
            $start_date = $first_item->get_meta('Startdatum', true);
            $start_time = $first_item->get_meta('Anfangszeit', true);
            $end_date = $first_item->get_meta('Enddatum', true);
            $end_time = $first_item->get_meta('Endzeit', true);
        }
    }
    
    // Set defaults if values don't exist
    $response->data['sticker_info'] = [
        'continuous' => !empty($continuous) ? $continuous : 'no',
        'period_type' => !empty($period_type) ? $period_type : 'until',
        'week_day_start' => !empty($week_day_start) ? $week_day_start : 'Mo',
        'week_day_end' => !empty($week_day_end) ? $week_day_end : 'Fr',
        // Read-only date/time fields
        'start_date' => $start_date,
        'start_time' => $start_time,
        'end_date' => $end_date,
        'end_time' => $end_time
    ];
    
    return $response;
}