<?php

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

// Schritt 1: Hinzufügen der Metabox zur Bestellung.
add_action('add_meta_boxes', 'admin_order_protocol_metabox');
function admin_order_protocol_metabox() {
    $screen = class_exists('\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController') && wc_get_container()->get(CustomOrdersTableController::class)->custom_orders_table_usage_is_enabled()
        ? wc_get_page_screen_id('shop-order')
        : 'shop_order';

    add_meta_box(
        'order_protocol_box',
        __('Aufstellprotokoll', 'your-text-domain'),
        'add_custom_protocol_fields',
        $screen,
        'advanced',
        'high'
    );
}

// Schritt 2: Hinzufügen der benutzerdefinierten Felder.
function add_custom_protocol_fields($order) 
{
    $order_id = $order->get_id();
    $manager = new \Utils\OrderProtocolsManager($order_id);
    $protocols = $manager->getProtocols();

    $file_protocols = $protocols["files"];
    $license_protocols = $protocols["licenses"];

    if (!is_array($file_protocols)) {
        $file_protocols = [];
    }
    
    if (!is_array($license_protocols)) {
        $license_protocols = [];
    }
    ?>
    <div class="form-field form-field-wide">
        <h4>Datei-Uploads</h4>
        <div id="order_file_protocols_repeater">
            <?php
            if (!empty($file_protocols)) {
                foreach ($file_protocols as $index => $file_url) {
                    $file_url = esc_url($file_url);
                    $preview_link = !empty($file_url) ? '<a href="' . $file_url . '" target="_blank">Vorschau</a>' : '';
                    echo '<div class="file-protocol">';
                    echo '<label for="order_file_protocols_' . $index . '">Datei hochladen</label>';
                    echo $preview_link ? ' | ' . $preview_link : '';
                    echo '<input type="text" class="file-protocol-url" name="order_file_protocols[' . $index . ']" id="order_file_protocols_' . $index . '" value="' . $file_url . '" placeholder="Datei-URL" />';
                    echo '<button type="button" class="button upload-file-button" onclick="uploadProtocolFile(this)">Datei auswählen</button>';
                    echo '<button type="button" class="button btn-danger remove-file-protocol" onclick="removeProtocolField(this)">Entfernen</button>';
                    echo '</div>';
                }
            } else {
                echo '<div class="file-protocol">';
                echo '<label for="order_file_protocols_0">Datei hochladen</label>';
                echo '<input type="text" class="file-protocol-url" name="order_file_protocols[0]" id="order_file_protocols_0" value="" placeholder="Datei-URL" />';
                echo '<button type="button" class="button upload-file-button" onclick="uploadProtocolFile(this)">Datei auswählen</button>';
                echo '<button type="button" class="button btn-danger remove-file-protocol" onclick="removeProtocolField(this)">Entfernen</button>';
                echo '</div>';
            }
            ?>
        </div>
        <button type="button" class="button" onclick="addFileProtocolField()">+ Datei-Upload hinzufügen</button>
    </div>
    <div class="form-field form-field-wide">
        <h4>Kennzeichen, Fahrzeugtyp und Farbe</h4>
        <div id="order_license_protocols_repeater">
            <?php
            if (!empty($license_protocols)) {
                foreach ($license_protocols as $index => $license_data) {
                    $license_plate = esc_html($license_data['license_plate'] ?? '');
                    $vehicle_type = esc_html($license_data['vehicle_type'] ?? '');
                    $color = esc_html($license_data['color'] ?? '');
                    echo '<div class="license-protocol">';
                        echo '<label for="order_license_protocols_' . $index . '_plate">Kennzeichen';
                        echo '<input type="text" class="license-protocol-input" name="order_license_protocols[' . $index . '][license_plate]" id="order_license_protocols_' . $index . '_plate" value="' . $license_plate . '" placeholder="Kennzeichen" /></label>';
                        echo '<label for="order_license_protocols_' . $index . '_type">Fahrzeugtyp';
                        echo '<input type="text" class="license-protocol-input" name="order_license_protocols[' . $index . '][vehicle_type]" id="order_license_protocols_' . $index . '_type" value="' . $vehicle_type . '" placeholder="Fahrzeugtyp" /></label>';
                        echo '<label for="order_license_protocols_' . $index . '_color">Farbe';
                        echo '<input type="text" class="license-protocol-input" name="order_license_protocols[' . $index . '][color]" id="order_license_protocols_' . $index . '_color" value="' . $color . '" placeholder="Farbe" /></label>';
                        echo '<button type="button" class="button btn-danger remove-license-protocol" onclick="removeProtocolField(this)">Entfernen</button>';
                    echo '</div>';
                }
            } else {
                echo '<div class="license-protocol">';
                    echo '<label for="order_license_protocols_0_plate">Kennzeichen';
                    echo '<input type="text" class="license-protocol-input" name="order_license_protocols[0][license_plate]" id="order_license_protocols_0_plate" value="" placeholder="Kennzeichen" /></label>';
                    echo '<label for="order_license_protocols_0_type">Fahrzeugtyp';
                    echo '<input type="text" class="license-protocol-input" name="order_license_protocols[0][vehicle_type]" id="order_license_protocols_0_type" value="" placeholder="Fahrzeugtyp" /></label>';
                    echo '<label for="order_license_protocols_0_color">Farbe';
                    echo '<input type="text" class="license-protocol-input" name="order_license_protocols[0][color]" id="order_license_protocols_0_color" value="" placeholder="Farbe" /></label>';
                    echo '<button type="button" class="button btn-danger remove-license-protocol" onclick="removeProtocolField(this)">Entfernen</button>';
                echo '</div>';
            }
            ?>
        </div>
        <button type="button" class="button" onclick="addLicenseProtocolField()">+ Fahrzeugdetails hinzufügen</button>
    </div>
    <script>
        function addFileProtocolField() 
        {
            var container = document.getElementById('order_file_protocols_repeater');
            var index = container.children.length;
            var protocolDiv = document.createElement('div');
            protocolDiv.className = 'file-protocol';
            protocolDiv.innerHTML = '<label for="order_file_protocols_' + index + '">Datei hochladen</label>' +
                '<input type="text" class="file-protocol-url" name="order_file_protocols[' + index + ']" id="order_file_protocols_' + index + '" placeholder="Datei-URL" />' +
                '<button type="button" class="button upload-file-button" onclick="uploadProtocolFile(this)">Datei auswählen</button>' +
                '<button type="button" class="button btn-danger remove-file-protocol" onclick="removeProtocolField(this)">Entfernen</button>';
            container.appendChild(protocolDiv);
        }

        function addLicenseProtocolField() 
        {
            var container = document.getElementById('order_license_protocols_repeater');
            var index = container.children.length;
            var protocolDiv = document.createElement('div');
            protocolDiv.className = 'license-protocol';
            protocolDiv.innerHTML = '<label for="order_license_protocols_' + index + '_plate">Kennzeichen' +
                '<input type="text" class="license-protocol-input" name="order_license_protocols[' + index + '][license_plate]" id="order_license_protocols_' + index + '_plate" placeholder="Kennzeichen" /></label>' +
                '<label for="order_license_protocols_' + index + '_type">Fahrzeugtyp' +
                '<input type="text" class="license-protocol-input" name="order_license_protocols[' + index + '][vehicle_type]" id="order_license_protocols_' + index + '_type" placeholder="Fahrzeugtyp" /></label>' +
                '<label for="order_license_protocols_' + index + '_color">Farbe' +
                '<input type="text" class="license-protocol-input" name="order_license_protocols[' + index + '][color]" id="order_license_protocols_' + index + '_color" placeholder="Farbe" /></label>' +
                '<button type="button" class="button btn-danger remove-license-protocol" onclick="removeProtocolField(this)">Entfernen</button>';
            container.appendChild(protocolDiv);
        }

        function removeProtocolField(button) 
        {
            var protocolDiv = button.parentNode;
            protocolDiv.parentNode.removeChild(protocolDiv);
        }

        function uploadProtocolFile(button) 
        {
            var fileInput = button.previousElementSibling;
            var customUploader = wp.media({
                title: 'Datei auswählen',
                button: {
                    text: 'Datei verwenden'
                },
                multiple: false
            }).on('select', function() {
                var attachment = customUploader.state().get('selection').first().toJSON();
                fileInput.value = attachment.url;
            }).open();
        }
    </script>
    <style>
        .form-field-wide {
            margin-bottom: 20px;
        }

        #order_file_protocols_repeater .file-protocol,
        #order_license_protocols_repeater .license-protocol {
            margin-bottom: 16px;
            padding: 15px;
            border: 1px solid #ccc;
            background: #f9f9f9;
        }

        #order_license_protocols_repeater .license-protocol {
            display:flex;
            flex-wrap: wrap;
        }

        #order_license_protocols_repeater .license-protocol input,
        #order_license_protocols_repeater .license-protocol label {
            display: block;
        }

        #order_license_protocols_repeater .license-protocol label {
            width: 33.33%;
        }
        #order_license_protocols_repeater .license-protocol input {
            margin-bottom: 8px;
        }
    </style>
    <?php
}

