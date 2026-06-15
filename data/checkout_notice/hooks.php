<?php
/**
 * Checkout-Hinweis
 *
 * Zeigt einen im Backend (Halteverbot App → Einstellungen → "Checkout-Hinweis")
 * konfigurierbaren, auffälligen Hinweistext an:
 *   - oben im Checkout
 *   - oben auf der Bestellbestätigung / Danke-Seite
 *
 * Aktivierung & Text werden über HalteverbotOptions / HalteverbotSettings gesteuert.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Utils\Settings\HalteverbotOptions;

if ( ! function_exists( 'wha_render_checkout_notice' ) ) {
    /**
     * Gibt die Hinweis-Box aus (Markup + einmalige Styles).
     */
    function wha_render_checkout_notice() {
        if ( ! HalteverbotOptions::isCheckoutNoticeEnabled() ) {
            return;
        }

        $title = HalteverbotOptions::getCheckoutNoticeTitle();
        $text  = HalteverbotOptions::getCheckoutNoticeText();

        // Styles nur einmal pro Seitenaufruf ausgeben.
        static $style_printed = false;
        if ( ! $style_printed ) {
            $style_printed = true;
            ?>
            <style>
                .wha-checkout-notice{
                    position:relative;
                    border:2px solid #e0a800;
                    background:#fff8e1;
                    color:#5f4b00;
                    border-radius:8px;
                    padding:16px 18px 16px 54px;
                    margin:0 0 24px;
                    box-shadow:0 2px 8px rgba(0,0,0,.08);
                    font-size:15px;
                    line-height:1.55;
                }
                .wha-checkout-notice::before{
                    content:"\26A0";
                    position:absolute;
                    left:18px;
                    top:15px;
                    font-size:24px;
                    line-height:1;
                }
                .wha-checkout-notice__title{
                    margin:0 0 6px;
                    font-size:16px;
                    font-weight:700;
                    text-transform:uppercase;
                    letter-spacing:.3px;
                }
                .wha-checkout-notice__text p{margin:0 0 8px;}
                .wha-checkout-notice__text p:last-child{margin-bottom:0;}
                .wha-checkout-notice__text a{color:#0073aa;text-decoration:underline;}
            </style>
            <?php
        }
        ?>
        <div class="wha-checkout-notice" role="alert">
            <?php if ( $title !== '' ) : ?>
                <p class="wha-checkout-notice__title"><?php echo esc_html( $title ); ?></p>
            <?php endif; ?>
            <div class="wha-checkout-notice__text">
                <?php echo wpautop( wp_kses_post( $text ) ); ?>
            </div>
        </div>
        <?php
    }
}

/**
 * Auf der Bestellbestätigung / Danke-Seite (oben, vor den Bestelldetails).
 * Die Bankdaten (Vorkasse/BACS) gibt WooCommerce dort darunter ohnehin selbst aus.
 */
add_action( 'woocommerce_before_thankyou', 'wha_render_checkout_notice', 5 );

/**
 * Im Checkout (oben über dem Formular).
 *
 * Bei aktivem "Kassensystem modifizieren" (oder Vorschau) wird das eigene
 * Checkout-Template verwendet, das den Hinweis direkt selbst ausgibt
 * (data/checkout_form/form/checkout.php). Den Core-Hook registrieren wir
 * daher nur, wenn das eigene Template NICHT greift – sonst doppelte Ausgabe.
 */
$wha_custom_checkout_active = ( isset( $_GET['preview'] ) && $_GET['preview'] === '1' )
    || HalteverbotOptions::isCheckoutModified();

if ( ! $wha_custom_checkout_active ) {
    add_action( 'woocommerce_before_checkout_form', 'wha_render_checkout_notice', 5 );
}
