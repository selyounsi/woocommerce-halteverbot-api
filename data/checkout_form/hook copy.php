<?php

/**
 * One-Page-Checkout für Single Product
 */
// if (isset($_GET['nowprocket']) && $_GET['nowprocket'] == '1')  {
if (isset($_GET['nowprocket']) && isset($_GET['test']))  {

    // Trash-Icon in der Checkout-Review-Tabelle hinzufügen (MUSEN AUßERHALB DES IF GET)
    add_filter('woocommerce_cart_item_name', function($product_name, $cart_item, $cart_item_key) {
        $product_name = sprintf(
            '<span class="product-name-wrapper" data-cart-item-key="%s">%s</span>',
            esc_attr($cart_item_key),
            $product_name
        );
        return $product_name;
    }, 10, 3);

    // AJAX-Handler zum Entfernen von Cart-Items (MUSEN AUßERHALB DES IF GET)
    add_action('wc_ajax_remove_cart_item', function() {
        $cart_item_key = sanitize_text_field($_POST['cart_item_key'] ?? '');
        if ($cart_item_key && WC()->cart->remove_cart_item($cart_item_key)) {
            WC()->cart->calculate_totals();
            wp_send_json_success(['fragments' => apply_filters('woocommerce_update_order_review_fragments', [])]);
        } else {
            wp_send_json_error(['message' => 'Item konnte nicht entfernt werden.']);
        }
    });

    // Entfernt den normalen Add-to-Cart-Button
    add_action('template_redirect', function () {
        if (is_product()) {
            remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
        }
    });

    // Checkout-URL auf die aktuelle Seite inkl. aller Parameter umleiten
    add_filter('woocommerce_get_checkout_url', function ($url) {
        if (is_product()) {
            return home_url( add_query_arg( [], $_SERVER['REQUEST_URI'] ) );
        }
        return $url;
    });

    // Sagt WooCommerce: "Wir sind im Checkout" – auch auf Produktseiten
    add_filter('woocommerce_is_checkout', function ($is_checkout) {
        if (is_product()) {
            return true;
        }
        return $is_checkout;
    });

    // CSS Link 
    add_action('wp_enqueue_scripts', function () {
        if (is_product()) {
            $plugin_url = plugin_dir_url(__FILE__);

            // Single Prodcut CSS
            wp_enqueue_style(
                'single-product-checkout',
                $plugin_url . 'css/single-product-checkout.css',
                [],
                '2.2'
            );
            // Notiflix CSS
            wp_enqueue_style(
                'notiflix-css',
                WHA_PLUGIN_ASSETS_URL . '/plugins/notiflix/notiflix.min.css',
                [],
                '3.2.8'
            );
            // Notiflix JS
            wp_enqueue_script(
                'notiflix-js',
                WHA_PLUGIN_ASSETS_URL . '/plugins/notiflix/notiflix.min.js',
                ['jquery'],
                '3.2.8',
                true
            );
            wp_enqueue_script('wc-add-to-cart');
        }
    });

    // Checkout immer anzeigen, auch wenn Warenkorb leer (aber ausgeblendet)
    add_action('woocommerce_after_add_to_cart_form', function () {
        if (is_product()) {
            echo '<div class="custom-checkout-wrapper" style="margin-top: 40px;">';
            echo do_shortcode('[woocommerce_checkout]');
            echo '</div>';

            // Hinweis bei leerem Warenkorb
            if (WC()->cart->is_empty()) {
                echo '<div class="empty-cart-notice" style="margin-top: 40px; padding: 20px; text-align: center; border: 1px solid #ddd;">';
                echo '<p>Bitte fügen Sie das Produkt zum Warenkorb hinzu, um zur Kasse zu gehen.</p>';
                echo '</div>';
            }

            // Container für WooCommerce Notices (falls Theme keinen hat)
            echo '<div class="woocommerce-notices-wrapper"></div>';
        }
    });

    // Verhindert, dass WooCommerce nach dem Add-to-Cart umleitet
    add_filter('woocommerce_add_to_cart_redirect', '__return_false');

    // Fügt JS für AJAX-Add-to-Cart + Checkout-Refresh hinzu
    add_action('wp_footer', function () {

        if (!is_product()) return;

        global $product;
        if (!$product) return;

        $product_id = $product->get_id();
        $add_to_cart_nonce = wp_create_nonce('woocommerce-add-to-cart');

        ?>
        <script>
            jQuery(document).ready(function($) {

                // Notiflix konfigurieren um HTML zu erlauben
                Notiflix.Report.init({
                    plainText: false,
                    messageMaxLength: 999999,
                });

                const productID = <?php echo json_encode($product_id); ?>;
                const addToCartNonce = '<?php echo esc_js($add_to_cart_nonce); ?>';

                // Button umbenennen
                $('.single_add_to_cart_button').text('Zum Warenkorb hinzufügen');
                $('.single_add_to_cart_button').removeClass('qbutton');

                addResetFormButton()

                /**
                 * Produkt in den Warenkorb legen
                 */
                $('form.cart').on('submit', function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    
                    addloading();

                    let $form = $(this);
                    let formData = $form.serializeArray();

                    // Pflichtfelder ergänzen
                    formData.push({ name: 'product_id', value: productID });
                    formData.push({ name: 'add-to-cart', value: productID });
                    formData.push({ name: 'security', value: addToCartNonce });

                    let params = new URLSearchParams();
                    formData.forEach(field => params.append(field.name, field.value));

                    fetch(wc_add_to_cart_params.wc_ajax_url.replace('%%endpoint%%', 'add_to_cart'), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: params.toString()
                    })
                    .then(res => {
                        if (!res.ok) throw new Error('Add-to-cart fehlgeschlagen');

                        // Notification ausgeben
                        setNotification('Produkt wurde deinem Warenkorb hinzugefügt.');

                        // Egal was kommt, wir reloaden einfach den Checkout-Block
                        $('.custom-checkout-wrapper').show().load(location.href + ' .custom-checkout-wrapper>*', function() {
                            $(document.body).trigger('update_checkout');
                        });
                        $('.empty-cart-notice').hide();

                        // Formular sauber zurücksetzen
                        resetForm();
                    })
                    .catch(err => {
                        console.error('AJAX Fehler:', err);
                        setNotification('Fehler beim Hinzufügen des Produkts!', 'error', 5000);
                    })
                    .finally(() => {
                        removeLoading(500, () => {
                            console.log("lol")
                            scrollToCheckout()
                        })
                    });
                });

                /**
                 * Checkout Handler
                 */
                 $('form.woocommerce-checkout').off('submit.checkout');
                $(document).on('click', '#place_order', function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    
                    addloading();

                    let formData = $('form.woocommerce-checkout').serializeArray();
                    let params = new URLSearchParams();
                    formData.forEach(field => params.append(field.name, field.value));

                    fetch(wc_checkout_params.checkout_url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: params.toString()
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.result === 'success') {
                            window.location = data.redirect;
                        } else {
                            data.messages = data.messages.replace('class="woocommerce-error"','');
                            Notiflix.Report.failure(
                                'Fehler bei der Bestellung',
                                data.messages || 'Ein unbekannter Fehler ist aufgetreten.',
                                'Verstanden',
                            );
                        }
                    })
                    .catch(err => {
                        console.error('Checkout Fehler:', err);
                        Notiflix.Report.failure(
                            'Technischer Fehler',
                            'Ein unerwarteter Fehler ist aufgetreten. Bitte versuchen Sie es erneut.',
                            'Verstanden'
                        );
                    })
                    .finally(() => {
                        removeLoading();
                    });
                });

                /**
                 * Produkt aus dem Warenkorb entfernen
                 */
                $(document).on('click', '.remove-cart-item', function(e) {
                    e.preventDefault();
                    let cartItemKey = $(this).data('cart-key');
                    
                    if (!cartItemKey) {
                        console.error('Kein cart_item_key gefunden');
                        return;
                    }

                    addloading();

                    // WC AJAX Endpoint verwenden (korrekte URL)
                    const ajaxUrl = wc_add_to_cart_params.wc_ajax_url.replace('%%endpoint%%', 'remove_cart_item');
                    
                    // FETCH verwenden (wie in deinem Beispiel)
                    fetch(ajaxUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: new URLSearchParams({
                            cart_item_key: cartItemKey
                        }).toString()
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // WooCommerce Fragments aktualisieren
                            if (data.fragments) {
                                $.each(data.fragments, function(key, value) {
                                    $(key).replaceWith(value);
                                });
                            }
                            
                            // Checkout-Block neu laden
                            $('.custom-checkout-wrapper').load(location.href + ' .custom-checkout-wrapper>*', function() {
                                $(document.body).trigger('update_checkout');
                                setTimeout(addTrashIcons, 100);
                            });
                            
                            setNotification('Produkt entfernt.', 'success');
                        } else {
                            console.error('Fehler beim Entfernen:', data);
                            setNotification('Konnte Produkt nicht entfernen!', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Fetch Fehler:', error);
                        setNotification('Fehler beim Entfernen des Produkts!', 'error');
                    })
                    .finally(() => {
                        removeLoading()
                    });
                });

                /**
                 * Nach jedem Checkout-Update Trash-Icons hinzufügen
                 */
                $(document.body).on('updated_checkout', function() {
                    setTimeout(() => {
                        addTrashIcons()
                        addToggleDetails()
                        addCustomFooterRow()
                    }, 100);
                });

                // Beim initialen Laden Trash-Icons hinzufügen
                    setTimeout(() => {
                        addTrashIcons()
                        addToggleDetails()
                        addCustomFooterRow()
                    }, 500);

                /**
                 * Reset Form
                 *
                 * @param [type] $form
                 * @return void
                 */
                function resetForm() 
                {
                    // 1. Form zurücksetzen (normale Input-Felder)
                    const form = document.querySelector('form.cart');
                    if (form) form.reset();

                    

                    // 2. WooCommerce-Meldungen entfernen
                    form.find('.woocommerce-error, .woocommerce-message').remove();

                    // 3. Validierungs-Klassen entfernen
                    form.find('.woocommerce-invalid, .woocommerce-validated')
                        .removeClass('woocommerce-invalid woocommerce-validated');

                    // 4. Add-to-Cart Button wieder aktivieren
                    form.find('.single_add_to_cart_button').prop('disabled', false);

                    // 5. WCPA / versteckte Inputs sauber zurücksetzen
                    form.find('input[type="hidden"]').each(function() {
                        var $hidden = $(this);
                        // Wenn original value gespeichert, zurücksetzen, sonst leer
                        if ($hidden.data('original-value') !== undefined) {
                            $hidden.val($hidden.data('original-value'));
                        } else {
                            $hidden.val('');
                        }
                    });

                    form.find('.wcpa_reset_field').each(function() {
                        let resetField = $(this);
                        resetField[0].click()
                    });


                    // 6. Sichtbare WCPA Inputs leeren
                    form.find('.wcpa-fp-input').val('');

                    // 7. Alle Selects auf Standard zurücksetzen
                    form.find('select').prop('selectedIndex', 0);

                    // 8. Trigger für Validierung/Change
                    form.find('input, select, textarea').trigger('change');
                }

                /**
                 * Notification mit Notiflix anzeigen
                 */
                function setNotification(message, type = 'success') {
                    // Notiflix verfügbar prüfen
                    if (typeof Notiflix === 'undefined') {
                        // Fallback zu einfacher Alert
                        alert(message);
                        return;
                    }
                    
                    switch(type) {
                        case 'success':
                            Notiflix.Notify.success(message, {
                                timeout: 3000,
                                position: 'left-top'
                            });
                            break;
                        case 'error':
                            Notiflix.Notify.failure(message, {
                                timeout: 3000,
                                position: 'left-top'
                            });
                            break;
                        case 'info':
                            Notiflix.Notify.info(message, {
                                timeout: 3000,
                                position: 'left-top'
                            });
                            break;
                        default:
                            Notiflix.Notify.success(message, {
                                timeout: 3000,
                                position: 'left-top'
                            });
                    }
                }

                /**
                 * Zusätzliche Zeile im Tabellen-Footer einfügen
                 */
                function addCustomFooterRow() {
                    // Prüfen ob die Tabelle existiert und die Zeile noch nicht hinzugefügt wurde
                    const $table = $('.woocommerce-checkout-review-order-table');
                    const $tfoot = $table.find('tfoot');
                    
                    if ($table.length && $tfoot.length && !$table.find('.custom-footer-row').length) {
                        // Neue Zeile erstellen
                        const newRow = `
                            <tr class="custom-footer-row">
                                <td colspan="2" style="text-align: center; padding: 20px; background-color: #f8f8f8; border-top: 2px solid #ddd;">
                                    <button class="further-order">
                                        🚧 Weitere Halteverbot bestellen
                                    </button>
                                </td>
                            </tr>
                        `;
                        
                        // Zeile zum Footer hinzufügen (als letzte Zeile)
                        $tfoot.append(newRow);

                        // Klick-Listener hinzufügen
                        $('.further-order').on('click', function(e) {
                            e.preventDefault();
                            scrollToForm();
                        });
                    }
                }

                /**
                 * Undocumented function
                 */
                function addResetFormButton() 
                {
                    const $addToCartButton = $('.single_add_to_cart_button');
                    const $form = $('form.cart');
                    
                    // Prüfen ob der Button und Form existieren und Reset-Button noch nicht hinzugefügt wurde
                    if ($addToCartButton.length && $form.length && !$form.find('.reset-form-button').length) {
                        // Reset-Button erstellen
                        const resetButton = `
                            <button type="button" class="reset-form-button" style="
                                background-color: #6c757d;
                                color: white;
                                padding: 12px 24px;
                                border: none;
                                border-radius: 4px;
                                font-weight: bold;
                                cursor: pointer;
                                margin-left: 10px;
                                transition: background-color 0.3s;
                            ">
                                ↺ Formular zurücksetzen
                            </button>
                        `;
                        
                        // Reset-Button nach dem Add-to-Cart Button einfügen
                        $addToCartButton.after(resetButton);
                        
                        // Klick-Event für Reset-Button
                        $('.reset-form-button').on('click', function() {
                            resetForm();
                        });
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

                /**
                 * Trash-Icons zu den Produkten im Checkout hinzufügen
                 */
                function addTrashIcons() {
                    // Nur auf Produktseiten ausführen
                    if (!$('body').hasClass('single-product')) return;
                    
                    // Durch jede Zeile in der Checkout-Tabelle iterieren
                    $('.woocommerce-checkout-review-order-table tr.cart_item').each(function() {
                        const productNameWrapper = $(this).find('.product-name-wrapper');
                        
                        if (productNameWrapper.length) {
                            const cartItemKey = productNameWrapper.data('cart-item-key');
                            
                            // Prüfen, ob bereits ein Trash-Icon existiert
                            if ($(this).find('.remove-cart-item').length === 0 && cartItemKey) {
                                // Trash-Icon erstellen und einfügen
                                const trashIcon = $('<a>', {
                                    href: '#',
                                    class: 'remove remove-cart-item',
                                    'aria-label': 'Entfernen',
                                    'data-cart-key': cartItemKey,
                                    css: {
                                        'margin-right': '8px',
                                        'color': 'red',
                                        'font-size': '16px',
                                        'text-decoration': 'none',
                                        'cursor': 'pointer'
                                    },
                                    html: '🗑️'
                                });
                                
                                // Icon vor den Produktnamen einfügen
                                productNameWrapper.before(trashIcon);
                            }
                        }
                    });
                }

                /**
                 * Add Loading Animation
                 */
                function addloading() 
                {
                    Notiflix.Loading.standard('Ladevorgang...', {
                        svgColor: '#e4525c'
                    });
                }

                /**
                 * Remove Loading Animation
                 */
                function removeLoading(delay = 500, callback = null)
                 {
                    if (callback && typeof callback === 'function') {
                        Notiflix.Loading.remove(delay);
                        setTimeout(callback, delay + 100);
                    } else {
                        Notiflix.Loading.remove(delay);
                    }
                }

                /**
                 * Scroll to checkout
                 */
                function scrollToCheckout() 
                {
                    $('html, body').animate({
                        scrollTop: $('.woocommerce-billing-fields').offset().top - 100
                    }, 1000);
                }

                /**
                 * Scroll to checkout
                 */
                function scrollToForm() 
                {
                    $('html, body').animate({
                        scrollTop: $('.cart').offset().top - 100
                    }, 1000);
                }
            });
        </script>
        <?php
    });
}