// Schritt 3: Speichern der benutzerdefinierten Felder.
add_action('woocommerce_process_shop_order_meta', 'save_custom_protocol_fields');
function save_custom_protocol_fields($order_id) 
{
    delete_post_meta($order_id, '_order_license_protocols');
    if (isset($_POST['order_file_protocols'])) {
        if(!empty($_POST['order_file_protocols'])) {
            update_post_meta($order_id, '_order_file_protocols', array_map('sanitize_text_field', $_POST['order_file_protocols']));
        } else {
            delete_post_meta($order_id, '_order_file_protocols');
        }
    }

    if (isset($_POST['order_license_protocols'])) {
        $license_protocols = [];
        foreach ($_POST['order_license_protocols'] as $license_data) {
            $license_protocols[] = [
                'license_plate' => sanitize_text_field($license_data['license_plate'] ?? ''),
                'vehicle_type' => sanitize_text_field($license_data['vehicle_type'] ?? ''),
                'color' => sanitize_text_field($license_data['color'] ?? '')
            ];
        }
        // Speichern als leeres Array, wenn kein Fahrzeug vorhanden ist, oder löschen der Metadaten.
        if (!empty($license_protocols)) {
            update_post_meta($order_id, '_order_license_protocols', $license_protocols);
        } else {
            delete_post_meta($order_id, '_order_license_protocols');
        }
    }
}