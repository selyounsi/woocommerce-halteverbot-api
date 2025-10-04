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

        // Review Order Template überschreiben
        if ( $template_name === 'checkout/review-order.php' ) {
            $template = plugin_dir_path( __FILE__ ) . 'form/review-order.php';
        }
        
        return $template;

    }, 10, 3 );

    // WICHTIG: Füge diesen Filter hinzu für AJAX Updates - AUCH IN DER CONDITION
    add_filter('woocommerce_update_order_review_fragments', function($fragments) {
        // Erzwinge unser custom review-order Template bei AJAX Updates
        ob_start();
        wc_get_template('checkout/review-order.php');
        $fragments['.woocommerce-checkout-review-order-table'] = ob_get_clean();
        
        return $fragments;
    });

    add_action('wp_enqueue_scripts', function () 
    {
        $plugin_url = plugin_dir_url(__FILE__);

        wp_enqueue_style(
            'single-product-checkout-css',
            $plugin_url . 'css/single-product-checkout.css',
            [],
            '1.97'
        );
        wp_enqueue_script(
            'single-product-checkout-js',
            $plugin_url . 'js/single-product-checkout.js',
            [],
            '1.32'
        );
    });
}