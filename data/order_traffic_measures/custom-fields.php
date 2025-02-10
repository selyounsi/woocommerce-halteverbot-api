<?php

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

// Schritt 1: Hinzufügen der Metabox zur Bestellung.
add_action('add_meta_boxes', 'admin_order_traffic_measures_metabox');
function admin_order_traffic_measures_metabox() {
    $screen = class_exists('\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController') && wc_get_container()->get(CustomOrdersTableController::class)->custom_orders_table_usage_is_enabled()
        ? wc_get_page_screen_id('shop-order')
        : 'shop_order';

    add_meta_box(
        'traffic_measures_box',
        __('Verkehrssicherungsmaßnahmen', WHA_TRANSLATION_KEY),
        'add_custom_order_fields',
        $screen,
        'advanced',
        'high'
    );
}

// Schritt 2: Hinzufügen der benutzerdefinierten Felder.
function add_custom_order_fields($order)
{
    $traffic_measures_files = get_post_meta($order->get_id(), '_traffic_measures_files', true);
    $measures = get_post_meta($order->get_id(), '_traffic_measures', true);

    if (!is_array($traffic_measures_files)) {
        $traffic_measures_files = [];
    }

    if (!is_array($measures)) {
        $measures = [];
    }

    ?>
    <div class="form-field form-field-wide">
        <div id="traffic_measures_repeater">
            <?php
            if (!empty($measures)) {
                foreach ($measures as $index => $measure) {
                    $main_measure = isset($measure['main']) ? esc_attr($measure['main']) : '';
                    $main_count = isset($measure['count']) ? esc_attr($measure['count']) : 0;
                    echo '<div class="measure">';
                    echo '<div class="field-box-container">';
                        echo '<div class="field-box"><label for="traffic_measures_' . $index . '_main">Maßnahme</label>';
                        echo '<input type="text" name="traffic_measures[' . $index . '][main]" id="traffic_measures_' . $index . '_main" value="' . $main_measure . '" placeholder="Hauptmaßnahme" /></div>';
                        echo '<div class="field-box"><label for="traffic_measures_' . $index . '_count">Anzahl</label>';
                        echo '<input type="number" name="traffic_measures[' . $index . '][count]" id="traffic_measures_' . $index . '_count" value="' . $main_count . '" placeholder="Anzahl" min="0" /></div>';
                    echo '</div>';

                    echo '<div class="sub-measures">';
                    if (!empty($measure['sub_measures']) && is_array($measure['sub_measures'])) {
                        foreach ($measure['sub_measures'] as $sub_index => $sub_measure) {
                            echo '<div class="sub-measure">';
                            echo '<div class="field-box"><label for="traffic_measures_' . $index . '_sub_' . $sub_index . '_measure">Zusatzmaßnahme</label>';
                            echo '<input type="text" name="traffic_measures[' . $index . '][sub_measures][' . $sub_index . '][measure]" id="traffic_measures_' . $index . '_sub_' . $sub_index . '_measure" value="' . esc_attr($sub_measure['measure']) . '" placeholder="Zusatzmaßnahme" /></div>';
                            echo '<div class="field-box"><label for="traffic_measures_' . $index . '_sub_' . $sub_index . '_count">Anzahl</label>';
                            echo '<input type="number" name="traffic_measures[' . $index . '][sub_measures][' . $sub_index . '][count]" id="traffic_measures_' . $index . '_sub_' . $sub_index . '_count" value="' . esc_attr($sub_measure['count']) . '" placeholder="Anzahl" min="0" /></div>';
                            echo '<button type="button" class="button remove-sub-measure" onclick="removeSubMeasureField(this)">Entfernen</button>';
                            echo '</div>';
                        }
                    }
                    echo '</div>';
                    echo '<button type="button" class="button add-sub-measure" onclick="addSubMeasureField(this)">+ Zusatzmaßnahme hinzufügen</button>';
                    echo '<button type="button" class="button btn-danger remove-measure" onclick="removeMeasureField(this)">Maßnahme entfernen</button>';
                    echo '</div>';
                }
            } else {
                // Zeige eine leere Eingabemaske für eine neue Maßnahme.
                echo '<div class="measure">';
                    echo '<div class="field-box-container">';
                        echo '<div class="field-box"><label for="traffic_measures_0_main">Maßnahme</label>';
                        echo '<input type="text" name="traffic_measures[0][main]" id="traffic_measures_0_main" value="" placeholder="Hauptmaßnahme" /></div>';
                        echo '<div class="field-box"><label for="traffic_measures_0_count">Anzahl</label>';
                        echo '<input type="number" name="traffic_measures[0][count]" id="traffic_measures_0_count" value="0" placeholder="Anzahl" min="0" /></div>';
                    echo '</div>';
                    echo '<div class="sub-measures"></div>';
                    echo '<button type="button" class="button add-sub-measure" onclick="addSubMeasureField(this)">+ Zusatzmaßnahme hinzufügen</button>';
                    echo '<button type="button" class="button btn-danger remove-measure" onclick="removeMeasureField(this)">Maßnahme entfernen</button>';
                echo '</div>';
            }
            ?>
        </div>
        <button type="button" class="button" onclick="addMeasureField()">+ Maßnahme hinzufügen</button>
    </div>
    <div class="form-field form-field-wide">
        <h4>Datei-Uploads</h4>
        <div id="traffic_measures_files_repeater">
            <?php
            if (!empty($traffic_measures_files)) {
                foreach ($traffic_measures_files as $index => $file_url) {
                    $file_url = esc_url($file_url);
                    $preview_link = !empty($file_url) ? '<a href="' . $file_url . '" target="_blank">Vorschau</a>' : '';
                    echo '<div class="file-protocol">';
                    echo '<label for="traffic_measures_files_' . $index . '">Datei hochladen</label>';
                    echo $preview_link ? ' | ' . $preview_link : '';
                    echo '<input type="text" class="file-protocol-url" name="traffic_measures_files[' . $index . ']" id="traffic_measures_files_' . $index . '" value="' . $file_url . '" placeholder="Datei-URL" />';
                    echo '<button type="button" class="button upload-file-button" onclick="uploadMeasuresFile(this)">Datei auswählen</button>';
                    echo '<button type="button" class="button btn-danger remove-file-protocol" onclick="removeMeasureField(this)">Entfernen</button>';
                    echo '</div>';
                }
            } else {
                echo '<div class="file-protocol">';
                echo '<label for="traffic_measures_files_0">Datei hochladen</label>';
                echo '<input type="text" class="file-protocol-url" name="traffic_measures_files[0]" id="traffic_measures_files_0" value="" placeholder="Datei-URL" />';
                echo '<button type="button" class="button upload-file-button" onclick="uploadMeasuresFile(this)">Datei auswählen</button>';
                echo '<button type="button" class="button btn-danger remove-file-protocol" onclick="removeMeasureField(this)">Entfernen</button>';
                echo '</div>';
            }
            ?>
        </div>
        <button type="button" class="button" onclick="addFileMeasureField()">+ Datei-Upload hinzufügen</button>
    </div>



    <script>
        function addMeasureField() {
            var container = document.getElementById('traffic_measures_repeater');
            var index = container.children.length;
            var measureDiv = document.createElement('div');
            measureDiv.className = 'measure';
            measureDiv.innerHTML = '<div class="field-box"><label for="traffic_measures_' + index + '_main">Maßnahme</label>' +
                '<input type="text" name="traffic_measures[' + index + '][main]" id="traffic_measures_' + index + '_main" placeholder="Hauptmaßnahme" /></div>' +
                '<div class="field-box"><label for="traffic_measures_' + index + '_count">Anzahl</label>' +
                '<input type="number" name="traffic_measures[' + index + '][count]" id="traffic_measures_' + index + '_count" value="0" placeholder="Anzahl" min="0" /></div>' +
                '<div class="sub-measures"></div>' +
                '<button type="button" class="button add-sub-measure" onclick="addSubMeasureField(this)">+ Zusatzmaßnahme hinzufügen</button>' +
                '<button type="button" class="button btn-danger remove-measure" onclick="removeMeasureField(this)">Maßnahme entfernen</button>';
            container.appendChild(measureDiv);
        }

        function addSubMeasureField(button) {
            var subMeasuresContainer = button.previousElementSibling;
            var subIndex = subMeasuresContainer.children.length;
            var measureIndex = Array.from(subMeasuresContainer.parentNode.parentNode.children).indexOf(subMeasuresContainer.parentNode);
            var subMeasureDiv = document.createElement('div');
            subMeasureDiv.className = 'sub-measure';
            subMeasureDiv.innerHTML = '<div class="field-box"><label for="traffic_measures_' + measureIndex + '_sub_' + subIndex + '_measure">Zusatzmaßnahme</label>' +
                '<input type="text" name="traffic_measures[' + measureIndex + '][sub_measures][' + subIndex + '][measure]" id="traffic_measures_' + measureIndex + '_sub_' + subIndex + '_measure" placeholder="Zusatzmaßnahme" /></div>' +
                '<div class="field-box"><label for="traffic_measures_' + measureIndex + '_sub_' + subIndex + '_count">Anzahl</label>' +
                '<input type="number" name="traffic_measures[' + measureIndex + '][sub_measures][' + subIndex + '][count]" id="traffic_measures_' + measureIndex + '_sub_' + subIndex + '_count" value="0" placeholder="Anzahl" min="0" /></div>' +
                '<button type="button" class="button remove-sub-measure" onclick="removeSubMeasureField(this)">Entfernen</button>';
            subMeasuresContainer.appendChild(subMeasureDiv);
        }

        function removeMeasureField(button) {
            var measureDiv = button.parentNode;
            measureDiv.parentNode.removeChild(measureDiv);
        }

        function removeSubMeasureField(button) {
            var subMeasureDiv = button.parentNode;
            subMeasureDiv.parentNode.removeChild(subMeasureDiv);
        }

        function addFileMeasureField() {
            var container = document.getElementById('traffic_measures_files_repeater');
            var index = container.children.length;
            var protocolDiv = document.createElement('div');
            protocolDiv.className = 'file-protocol';
            protocolDiv.innerHTML = '<label for="traffic_measures_files_' + index + '">Datei hochladen</label>' +
                '<input type="text" class="file-protocol-url" name="traffic_measures_files[' + index + ']" id="traffic_measures_files_' + index + '" placeholder="Datei-URL" />' +
                '<button type="button" class="button upload-file-button" onclick="uploadMeasuresFile(this)">Datei auswählen</button>' +
                '<button type="button" class="button btn-danger remove-file-protocol" onclick="removeSubMeasureField(this)">Entfernen</button>';
            container.appendChild(protocolDiv);
        }

        function uploadMeasuresFile(button) {
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

        #traffic_measures_files_repeater .file-protocol {
            margin-bottom: 16px;
            padding: 15px;
            border: 1px solid #ccc;
            background: #f9f9f9;
        }

        #traffic_measures_repeater {
            margin-bottom: 20px;
        }

        #traffic_measures_repeater .field-box-container {
            display: flex;
        }
        #traffic_measures_repeater .field-box {
            width: 50%;
        }

        #traffic_measures_repeater > .measure {
            margin-top: 15px;
            padding: 30px 10px;
            border: 1px solid #ccc;
            background: #f9f9f9;
            position: relative;
        }
        #traffic_measures_repeater > .measure > .field-box {
            margin-bottom: 15px;
        }
        #traffic_measures_repeater > .measure > input {
            width: 100%;
        }
        #traffic_measures_repeater > .measure .sub-measures {
            margin: 30px 0;
        }
        #traffic_measures_repeater > .measure .sub-measures .sub-measure {
            display: flex;
            flex-wrap: wrap;
        }
        #traffic_measures_repeater > .measure .sub-measures .sub-measure .field-box {
            width: 50%;
        }
        #traffic_measures_repeater > .measure .sub-measures .sub-measure .remove-sub-measure {
            width: 100%;
            margin-top: 6px;
        }
        #traffic_measures_repeater > .measure .sub-measures .sub-measure:not(:last-child) {
            margin-bottom: 12px;
        }
    </style>
    <?php
}

