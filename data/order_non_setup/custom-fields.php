<?php

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

// Schritt 1: Metabox zur Bestellung hinzufügen.
add_action('add_meta_boxes', 'admin_order_non_setup_metabox');
function admin_order_non_setup_metabox()
{
    $screen = class_exists('\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController') && wc_get_container()->get(CustomOrdersTableController::class)->custom_orders_table_usage_is_enabled()
        ? wc_get_page_screen_id('shop-order')
        : 'shop_order';

    add_meta_box(
        'order_non_setup_box',
        __('Nicht aufstellbar', WHA_TRANSLATION_KEY),
        'add_custom_non_setup_fields',
        $screen,
        'advanced',
        'high'
    );
}

// Schritt 2: Benutzerdefinierte Felder rendern.
function add_custom_non_setup_fields($order)
{
    $order_id = $order->get_id();
    $manager  = new \Utils\OrderNonSetupManager($order_id);
    $data     = $manager->getNonSetupData();

    $file_urls = $data['files'];
    $info      = $data['info'];

    if (!is_array($file_urls)) {
        $file_urls = [];
    }
    ?>
    <div class="form-field form-field-wide">
        <h4>Foto-Nachweise (Nicht aufstellbar)</h4>
        <div id="order_non_setup_files_repeater">
            <?php
            if (!empty($file_urls)) {
                foreach ($file_urls as $index => $file_url) {
                    $file_url     = esc_url($file_url);
                    $preview_link = !empty($file_url) ? '<a href="' . $file_url . '" target="_blank">Vorschau</a>' : '';
                    echo '<div class="non-setup-file">';
                    echo '<label for="order_non_setup_files_' . $index . '">Datei hochladen</label>';
                    echo $preview_link ? ' | ' . $preview_link : '';
                    echo '<input type="text" class="non-setup-file-url" name="order_non_setup_files[' . $index . ']" id="order_non_setup_files_' . $index . '" value="' . $file_url . '" placeholder="Datei-URL" />';
                    echo '<button type="button" class="button upload-non-setup-file-button" onclick="uploadNonSetupFile(this)">Datei auswählen</button>';
                    echo '<button type="button" class="button btn-danger" onclick="removeNonSetupField(this)">Entfernen</button>';
                    echo '</div>';
                }
            } else {
                echo '<div class="non-setup-file">';
                echo '<label for="order_non_setup_files_0">Datei hochladen</label>';
                echo '<input type="text" class="non-setup-file-url" name="order_non_setup_files[0]" id="order_non_setup_files_0" value="" placeholder="Datei-URL" />';
                echo '<button type="button" class="button upload-non-setup-file-button" onclick="uploadNonSetupFile(this)">Datei auswählen</button>';
                echo '<button type="button" class="button btn-danger" onclick="removeNonSetupField(this)">Entfernen</button>';
                echo '</div>';
            }
            ?>
        </div>
        <button type="button" class="button" onclick="addNonSetupFileField()">+ Datei-Upload hinzufügen</button>
    </div>

    <div class="form-field form-field-wide">
        <h4>Hinweis / Info</h4>
        <label for="order_non_setup_info">Info</label>
        <textarea
            name="order_non_setup_info"
            id="order_non_setup_info"
            rows="5"
            style="width:100%;margin-top:6px;"
            placeholder="Optionaler Hinweis zur Nicht-Aufstellung …"
        ><?php echo esc_textarea($info); ?></textarea>
    </div>

    <script>
        function addNonSetupFileField()
        {
            var container = document.getElementById('order_non_setup_files_repeater');
            var index     = container.children.length;
            var div       = document.createElement('div');
            div.className = 'non-setup-file';
            div.innerHTML =
                '<label for="order_non_setup_files_' + index + '">Datei hochladen</label>' +
                '<input type="text" class="non-setup-file-url" name="order_non_setup_files[' + index + ']" id="order_non_setup_files_' + index + '" placeholder="Datei-URL" />' +
                '<button type="button" class="button upload-non-setup-file-button" onclick="uploadNonSetupFile(this)">Datei auswählen</button>' +
                '<button type="button" class="button btn-danger" onclick="removeNonSetupField(this)">Entfernen</button>';
            container.appendChild(div);
        }

        function removeNonSetupField(button)
        {
            var div = button.parentNode;
            div.parentNode.removeChild(div);
        }

        function uploadNonSetupFile(button)
        {
            var fileInput = button.previousElementSibling;
            wp.media({
                title: 'Datei auswählen',
                button: { text: 'Datei verwenden' },
                multiple: false
            }).on('select', function () {
                var attachment = this.state().get('selection').first().toJSON();
                fileInput.value = attachment.url;
            }.bind(wp.media())).open();

            // Korrekte Referenz über Closure
            var uploader = wp.media({
                title: 'Datei auswählen',
                button: { text: 'Datei verwenden' },
                multiple: false
            }).on('select', function () {
                var attachment = uploader.state().get('selection').first().toJSON();
                fileInput.value = attachment.url;
            }).open();
        }
    </script>

    <style>
        #order_non_setup_files_repeater .non-setup-file {
            margin-bottom: 16px;
            padding: 15px;
            border: 1px solid #ccc;
            background: #f9f9f9;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
        }

        #order_non_setup_files_repeater .non-setup-file label {
            width: 100%;
            font-weight: 600;
            margin-bottom: 4px;
        }

        #order_non_setup_files_repeater .non-setup-file input[type="text"] {
            flex: 1;
            min-width: 200px;
        }
    </style>
    <?php
}

// Schritt 3: Felder speichern.
add_action('woocommerce_process_shop_order_meta', 'save_custom_non_setup_fields');
function save_custom_non_setup_fields($order_id)
{
    // Dateien speichern
    if (isset($_POST['order_non_setup_files'])) {
        $files = array_map('sanitize_text_field', $_POST['order_non_setup_files']);
        $files = array_filter($files); // Leere Einträge entfernen

        if (!empty($files)) {
            update_post_meta($order_id, '_order_non_setup_files', array_values($files));
        } else {
            delete_post_meta($order_id, '_order_non_setup_files');
        }
    }

    // Info speichern
    if (isset($_POST['order_non_setup_info'])) {
        $info = sanitize_textarea_field($_POST['order_non_setup_info']);

        if (!empty($info)) {
            update_post_meta($order_id, '_order_non_setup_info', $info);
        } else {
            delete_post_meta($order_id, '_order_non_setup_info');
        }
    }
}