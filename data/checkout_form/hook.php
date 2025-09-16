<?php
if ( ! defined( 'ABSPATH' ) ) exit;

use Utils\Settings\HalteverbotOptions;

if ( ( isset($_GET['preview']) && $_GET['preview'] === '1' ) || HalteverbotOptions::isCheckoutModified() ) {

    // WooCommerce Checkout Template ersetzen
    add_filter( 'woocommerce_locate_template', function( $template, $template_name, $template_path ) {

        // Checkout-Form Template überschreiben
        if ( $template_name === 'checkout/form-checkout.php' ) {
            $template = plugin_dir_path( __FILE__ ) . 'form/checkout.php';
        }

        return $template;

    }, 10, 3 );

    add_action('wp_enqueue_scripts', function () 
    {
        $plugin_url = plugin_dir_url(__FILE__);

        wp_enqueue_style(
            'single-product-checkout-css',
            $plugin_url . 'css/single-product-checkout.css',
            [],
            '1.0'
        );
        wp_enqueue_script(
            'single-product-checkout-js',
            $plugin_url . 'js/single-product-checkout.js',
            [],
            '1.0'
        );
    });
}