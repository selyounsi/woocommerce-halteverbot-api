<?php

// Überprüfen, ob das Plugin aktiv ist
if ( is_plugin_active( 'bp-custom-order-status-for-woocommerce/main.php' ) ) {

    $statuses = [
        [
            "name" => "Abgelehnt- anderweitige Arbeiten",
            "slug" => "rejected-other"
        ],
        [
            "name" => "Abgelehnt – Zeit Überschreitung",
            "slug" => "rejected-overrun"
        ],
        [
            "name" => "Abgelehnt – festes Halteverbot",
            "slug" => "rejected-fixed"
        ],
        [
            "name" => "Abgelehnt – sonstiges",
            "slug" => "rejected"
        ],
        [
            "name" => "Aufgestellt",
            "slug" => "installed"
        ],
        [
            "name" => "Genehmigt",
            "slug" => "approved"
        ],
        [
            "name" => "Beantragt",
            "slug" => "requested"
        ]
    ];

    $existing_statuses = wc_get_order_statuses();

    // var_dump($existing_statuses);
    
    foreach ( $statuses as $status ) {

        $slug       = $status['slug'];
        $name       = $status['name'];
        $full_slug  = 'wc-' . $slug;
    
        if ( ! isset( $existing_statuses[ $full_slug ] ) ) 
        {    
            wp_insert_post( array(
                'post_title'  => $name,
                'post_name'   => $slug,
                'post_type'   => 'order_status',
                'post_status' => 'publish',
                'meta_input'  => array(
                    'status_slug' => $slug,
                    'status_icon' => '0',
                    'what_to_show' => 'text',
                    'text_color' => '#000000',
                    'background_color' => '#eeeeee',
                    'is_status_paid' => '0',
                    'downloadable_grant' => '0',
                    '_enable_action_status' => '1',
                    '_enable_bulk' => '1',
                    '_enable_order_edit' => '0',
                    '_enable_email' => '1',
                    '_email_type' => 'customer',
                )
            ) );
        }
    }
}