// Schritt 2: Speichern der verschachtelten Daten.
add_action('woocommerce_process_shop_order_meta', 'save_custom_order_fields');
function save_custom_order_fields($order_id) {

    if (isset($_POST['traffic_measures_files'])) {
        $file_protocols = array_map('sanitize_text_field', $_POST['traffic_measures_files']);
        update_post_meta($order_id, '_traffic_measures_files', $file_protocols);
    }

    if (isset($_POST['traffic_measures'])) {
        $measures = array_map(function($measure) {
            $main = sanitize_text_field($measure['main']);
            $count = isset($measure['count']) ? absint($measure['count']) : 0; // Anzahl für Hauptmaßnahme
            $sub_measures = isset($measure['sub_measures']) ? array_map(function($sub_measure) {
                return [
                    'measure' => sanitize_text_field($sub_measure['measure']),
                    'count' => isset($sub_measure['count']) ? absint($sub_measure['count']) : 0 // Anzahl für Zusatzmaßnahme
                ];
            }, $measure['sub_measures']) : [];
            return [
                'main' => $main,
                'count' => $count,
                'sub_measures' => $sub_measures,
            ];
        }, $_POST['traffic_measures']);
        update_post_meta($order_id, '_traffic_measures', $measures);
    }
}

// Schritt 3: Gespeicherte Daten anzeigen.
add_action('woocommerce_order_details_after_order_table', 'display_custom_order_fields');
function display_custom_order_fields($order) {
    $measures = get_post_meta($order->get_id(), '_traffic_measures', true);
    if (!empty($measures)) {
        echo '<h2>' . esc_html__('Verkehrssicherungsmaßnahmen', 'your-textdomain') . '</h2>';
        echo '<ul>';
        foreach ($measures as $measure) {
            echo '<li>' . esc_html($measure['main']) . ' (Anzahl: ' . esc_html($measure['count']) . ')';
            if (!empty($measure['sub_measures'])) {
                echo '<ul>';
                foreach ($measure['sub_measures'] as $sub_measure) {
                    echo '<li>' . esc_html($sub_measure['measure']) . ' (Anzahl: ' . esc_html($sub_measure['count']) . ')</li>';
                }
                echo '</ul>';
            }
            echo '</li>';
        }
        echo '</ul>';
    }
}