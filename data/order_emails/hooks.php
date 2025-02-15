<?php 

    /**
     * Adds the email notification status to the REST API response for orders.
     */
    add_filter('woocommerce_rest_prepare_shop_order_object', 'add_email_notification_status', 10, 3);
    function add_email_notification_status($response, $object, $request) 
    {
        // Check if the '_email_notification' meta field is set
        $email_notification = get_post_meta($object->get_id(), '_email_notification', true);

        // Default to true if the meta field is not set
        $email_notification = ($email_notification === '') ? true : filter_var($email_notification, FILTER_VALIDATE_BOOLEAN);

        // Add the 'email_notification' attribute to the API response
        $response->data['email_notification'] = $email_notification;

        return $response;
    }

    /**
     * Prevents the sending of order emails for processing orders based on a custom order meta.
     *
     */
    function prevent_email_for_processing_order( $recipient, $order ) 
    {
        if($order instanceof WC_Order ) {
            if ($order->get_meta('_disable_new_order_notification')) 
            {
                error_log("prevent_email_for_processing_order");
                return false;
            }
        }

        return $recipient;
    }
    add_filter( 'woocommerce_email_enabled_processing_order', 'prevent_email_for_processing_order', 10, 2 );
    add_filter( 'woocommerce_email_enabled_customer_processing_order', 'prevent_email_for_processing_order', 10, 2 );
    add_filter( 'woocommerce_email_enabled_customer_paid_for_order', 'prevent_email_for_processing_order', 10, 2 );


    /**
     * Prevents the sending of order emails for completed orders based on a custom order meta.
     */
    function prevent_email_for_completed_order( $recipient, $order ) 
    {
        if($order instanceof WC_Order ) {
            error_log("prevent_email_for_completed_order");
            return false;
        }

        return $recipient;
    }
    add_filter( 'woocommerce_email_enabled_customer_completed_order', 'prevent_email_for_completed_order', 10, 2 );
    add_filter( 'woocommerce_email_enabled_completed_order', 'prevent_email_for_completed_order', 10, 2 );