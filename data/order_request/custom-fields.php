<?php

use Utils\NegativeListPdfGenerator;

/**
 * Plugin Name: Custom Order File Uploads
 * Description: Adds custom file upload fields for Application, Approval, Rejection, and Negativliste in WooCommerce orders.
 */

// Enqueue the WordPress media library on admin pages
add_action('admin_enqueue_scripts', function () {
    if (is_admin()) {
        wp_enqueue_media();
    }
});

// Add custom file upload fields to the WooCommerce order edit page
add_action('woocommerce_admin_order_data_after_order_details', 'add_custom_file_upload_fields');
function add_custom_file_upload_fields($order) 
{    
    // Retrieve existing file URLs from the order meta
    $application_file = get_post_meta($order->get_id(), '_file_upload_application', true);
    $approval_file = get_post_meta($order->get_id(), '_file_upload_approval', true);
    $rejection_file = get_post_meta($order->get_id(), '_file_upload_rejection', true);
    $negativliste_file = get_post_meta($order->get_id(), '_file_upload_negativliste', true);

    ?>
    <div class="form-field form-field-wide">
        <h3><?php esc_html_e('Zusätzliche Dokumente', WHA_TRANSLATION_KEY); ?></h3>

        <!-- Antrag -->
        <div class="field-container">
            <div class="field">
                <label for="application_file"><?php esc_html_e('Antrag', WHA_TRANSLATION_KEY); ?></label>
                <input type="text" id="application_file" name="application_file" value="<?php echo esc_url($application_file); ?>" placeholder="File URL">
            </div>
            <button class="set_custom_images button">PDF setzen</button>
            <?php if ($application_file): ?>
                <br><a href="<?php echo esc_url($application_file); ?>" target="_blank"><?php esc_html_e('Antrag ansehen', WHA_TRANSLATION_KEY); ?></a>
            <?php endif; ?>
        </div>

        <!-- Genehmigung -->
        <div class="field-container">
            <div class="field">
                <label for="approval_file"><?php esc_html_e('Genehmigung', WHA_TRANSLATION_KEY); ?></label>
                <input type="text" id="approval_file" name="approval_file" value="<?php echo esc_url($approval_file); ?>" placeholder="File URL">
            </div>
            <button class="set_custom_images button">PDF setzen</button>
            <?php if ($approval_file): ?>
                <br><a href="<?php echo esc_url($approval_file); ?>" target="_blank"><?php esc_html_e('Genehmigung ansehen', WHA_TRANSLATION_KEY); ?></a>
            <?php endif; ?>
        </div>

        <!-- Ablehnung -->
        <div class="field-container">
            <div class="field">
                <label for="rejection_file"><?php esc_html_e('Ablehnung', WHA_TRANSLATION_KEY); ?></label>
                <input type="text" id="rejection_file" name="rejection_file" value="<?php echo esc_url($rejection_file); ?>" placeholder="File URL">
            </div>
            <button class="set_custom_images button">PDF setzen</button>
            <?php if ($rejection_file): ?>
                <br><a href="<?php echo esc_url($rejection_file); ?>" target="_blank"><?php esc_html_e('Ablehnung ansehen', WHA_TRANSLATION_KEY); ?></a>
            <?php endif; ?>
        </div>

        <!-- Negativliste -->
        <div class="field-container">
            <div class="field">
                <label for="negativliste_file"><?php esc_html_e('Negativliste', WHA_TRANSLATION_KEY); ?></label>
                <input type="text" id="negativliste_file" readonly name="negativliste_file" value="<?php echo esc_url($negativliste_file); ?>" placeholder="File URL">
            </div>
            <button id="generate_negative_list_pdf" class="button">PDF setzen</button>
            <?php if ($negativliste_file): ?>
                <br><a href="<?php echo esc_url($negativliste_file); ?>" target="_blank"><?php esc_html_e('Negativliste ansehen', WHA_TRANSLATION_KEY); ?></a>
            <?php endif; ?>
        </div>
    </div>

    <style>
        .field-container {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            margin-bottom: 12px;
        }

        .field-container .field {
            flex: 1;
        }

        .field-container a {
            width: 100%;
        }
    </style>

    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#generate_negative_list_pdf').on('click', function(e) {
                e.preventDefault();

                var orderId = <?php echo $order->get_id(); ?>;
                var button = $(this);
                var message = $('#negative_list_message');

                button.prop('disabled', true).text('<?php esc_html_e('Generieren...', WHA_TRANSLATION_KEY); ?>');

                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'generate_negative_list_pdf',
                        order_id: orderId,
                    },
                    success: function(response) {
                        button.prop('disabled', false).text('<?php esc_html_e('PDF generieren', WHA_TRANSLATION_KEY); ?>');
                        if (response.success) {
                            message.text('<?php esc_html_e('PDF erfolgreich erstellt:', WHA_TRANSLATION_KEY); ?> ' + response.data.url);
                            location.reload(); // Seite aktualisieren, um den Link anzuzeigen
                        } else {
                            message.text('<?php esc_html_e('Fehler:', WHA_TRANSLATION_KEY); ?> ' + response.data.message);
                        }
                    },
                    error: function() {
                        button.prop('disabled', false).text('<?php esc_html_e('PDF generieren', WHA_TRANSLATION_KEY); ?>');
                        message.text('<?php esc_html_e('Ein Fehler ist aufgetreten.', WHA_TRANSLATION_KEY); ?>');
                    }
                });
            });
        });
    </script>

    <?php
}

