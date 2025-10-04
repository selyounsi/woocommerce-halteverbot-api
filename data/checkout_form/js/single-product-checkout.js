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
     * Toggle-Button für Produktdetails
     */
    function initToggleButtons() {
        $('.woocommerce-checkout-review-order-table .toggle-details-btn').on('click', function() {
            const $button = $(this);
            const $metaList = $button.next('.wcpa_cart_meta');
            
            $metaList.toggle();
            $button.html($metaList.is(':visible') ? 
                '🔼 Details ausblenden' : '🔽 Details anzeigen'
            );
        });
    }

    /**
     * Füge alle Parameter der aktuellen URL zu WooCommerce AJAX Requests hinzu
     */
    function addAllUrlParameters() {
        // Überwache alle AJAX Requests
        $(document).ajaxSend(function(event, xhr, settings) {
            if (settings.url && settings.url.includes('wc-ajax=')) {
                const currentUrl = new URL(window.location.href);
                const targetUrl = new URL(settings.url, window.location.origin);
                
                // Füge alle Parameter der aktuellen URL zum AJAX Request hinzu
                currentUrl.searchParams.forEach((value, key) => {
                    targetUrl.searchParams.set(key, value);
                });
                
                // Aktualisiere die URL
                settings.url = targetUrl.toString();
            }
        });
    }

    /**
     * Rerender 
     */
    function reRender() 
    {
        moveReviewOrderTable();
        movePaymentSection();
        initToggleButtons();
    }

    // Direkt nach DOM ready
    reRender()
    addAllUrlParameters();

    // Jedes Mal, wenn WooCommerce den Checkout updated
    $(document.body).on('updated_checkout', reRender);

    // Optional: Wenn Payment-Methode gewechselt wird
    $(document.body).on('payment_method_selected', reRender);
});