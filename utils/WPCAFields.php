<?php

namespace Utils;

/**
 * Class WPCAFields
 * 
 * Handles extraction and formatting of custom fields from WooCommerce orders.
 */
class WPCAFields
{
    /**
     * @var \WC_Order WooCommerce order object.
     */
    public $order;
    
    /**
     * @var array Extracted meta fields from the order.
     */
    public $metaFields;

    /**
     * Constructor for WPCAFields.
     *
     * @param \WC_Order $order WooCommerce order object.
     */
    public function __construct($order)
    {
        $this->order = $order;
        $this->metaFields = $this->getMetaFields();
    }

    /**
     * Extracts and returns formatted custom fields from the order.
     *
     * @return array Associative array with order details.
     */
    public function getFieldsets()
    {
        $meta_fields = [];

        foreach($this->metaFields as $key => $field) 
        {
            $meta_fields[$key] = [
                "id"            => $this->order->get_id(),
                "number"        => $this->order->get_order_number(),
                "status"        => $this->order->get_status(),
                "startdate"     => $field['Startdatum'] ?? "",
                "starttime"     => $field['Anfangszeit'] ?? "",
                "enddate"       => $field['Enddatum'] ?? "",
                "endtime"       => $field['Endzeit'] ?? "",
                "reason"        => ($field['Grund'] ?? $field['_Grund']) ?? "",
                "distance"      => ($field['Strecke'] ?? $field['_Strecke']) ?? "",
                "days"          => ($field['Anzahl der Tage'] ?? $field['_Anzahl der Tage']) ?? "",
                "address"       => $field['Straße + Hausnummer'] ?? "",
                "postalcode"    => $field['Postleitzahl'] ?? "",
                "place"         => $field['Ort'] ?? "",
                "client_fname"  => trim($this->order->get_billing_first_name()),
                "client_lname"  => trim($this->order->get_billing_last_name())
            ];
        }

        return $meta_fields;
    }


    /**
     * Extracts and returns formatted custom fields from the order.
     *
     * @return array Associative array with order details.
     */
    public function getMetaFieldsets()
    {
        $meta_fields = [];

        foreach ($this->order->get_items() as $item_id => $item) 
        {
            $meta_fields[$item_id] = [
                "id"            => $this->order->get_id(),
                "number"        => $this->order->get_order_number(),
                "status"        => $this->order->get_status(),
                "startdate"     => $item->get_meta('Startdatum') ?? "",
                "starttime"     => $item->get_meta('Anfangszeit') ?? "",
                "enddate"       => $item->get_meta('Enddatum') ?? "",
                "endtime"       => $item->get_meta('Endzeit') ?? "",
                "reason"        => $this->cleanMetaValue("Grund", $item->get_meta('Grund') ?? $item->get_meta('_Grund') ?? ""),
                "distance"      => ($item->get_meta('Strecke') ?? $item->get_meta('_Strecke')) ?? "",
                "distance_unit" => ($this->cleanMetaValue("Strecke", $item->get_meta('Strecke')) ?? $this->cleanMetaValue("Strecke", $item->get_meta('_Strecke'))) ?? "",
                "days"          => ($item->get_meta('Anzahl der Tage') ?? $item->get_meta('_Anzahl der Tage')) ?? "",
                "address"       => $item->get_meta('Straße + Hausnummer') ?? "",
                "postalcode"    => $item->get_meta('Postleitzahl') ?? "",
                "place"         => $item->get_meta('Ort') ?? "",
                "client_fname"  => trim($this->order->get_billing_first_name()),
                "client_lname"  => trim($this->order->get_billing_last_name())
            ];
        }

        return $meta_fields;
    }

    public function countFieldsets()
    {
        return count($this->getFieldsets());
    }

    /**
     * Extracts custom meta fields from the order.
     *
     * @return array Associative array of meta fields.
     */
    public function getMetaFields()
    {
        $meta_fields = []; 
        $count_field = 0;

        // Iterate through order items
        foreach ($this->order->get_items() as $item_id => $item) 
        {
            // Get the custom fields metadata
            $sections = $item->get_meta('_WCPA_order_meta_data', true);

            if (is_array($sections)) { // Ensure $sections is valid
                foreach ($sections as $sectionKey => $section) 
                {
                    $fields = $section['fields'] ?? [];
                    
                    foreach ($fields as $key => $row) 
                    {
                        foreach ($row as $field) 
                        {
                            $label = trim($field['label'] ?? '');
                            $value = $field['value'] ?? '';

                            if ($label && is_array($value)) {
                                // Extract labels if value is an array
                                foreach ($value as $sub_value) {
                                    $meta_fields[$count_field][$label] = $sub_value['label'] ?? '';
                                }
                            } elseif ($label) {
                                // Directly assign value if not an array
                                $meta_fields[$count_field][$label] = $value;
                            }
                        }
                    }

                    $meta_fields[$count_field]["line_item_id"] = $item_id;
                    $count_field++;
                }
            }
        }

        if (empty($meta_fields)) {
            foreach ($this->order->get_items() as $item_id => $item) {
                // Alle Metadaten für die Position abrufen
                foreach ($item->get_meta_data() as $meta) {
                    // Nur Werte mit Inhalt, außer WCPA_ORDER_META_KEY
                    if (!empty($meta->value) && $meta->key !== WCPA_ORDER_META_KEY) {
                        $meta_fields[$count_field][$meta->key] = $meta->value;
                    }
                }

                $meta_fields[$count_field]["line_item_id"] = $item_id;
                $count_field++;
            }
        }

        return $meta_fields;
    }

    /**
     * Deletes a specific line item from the order.
     *
     * @param int $item_id The ID of the line item to delete.
     * @return array Response indicating success or failure.
     */
    public function deleteLineItem($item_id)
    {
        // Check if the item exists in the order
        $items = $this->order->get_items();
        if (!isset($items[$item_id])) {
            return ['success' => false, 'message' => 'Item not found in the order.'];
        }

        // Try to remove the line item
        try {
            $this->order->remove_item($item_id); // Remove the item from the order
            $this->order->save(); // Save changes to the order

            return ['success' => true, 'message' => 'Item successfully deleted.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Liefert alle Werte zu einem Meta-Label aus $this->metaFields.
     * Werte werden vor Rückgabe ggf. bereinigt.
     *
     * @param string $label Das Meta-Feld Label, z.B. 'Strecke' oder 'Grund'
     * @return array Gefundene Werte (können leer sein)
     */
    public function getItemByLabel(string $label): ?string
    {
        foreach ($this->metaFields as $fieldset) {
            if (isset($fieldset[$label])) {
                $value = $fieldset[$label];

                // Wert bereinigen (Regex o.ä.)
                return $this->cleanMetaValue($label, $value);
            }
        }

        // Label nicht gefunden
        return null;
    }

    /**
     * Führt label-spezifische Bereinigungen am Wert durch.
     *
     * @param string $label Meta-Key
     * @param string $value Originalwert
     * @return string Bereinigter Wert
     */
    protected function cleanMetaValue(string $label, string $value): string
    {
        switch ($label) {
            case 'Strecke':
                // Beispiel: extrahiere z.B. '15m' aus '15m ...'
                $value = preg_replace('/^(\d+m).*/', '$1', $value);
                break;

            case 'Grund':
                // Beispiel: entferne Muster wie " | Option - 1"
                $value = preg_replace('/\s*\|\s*Option\s*-\s*\d+/', '', $value);
                break;

            // Hier kannst du weitere Keys mit eigenen Bereinigungen ergänzen

            default:
                // keine Bereinigung
                break;
        }

        return $value;
    }
}