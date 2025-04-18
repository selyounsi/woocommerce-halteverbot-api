<?php
// Verhindere direkten Zugriff
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function add_custom_offer_email( $email_classes ) 
{
    $class_file = WHA_PLUGIN_PATH . '/data/order_emails/classes/offer-email-class.php';

    // include our custom email class
    require_once( $class_file );

    // add the email class to the list of email classes that WooCommerce loads
    $email_classes['WC_Email_Offer'] = new WC_Email_Offer();
    return $email_classes;
}
add_filter( 'woocommerce_email_classes', 'add_custom_offer_email' );

function add_custom_invoice_email( $email_classes ) 
{
    $class_file = WHA_PLUGIN_PATH . '/data/order_emails/classes/invoice-email-class.php';

    // include our custom email class
    require_once( $class_file );

    // add the email class to the list of email classes that WooCommerce loads
    $email_classes['WC_Email_Invoice'] = new WC_Email_Invoice();
    return $email_classes;
}
add_filter( 'woocommerce_email_classes', 'add_custom_invoice_email' );