/**
 * Save the custom file URLs when the order is updated
 */
add_action('woocommerce_process_shop_order_meta', 'save_custom_file_uploads');
function save_custom_file_uploads($order_id) {
    // Save Application file
    if (isset($_POST['application_file'])) {
        update_post_meta($order_id, '_file_upload_application', esc_url_raw($_POST['application_file']));
    }

    // Save Approval file
    if (isset($_POST['approval_file'])) {
        update_post_meta($order_id, '_file_upload_approval', esc_url_raw($_POST['approval_file']));
    }

    // Save Rejection file
    if (isset($_POST['rejection_file'])) {
        update_post_meta($order_id, '_file_upload_rejection', esc_url_raw($_POST['rejection_file']));
    }

    // Save Negativliste file
    if (isset($_POST['negativliste_file'])) {
        update_post_meta($order_id, '_file_upload_negativliste', esc_url_raw($_POST['negativliste_file']));
    }
}

/**
 * JavaScript to handle the Media Library
 */
add_action('admin_footer', 'custom_file_uploads_media_library_js');
function custom_file_uploads_media_library_js() {
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            if ($('.set_custom_images').length > 0) {
                if (typeof wp !== 'undefined' && wp.media && wp.media.editor) {
                    $('.set_custom_images').on('click', function(e) {
                        e.preventDefault();
                        var button = $(this);
                        var inputField = button.prev();
                        wp.media.editor.send.attachment = function(props, attachment) {
                            inputField.val(attachment.url); // Set the URL of the selected media
                        };
                        wp.media.editor.open(button);
                        return false;
                    });
                }
            }
        });
    </script>
    <?php
}

/**
 * AJAX-Handler für die PDF-Generierung
 */
add_action('wp_ajax_generate_negative_list_pdf', 'generate_negative_list_pdf_php');
function generate_negative_list_pdf_php() 
{
    if (!isset($_POST['order_id'])) {
        wp_send_json_error(['message' => 'Ungültige Anfrage.']);
    }

    $order_id = intval($_POST['order_id']);
    $order = wc_get_order($order_id);

    if (!$order) {
        wp_send_json_error(['message' => 'Bestellung nicht gefunden.']);
    }

    $generator = new NegativeListPdfGenerator($order);
    $generator->generatePdf();
}