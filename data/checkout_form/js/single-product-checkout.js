jQuery( function($) 
{
    /**
     * Move review order table
     */
    function moveReviewOrderTable() 
    {
        var $orderTable = $('.shop_table.woocommerce-checkout-review-order-table');
        var $container = $('#woocommerce_order_review');

        if($orderTable.length && $container.length) {
            $container.append($orderTable);
        }
    }

    /**
     * Move payment Section
     */
    function movePaymentSection() 
    {
        var $container = $('#woocommerce_checkout_payment');

        if($container.length) {
            // h3 "Zahlungsart auswählen"
            var $heading = $('#order_payment_heading');
            if($heading.length && $heading.parent()[0] !== $container[0]) {
                $container.append($heading);
            }

            // div.ppcp-messages (PayPal Messages)
            var $ppMessages = $('.ppcp-messages');
            if($ppMessages.length && $ppMessages.parent()[0] !== $container[0]) {
                $container.append($ppMessages);
            }

            // div#payment.woocommerce-checkout-payment
            var $paymentDiv = $('#payment.woocommerce-checkout-payment');
            if($paymentDiv.length && $paymentDiv.parent()[0] !== $container[0]) {
                $container.append($paymentDiv);
            }
        }
    }

    /**
     * Aufklappmenü mit Icon
     */
    function addToggleDetails() {
        $('.woocommerce-checkout-review-order-table tr.cart_item').each(function() {
            const $metaList = $(this).find('.wcpa_cart_meta');
            
            if ($metaList.length > 0 && $(this).find('.toggle-details-btn').length === 0) {
                // Toggle-Button mit Icon erstellen
                const toggleBtn = $('<button>', {
                    class: 'toggle-details-btn',
                    html: '🔽 Details anzeigen',
                    type: 'button'
                });
                
                // Button direkt vor der UL-Liste einfügen
                toggleBtn.insertBefore($metaList);
                
                toggleBtn.on('click', function() {
                    $metaList.toggleClass('show');
                    $(this).html($metaList.hasClass('show') ? '🔼 Details ausblenden' : '🔽 Details anzeigen');
                });
            }
        });
    }


    function reRender() 
    {
        moveReviewOrderTable();
        movePaymentSection();
        addToggleDetails();
    }


    // Direkt nach DOM ready
    reRender()

    // Jedes Mal, wenn WooCommerce den Checkout updated
    $(document.body).on('updated_checkout', reRender);

    // Optional: Wenn Payment-Methode gewechselt wird
    $(document.body).on('payment_method_selected', reRender);
});