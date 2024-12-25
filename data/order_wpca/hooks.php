<?php

/**
 * Inserts data into the order object
 */
add_filter('woocommerce_rest_prepare_shop_order_object', 'add_wcpa_to_order_response', 10, 3);
function add_wcpa_to_order_response($response, $object, $request) 
{
    // Initialisieren des Wertes
    $has_wcpa = false;
    $wcpa_fields = [];

    // Abrufen aller Bestellpositionen
    foreach ($object->get_items() as $item) {

        $meta_data = $item->get_meta(WCPA_ORDER_META_KEY, true);
        if ($meta_data) {
            $has_wcpa = true;
        }

        // Alle Metadaten für die Position abrufen
        foreach ($item->get_meta_data() as $meta) 
        {
            if(!empty($meta->value) && $meta->key !== WCPA_ORDER_META_KEY) {
                $wcpa_fields[] = $meta;
            }
        }
    }

    // Setzen des Wertes in der API-Antwort
    $response->data['has_wcpa'] = $has_wcpa;
    $response->data['wcpa_fields'] = $wcpa_fields;

    return $response;
}