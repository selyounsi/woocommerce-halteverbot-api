jQuery(document).ready(function($) {
    // Standard Page Tracking
    $.post(VisitorTrackerData.ajax_url, {
        action: 'track_visitor',
        nonce: VisitorTrackerData.nonce,
        referrer: document.referrer,
        url: window.location.href,
        userAgent: navigator.userAgent,
        pageTitle: document.title
    });

    // WooCommerce Event Tracking
    trackProductView();
    trackOrderComplete();
    trackAddToCart()
});

/**
 * Send Tracked Event
 */
function sendTrackedEvent(eventType, productId = null, quantity = null, orderId = null) 
{
    jQuery.post(VisitorTrackerData.ajax_url, {
        action: 'track_wc_event',
        nonce: VisitorTrackerData.nonce,
        event_type: eventType,
        product_id: productId,
        quantity: quantity,
        order_id: orderId,
        url: window.location.href,
        userAgent: navigator.userAgent
    });
}

/**
 * Track Product view
 */
function trackProductView()
{
    if (document.body.classList.contains('single-product')) {
        const productId = document.querySelector('[name="add-to-cart"]')?.value || document.querySelector('[name="product_id"]')?.value;
        if (productId) {
            sendTrackedEvent('product_view', productId);
        }
    }
}

/**
 * Track add to Cart
 */
function trackAddToCart()
{
    document.addEventListener('click', function(e) {
        if (e.target.closest('.add_to_cart_button, .single_add_to_cart_button')) {
            const button = e.target.closest('button');
            const productId = button.value || 
                            document.querySelector('input[name="add-to-cart"]')?.value ||
                            button.dataset.product_id;
            const quantity = document.querySelector('input[name="quantity"]')?.value || 1;
            
            if (productId) {
                setTimeout(() => {
                    sendTrackedEvent('add_to_cart', productId, quantity);
                }, 500); // Kurze Verzögerung für AJAX Completion
            }
        }
    });
}

/**
 * Track Order Complete
 */
function trackOrderComplete() 
{
    const urlParams = new URLSearchParams(window.location.search);
    const orderKey = urlParams.get('key');

    if (orderKey && orderKey.startsWith('wc_order_')) {
        
        let orderId = null;

        const urlMatch = window.location.pathname.match(/\/(\d+)\/?$/);
        if (urlMatch) orderId = urlMatch[1];
        
        if (!orderId) {
            const orderElement = document.querySelector('.order-number, .order details, [class*="order"], .woocommerce-order-details');
            if (orderElement) {
                const text = orderElement.textContent;
                const idMatch = text.match(/#?(\d+)/);
                if (idMatch) orderId = idMatch[1];
            }
        }
        
        if (!orderId) {
            const keyMatch = orderKey.match(/(\d+)$/);
            if (keyMatch) orderId = keyMatch[1];
        }
        
        console.log('TRACKER: Order complete - Key:', orderKey, 'ID:', orderId);
        sendTrackedEvent('order_complete', null, null, orderId, orderKey);
    }
}