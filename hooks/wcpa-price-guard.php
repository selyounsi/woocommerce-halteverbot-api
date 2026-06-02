<?php
/**
 * Schutz gegen den WCPA-Fatal-Error  "Unsupported operand types: int * array".
 *
 * Hintergrund:
 *   Woo Custom Product Addons Pro (Acowebs) rechnet beim Formatieren der
 *   Order-Item-Meta  priceMultiplier * price.  Ist ein "price" aus
 *   _WCPA_order_meta_data ausnahmsweise ein ARRAY (statt einer Zahl), bricht
 *   PHP mit einem Fatal Error ab – das legt u. a. die gesamte
 *   WooCommerce-REST-API (GET /wc/v3/orders) lahm.
 *
 * Loesung (update-sicher, im EIGENEN Plugin):
 *   Wir haengen uns mit Prioritaet 9 – also VOR WCPA (Prio 10) – in den Filter
 *   'woocommerce_order_item_display_meta_value' und normalisieren die
 *   WCPA-Metastruktur des Items: jeder "price", der ein Array ist, wird zu
 *   einer Zahl. Das passiert nur im Arbeitsspeicher des aktuellen Requests –
 *   die Datenbank wird NICHT veraendert. Dadurch laeuft die Bereinigung bei
 *   jedem Aufruf automatisch und ist von WCPA-Updates unabhaengig.
 */

defined('ABSPATH') || exit;

/**
 * Liefert den richtigen Meta-Key (WCPA-Konstante, sonst Fallback).
 *
 * @return string
 */
function wha_wcpa_meta_key()
{
    return defined('WCPA_ORDER_META_KEY') ? WCPA_ORDER_META_KEY : '_WCPA_order_meta_data';
}

/**
 * Wandelt einen price-Wert sicher in eine Zahl:
 *  - Array  -> Summe aller numerischen Blaetter
 *  - sonst  -> unveraendert (Zahl bleibt Zahl; '' / null sind fuer WCPA "kein Preis")
 *
 * @param mixed $value
 * @return mixed
 */
function wha_wcpa_normalize_price($value)
{
    if (!is_array($value)) {
        return $value;
    }

    $sum = 0.0;
    array_walk_recursive($value, function ($leaf) use (&$sum) {
        if (is_numeric($leaf)) {
            $sum += (float) $leaf;
        }
    });

    return $sum;
}

/**
 * Geht rekursiv durch die WCPA-Metastruktur und ersetzt jeden "price"-Schluessel,
 * der ein Array ist, durch eine Zahl.
 *
 * @param array $node  (per Referenz)
 * @return bool  true, wenn etwas geaendert wurde
 */
function wha_wcpa_sanitize_meta(array &$node)
{
    $changed = false;

    foreach ($node as $key => &$child) {
        if ($key === 'price' && is_array($child)) {
            $child   = wha_wcpa_normalize_price($child);
            $changed = true;
            continue; // $child ist jetzt skalar, nicht weiter hineinsteigen
        }

        if (is_array($child) && wha_wcpa_sanitize_meta($child)) {
            $changed = true;
        }
    }
    unset($child);

    return $changed;
}

/**
 * Bereinigt EINGEHENDE WCPA-Felder VOR dem Speichern (POST aus der App).
 *
 * Hintergrund:
 *   WCPAs order_meta_plain() ruft fuer Multi-Option-Felder (value = Array, z.B.
 *   select / checkbox-group / radio-group / color-group / productGroup)
 *       array_map($cb, $value, $field['price'])
 *   auf und erwartet $field['price'] dort als ARRAY (parallel zu value) – oder
 *   einen falsy Wert. Ein truthy SKALAR (z. B. 5) fuehrt zu
 *   "array_map(): Argument #3 must be of type array, int given" -> Fatal Error.
 *
 *   Wir verpacken einen solchen Skalar wieder in ein Array (Betrag auf die erste
 *   Option), sodass der Preis erhalten bleibt und nichts crasht. Bereits korrekte
 *   Array- oder falsy-Preise bleiben unveraendert; Felder mit skalarem value
 *   (date, content, einzelne checkbox ...) werden bewusst nicht angefasst.
 *
 * @param mixed $fields  Struktur aus $request['fields']
 * @return mixed
 */
function wha_wcpa_sanitize_fields_for_save($fields)
{
    if (!is_array($fields)) {
        return $fields;
    }

    foreach ($fields as &$section) {
        if (empty($section['fields']) || !is_array($section['fields'])) {
            continue;
        }
        foreach ($section['fields'] as &$row) {
            if (!is_array($row)) {
                continue;
            }
            foreach ($row as &$field) {
                if (!is_array($field) || !array_key_exists('price', $field)) {
                    continue;
                }

                $value = $field['value'] ?? null;
                $price = $field['price'];

                // Nur Multi-Option-Felder (value = Array) mit truthy skalarem Preis.
                if (is_array($value) && !is_array($price) && !empty($price)) {
                    $amount   = is_numeric($price) ? $price + 0 : 0;
                    $count    = max(1, count($value));
                    $priceArr = array_fill(0, $count, 0);
                    $priceArr[0] = $amount; // Betrag auf die erste Option, Rest 0
                    $field['price'] = $priceArr;
                }
            }
            unset($field);
        }
        unset($row);
    }
    unset($section);

    return $fields;
}

/**
 * Bereinigt die WCPA-Metadaten einer Bestellposition im Speicher,
 * BEVOR WCPA (Prio 10) sie zum Rechnen liest.
 */
add_filter('woocommerce_order_item_display_meta_value', function ($display_value, $meta = null, $item = null) {

    if (!($item instanceof WC_Order_Item)) {
        return $display_value;
    }

    // Pro Position nur einmal pro Request bereinigen.
    static $done = [];
    $item_id = $item->get_id();
    if (isset($done[$item_id])) {
        return $display_value;
    }
    $done[$item_id] = true;

    $meta_key = wha_wcpa_meta_key();
    $wcpa     = $item->get_meta($meta_key, true);

    if (is_array($wcpa) && wha_wcpa_sanitize_meta($wcpa)) {
        // Nur im Speicher aktualisieren – kein save(), die DB bleibt unveraendert.
        $item->update_meta_data($meta_key, $wcpa);
    }

    return $display_value;
}, 9, 3);
