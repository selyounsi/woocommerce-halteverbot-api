<?php

namespace Utils;

class WPCAMetaHandler
{
    private $fields;

    /**
     * Constructor accepts an object containing fields.
     *
     * @param object $fieldsObject The object containing field data.
     */
    public function __construct(object $fieldsObject)
    {
        $this->fields = $fieldsObject;
    }

    /**
     * Get the value of a field by its label.
     *
     * @param string $label The label to search for.
     * @return mixed|null The value of the field or null if not found.
     */
    public function getValueByLabel(string $label)
    {
        foreach ($this->fields as $sectionKey => $section) {
            if (isset($section->fields) && is_array($section->fields)) {
                foreach ($section->fields as $fieldGroup) {
                    foreach ($fieldGroup as $field) {
                        if (isset($field->label) && trim($field->label) === trim($label)) {
                            return $field->value ?? null;
                        }
                    }
                }
            }
        }
        return null;
    }

    /**
     * Set the value of a field by its label.
     *
     * @param string $label The label to search for.
     * @param mixed $newValue The new value to set.
     * @return bool True if the value was updated, false otherwise.
     */
    public function setValueByLabel(string $label, $newValue): bool
    {
        foreach ($this->fields as $sectionKey => $section) {
            if (isset($section->fields) && is_array($section->fields)) {
                foreach ($section->fields as $fieldGroup) {
                    foreach ($fieldGroup as $field) {
                        if (isset($field->label) && trim($field->label) === trim($label)) {
                            $field->value = $newValue;
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }


    public function addValueByLabel(string $label, $data = []): bool
    {
        foreach ($this->fields as $sectionKey => $section) {
            if (isset($section->fields) && is_array($section->fields)) {
                foreach ($section->fields as $fieldGroup) {
                    foreach ($fieldGroup as $field) {
                        if (isset($field->label) && trim($field->label) === trim($label) && $data) 
                        {
                            if (isset($data['key']) && isset($data['value'])) 
                            {
                                // Prüfen, ob der Typ "select" ist
                                if ($field->type === 'select') {
                                    if ($data['key'] === 'value') {
                                        // Value im gewünschten Format speichern
                                        $field->value = [
                                            [
                                                "i" => 0,
                                                "value" => "Option",
                                                "label" => $data['value'],
                                                "price" => 0,
                                                "weight" => 0
                                            ]
                                        ];
                                    } elseif ($data['key'] === 'price') {
                                        // Preis für jede Option im "value"-Array aktualisieren
                                        if (isset($field->value) && is_array($field->value)) {
                                            foreach ($field->value as &$option) {
                                                if (isset($option['price'])) {
                                                    $option['price'] = $data['value'];
                                                }
                                            }
                                        }
                                    }
                                } else {
                                    // Andernfalls normalen Key setzen
                                    $field->{$data['key']} = $data['value'];
                                }
                                return true;
                            }
                        }
                    }
                }
            }
        }
        return false;
    }
    
    /**
     * Get all fields.
     *
     * @param bool $asArray Optional. If true, returns fields as an array. Default is false (returns object).
     * @return object|array The fields as an object or array.
     */
    public function getFields(bool $asArray = false)
    {
        return $asArray ? json_decode(json_encode($this->fields), true) : $this->fields;
    }

    /**
     * Removes specified keys from all fields.
     *
     * @param array $keysToRemove Array of keys to remove from each field.
     * @return void
     */
    public function removeKeysFromFields(array $keysToRemove): void
    {
        foreach ($this->fields as $sectionKey => $section) {
            if (isset($section->fields) && is_array($section->fields)) {
                foreach ($section->fields as $fieldGroup) {
                    foreach ($fieldGroup as $field) {
                        foreach ($keysToRemove as $key) {
                            if (isset($field->$key)) {
                                unset($field->$key);
                            }
                        }
                    }
                }
            }
        }
    }
}
