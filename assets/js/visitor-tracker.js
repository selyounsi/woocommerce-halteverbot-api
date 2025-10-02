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
    trackAddToCart();
    trackContactClicks();
    trackCheckoutStart();
    trackProductSearch();
    trackCategoryView();
    trackWishlistEvents();
    trackCouponUsage();
    trackAccountActions();
    trackShippingSelection();
    trackPaymentSelection();
});

/**
 * Track Telefon und E-Mail Klicks
 */
function trackContactClicks() {
    document.addEventListener('click', function(e) {
        const target = e.target.closest('a[href^="tel:"], a[href^="mailto:"], a[href*="phone"], a[href*="contact"]') || 
                       e.target.closest('button[onclick*="tel:"], button[onclick*="mailto:"]');
        
        if (target) {
            const href = target.getAttribute('href') || target.getAttribute('onclick') || '';
            
            // Telefon Klick
            if (href.includes('tel:') || href.includes('callto:')) {
                const phoneNumber = href.match(/tel:([^"']+)/)?.[1] || href.match(/callto:([^"']+)/)?.[1];
                if (phoneNumber) {
                    sendTrackedEvent('phone_click', phoneNumber);
                }
            }
            
            // E-Mail Klick
            else if (href.includes('mailto:')) {
                const email = href.match(/mailto:([^"']+)/)?.[1];
                if (email) {
                    sendTrackedEvent('email_click', email);
                }
            }
            
            // Kontakt-Link Klick
            // else if (target.textContent.match(/kontakt|contact|anruf|call|telefon|phone/i)) {
            //     sendTrackedEvent('contact_click');
            // }
        }
    });
}

/**
 * Send Tracked Event
 */
function sendTrackedEvent(eventType, extraValue = null, productId = null, quantity = null, orderId = null) 
{
    jQuery.post(VisitorTrackerData.ajax_url, {
        action: 'track_wc_event',
        nonce: VisitorTrackerData.nonce,
        event_type: eventType,
        product_id: productId,
        extra_value: extraValue,
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
            sendTrackedEvent('product_view', null, productId);
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
                    sendTrackedEvent('add_to_cart', null, productId, quantity);
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
        sendTrackedEvent('order_complete', null, null, null, orderId, orderKey);
    }
}

/**
 * Track Checkout Start - Optimierte Version
 */
function trackCheckoutStart() {
    let checkoutTracked = false;
    
    // Prüfe ob wir auf der Checkout-Seite sind
    const isCheckoutPage = window.location.href.includes('/checkout') || 
                          document.body.classList.contains('checkout') ||
                          document.querySelector('form.woocommerce-checkout');
    
    if (!isCheckoutPage) return;
    
    function trackCheckout() {
        if (!checkoutTracked) {
            checkoutTracked = true;
            sendTrackedEvent('checkout_start');
            console.log('Checkout gestartet getrackt');
        }
    }
    
    // 1. Sofort tracken wenn von Warenkorb kommend
    if (document.referrer.includes('/cart')) {
        trackCheckout();
        return;
    }
    
    // 2. Tracken bei erster Interaktion mit Formular
    const formFields = document.querySelectorAll('form.woocommerce-checkout input, form.woocommerce-checkout select');
    formFields.forEach(field => {
        field.addEventListener('focus', trackCheckout, { once: true });
        field.addEventListener('input', trackCheckout, { once: true });
    });
    
    // 3. Falls keine Interaktion: nach 8 Sekunden tracken
    setTimeout(trackCheckout, 8000);
}

/**
 * Track Product Search
 */
function trackProductSearch() {
    const searchForm = document.querySelector('form[role="search"], .woocommerce-product-search');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            const searchInput = this.querySelector('input[type="search"], input[name="s"]');
            if (searchInput && searchInput.value.trim()) {
                sendTrackedEvent('product_search', searchInput.value.trim());
            }
        });
    }
}

/**
 * Track Category/Shop Views
 */
function trackCategoryView() {
    if (document.body.classList.contains('archive') || 
        document.body.classList.contains('tax-product_cat') ||
        window.location.href.includes('/shop') ||
        window.location.href.includes('/product-category')) {
        sendTrackedEvent('category_view');
    }
}

/**
 * Track Wishlist/Bookmark Events
 */
function trackWishlistEvents() {
    document.addEventListener('click', function(e) {
        // Wishlist Buttons erkennen
        if (e.target.closest('.add_to_wishlist, .wishlist-button, [class*="wishlist"], .yith-wcwl-add-to-wishlist')) {
            const productId = e.target.closest('button')?.dataset?.product_id || 
                            document.querySelector('[name="add-to-cart"]')?.value;
            if (productId) {
                sendTrackedEvent('add_to_wishlist', null, productId);
            }
        }
    });
}

/**
 * Track Coupon Usage
 */
function trackCouponUsage() {
    const couponForm = document.querySelector('.coupon-form, [class*="coupon"]');
    if (couponForm) {
        couponForm.addEventListener('submit', function(e) {
            const couponInput = this.querySelector('input[name="coupon_code"]');
            if (couponInput && couponInput.value.trim()) {
                sendTrackedEvent('coupon_applied', couponInput.value.trim());
            }
        });
    }
}

/**
 * Track Account Actions
 */
function trackAccountActions() {
    // Login
    document.addEventListener('click', function(e) {
        if (e.target.closest('.login-button, [href*="my-account"]')) {
            sendTrackedEvent('account_login_attempt');
        }
    });
    
    // Registration
    if (window.location.href.includes('my-account') && 
        document.querySelector('form[class*="register"]')) {
        sendTrackedEvent('account_registration_view');
    }
}

/**
 * Track Shipping Method Selection
 */
function trackShippingSelection() {
    document.addEventListener('change', function(e) {
        if (e.target.name === 'shipping_method[0]' || e.target.name === 'shipping_method') {
            sendTrackedEvent('shipping_method_selected', e.target.value);
        }
    });
}

/**
 * Track Payment Method Selection
 */
function trackPaymentSelection() {
    document.addEventListener('change', function(e) {
        if (e.target.name === 'payment_method' || e.target.closest('.wc_payment_method')) {
            sendTrackedEvent('payment_method_selected', e.target.value);
        }
    });
}