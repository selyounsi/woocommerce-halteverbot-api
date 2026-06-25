<?php
// Verhindere direkten Zugriff
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * OFFER E-MAIL
 */
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

/**
 * INVOICE E-MAIL
 */
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

/**
 * OFFER REMINDER E-MAIL
 */
function add_custom_offer_reminder_email( $email_classes )
{
    $class_file = WHA_PLUGIN_PATH . '/data/order_emails/classes/offer-reminder-email-class.php';

    require_once( $class_file );

    $email_classes['WC_Email_Offer_Reminder'] = new WC_Email_Offer_Reminder();
    return $email_classes;
}
add_filter( 'woocommerce_email_classes', 'add_custom_offer_reminder_email' );

/**
 * INVOICE REMINDER E-MAIL
 */
function add_custom_invoice_reminder_email( $email_classes )
{
    $class_file = WHA_PLUGIN_PATH . '/data/order_emails/classes/invoice-reminder-email-class.php';

    require_once( $class_file );

    $email_classes['WC_Email_Invoice_Reminder'] = new WC_Email_Invoice_Reminder();
    return $email_classes;
}
add_filter( 'woocommerce_email_classes', 'add_custom_invoice_reminder_email' );

/**
 * REVIEW E-MAIL
 */
function add_custom_review_email( $email_classes ) 
{
    $class_file = WHA_PLUGIN_PATH . '/data/order_emails/classes/review-email-class.php';

    // include our custom email class
    require_once( $class_file );

    // add the email class to the list of email classes that WooCommerce loads
    $email_classes['WC_Email_review'] = new WC_Email_Review();
    return $email_classes;
}
add_filter( 'woocommerce_email_classes', 'add_custom_review_email' );