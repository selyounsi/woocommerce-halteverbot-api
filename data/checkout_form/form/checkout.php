<?php
defined( 'ABSPATH' ) || exit;
$checkout = WC()->checkout();
?>

<form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">

    <div class="checkout-grid">

        <!-- LINKE SPALTE -->
        <div class="checkout-left">

            <!-- Rechnungsdetails -->
            <div id="woocommerce_checkout_billing">
                <?php do_action( 'woocommerce_checkout_billing' ); ?>
            </div>


            <!-- Zahlungsarten -->
            <div id="woocommerce_checkout_payment">

            </div>

            <!-- Zusätzliche Informationen -->
            <?php
                $additional_fields = WC()->checkout()->get_checkout_fields('order');
                if ($additional_fields) {
                    echo '<div class="woocommerce-additional-fields">';
                    echo '<h3>Zusätzliche Informationen</h3>';
                    echo '<div class="woocommerce-additional-fields__field-wrapper">';
                    
                    foreach ($additional_fields as $key => $field) {
                        woocommerce_form_field($key, $field, WC()->checkout()->get_value($key));
                    }
                    
                    echo '</div>';
                    echo '</div>';
                }
            ?>
        </div>

        <!-- RECHTE SPALTE -->
        <div class="checkout-right">

            <h3>Deine Bestellung</h3>

            <!-- Warenkorb / Order Review -->
            <div id="woocommerce_order_review">

            </div>

            <!-- Germanized Checkboxen -->
            <?php
            if ( function_exists( 'woocommerce_gzd_template_checkout_legal' ) ) {
                woocommerce_gzd_template_checkout_legal();
            }
            if ( function_exists( 'woocommerce_gzd_template_checkout_service' ) ) {
                woocommerce_gzd_template_checkout_service();
            }
            ?>

            <div class="wc-gzd-checkboxs">

            </div>

            <div class="place-order-container">
                <?php do_action( 'woocommerce_checkout_order_review' ); ?>
            </div>
        </div>
    </div>
</form>
