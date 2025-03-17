<?php
    use Utils\CurrencyFormatter;
    use Utils\DateUtils;

    $positions	= $template->getMetaValue("wpo_wcpdf_invoice_positions", $data["meta_data"]);
?>

<ul>
    <?php if($template->getMetaValue("order_time_type", $data["meta_data"]) === "range" || !$template->getMetaValue("order_time_type", $data["meta_data"])): ?>
        <li>
            - Ausführungszeitraum: <?= DateUtils::formatToGermanDate($this->getLineItemMeta($data['line_items'], 'Startdatum')); ?> bis <?= DateUtils::formatToGermanDate($this->getLineItemMeta($data['line_items'], 'Enddatum')); ?> (<?= $this->getLineItemMeta($data['line_items'], 'Anzahl der Tage') ?> Tag/e)
        </li>
    <?php else: ?>
        <li>
        - Ausführungszeitraum: <?= $template->getMetaValue("order_time_duration", $data["meta_data"]); ?> <?= DateUtils::checkTimeUnit($template->getMetaValue("order_time_type", $data["meta_data"])); ?> 
        </li>
    <?php endif; ?>
    <li>- Ausführungsort: <?= $this->getLineItemMeta($data['line_items'], 'Straße + Hausnummer') ?>, <?= $this->getLineItemMeta($data['line_items'], 'Postleitzahl') ?> <?= $this->getLineItemMeta($data['line_items'], 'Ort') ?></li>
    <li>- Grund: Beantragung und Aufstellung von Halteverbotsschildern für <?= $this->getLineItemMeta($data['line_items'], 'Grund'); ?> (<?= $this->getLineItemMeta($data['line_items'], 'Strecke'); ?>)</li>
</ul>

<br>

<table class="order-details">
    <thead>
        <tr>
            <th class="product"><?php _e( 'Product', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
            <th class="quantity"><?php _e( 'Quantity', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
            <th class="price align-right"><?php _e( 'Price', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($positions as $position): ?>
            <tr>
                <td class="product">
                    <?= $position["description"]; ?>
                </td>
                <td class="quantity"><?= $position["quantity"]; ?></td>
                <td class="price align-right"><?= CurrencyFormatter::formatEuro($position["netto"]); ?></td>
            </tr>
        <?php endforeach; ?>
        <tr class="spacer-row">
            <td colspan="3" style="height: 20px; border: none;"></td>
        </tr>
    </tbody>
    <tfoot>
        <tr class="no-borders">
            <td class="no-borders">
                <?php if(isset($data["document_note"]) && $data["document_note"] !== ''): ?>
                    <div class="document-notes">
                        <h3><?php _e( 'Anmerkungen', 'woocommerce-pdf-invoices-packing-slips' ); ?></h3>
                        <?= $data["document_note"]; ?>
                    </div>
                <?php endif; ?>

                <?php if(isset($data["customer_note"]) && $data["customer_note"] !== ''): ?>
                    <div class="customer-notes">
                        <h3><?php _e( 'Anmerkung zur Bestellung', 'woocommerce-pdf-invoices-packing-slips' ); ?></h3>
                        <?= $data["customer_note"]; ?>
                    </div>	
                <?php endif; ?>			
            </td>
            <td class="no-borders" colspan="2">
                <table class="totals">
                    <tfoot>
                        <tr class="invoice">
                            <th class="description">Gesamtnetto</th>
                            <td class="price align-right">
                                <span class="totals-price">
                                    <?php if($template->getMetaValue("net_total")): ?>
                                        <?= CurrencyFormatter::formatEuro($template->getMetaValue("net_total")); ?>
                                    <?php else: ?>
                                        <?= CurrencyFormatter::formatEuro($data['line_items'][0]['total']); ?>
                                    <?php endif; ?>
                                </span>
                            </td>
                        </tr>

                        <?php if($template->getMetaValue("discount_amount")): ?>
                        <tr class="invoice">
                            <th class="description">Rabatt (<?= $template->getMetaValue("discount_percentage"); ?>%)</th>
                            <td class="price align-right"><span class="totals-price">-<?= CurrencyFormatter::formatEuro($template->getMetaValue("discount_amount")); ?></span></td>
                        </tr>
                        <?php endif; ?>

                        <?php if($template->getMetaValue("discount_amount")): ?>
                        <tr class="invoice">
                            <th class="description">Netto nach Rabatt</th>
                            <td class="price align-right"><span class="totals-price">-<?= CurrencyFormatter::formatEuro($template->getMetaValue("net_after_discount")); ?></span></td>
                        </tr>
                        <?php endif; ?>

                        <tr class="invoice">
                            <th class="description">MwSt (19%)</th>
                            <td class="price align-right"><span class="totals-price"><?= CurrencyFormatter::formatEuro($data['line_items'][0]['total_tax']); ?></span></td>
                        </tr>
                        <tr class="invoice">
                            <th class="description">Gesamtbrutto</th>
                            <td class="price align-right"><span class="totals-price"><?= CurrencyFormatter::formatEuro($data['total']); ?></span></td>
                        </tr>
                    </tfoot>
                </table>
            </td>
        </tr>
    </tfoot>
</table>