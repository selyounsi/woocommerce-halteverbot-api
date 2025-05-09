<?php
namespace Utils\PDF\Invoice;

use Exception;
use Utils\DateUtils;

class Positions
{
    static $type = "invoice";

    /**
     * Modifies all invoices by adding position details to the order.
     *
     * This function hooks into the WooCommerce PDF invoice generation process and 
     * appends custom position details (such as description, days, quantity, and price) 
     * before the order details section. It also processes item metadata to format 
     * specific values, such as dates and boolean fields.
     *
     * @return void
     */
    static function setPositions() 
    {
        add_action('wpo_wcpdf_before_order_details', function($document_type, $order) 
        {
            if ($document_type == static::$type) 
            {
                foreach ($order->get_items() as $item_id => $item) {

                    $meta_data = $item->get_meta_data();
        
                    foreach ($meta_data as $meta) 
                    {
                        if ($meta->key == 'Startdatum') {
                            $meta->value = DateUtils::formatToGermanDate($meta->value); 
                        }
        
                        if ($meta->key == 'Enddatum') {
                            $meta->value = DateUtils::formatToGermanDate($meta->value); 
                        }
        
                        if ($meta->key == 'Inklusive behördlicher Genehmigung') {
                            if($meta->value) {
                                $meta->value = "Ja"; 
                            }
                        }
        
                        if ($meta->key == 'Gegenüberliegende Straßenseite sperren') {
                            if($meta->value) {
                                $meta->value = "Ja"; 
                            }
                        }
                    }
                }
        
                if ($order->meta_exists('wpo_wcpdf_invoice_positions')) {
                    $invoice_positions = $order->get_meta('wpo_wcpdf_invoice_positions');
                    if (!empty($invoice_positions)) {
                        echo '<table class="order-details" style="margin-bottom: 20px;">';
                        echo '<thead>';
                        echo '<tr>';
                        echo '<th>#</th>';
                        echo '<th>Beschreibung</th>';
                        echo '<th>Tage</th>';
                        echo '<th>Anzahl</th>';
                        echo '<th style="text-align:right">Preis</th>';
                        echo '</tr>';
                        echo '</thead>';
                        echo '<tbody>';
                        foreach ($invoice_positions as $index => $position) {
                            // Verwende 'name', wenn 'description' leer ist
                            $description = !empty($position['description']) ? $position['description'] : $position['name'];
        
                            echo '<tr>';
                            echo '<td>' . $index + 1 . '</td>';
                            echo '<td>' . esc_html($description) . '</td>';
                            echo '<td>' . esc_html($position['days']) . '</td>';
                            echo '<td>' . esc_html($position['quantity']) . '</td>';
                            echo '<td style="text-align:right">' . esc_html(number_format($position['total'], 2, ',', '.')) . ' €</td>';
                            echo '</tr>';
                        }
                        echo '</tbody>';
                        echo '</table>';
                    }
                }
            }
        }, 10, 2);
    }
}