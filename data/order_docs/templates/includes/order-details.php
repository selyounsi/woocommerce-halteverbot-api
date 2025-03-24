<?php
    use Utils\CurrencyFormatter;
    use Utils\DateUtils;

    $positions	= $this->order->getMetaValue("wpo_wcpdf_invoice_positions");
?>

<ul>
    <?php if($this->order->getMetaValue("order_time_type") === "range" || !$this->order->getMetaValue("order_time_type")): ?>
        <li>
            - Ausführungszeitraum: <?= DateUtils::formatToGermanDate($this->order->getLineItemMeta('Startdatum')); ?> bis <?= DateUtils::formatToGermanDate($this->order->getLineItemMeta('Enddatum')); ?> (<?= $this->order->getLineItemMeta('Anzahl der Tage') ?> Tag/e)
        </li>
    <?php else: ?>
        <li>
        - Ausführungszeitraum: <?= $this->order->getMetaValue("order_time_duration") ?> <?= DateUtils::checkTimeUnit($this->order->getMetaValue("order_time_type")); ?> 
        </li>
    <?php endif; ?>
    <li>- Ausführungsort: <?= $this->order->getLineItemMeta("Straße + Hausnummer") ?>, <?= $this->order->getLineItemMeta("Postleitzahl") ?> <?= $this->order->getLineItemMeta("Ort") ?></li>
    <li>- Grund: Beantragung und Aufstellung von Halteverbotsschildern für <?= $this->order->getLineItemMeta("Grund"); ?> (<?= $this->order->getLineItemMeta("Strecke"); ?>)</li>
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
                <?php if($this->order->getDocumentNote()): ?>
                    <div class="document-notes">
                        <h3><?php _e( 'Anmerkungen', 'woocommerce-pdf-invoices-packing-slips' ); ?></h3>
                        <?= $this->order->getDocumentNote(); ?>
                    </div>
                <?php endif; ?>

                <?php if($this->order->getCustomerNote()): ?>
                    <div class="customer-notes">
                        <h3><?php _e( 'Anmerkung zur Bestellung', 'woocommerce-pdf-invoices-packing-slips' ); ?></h3>
                        <?= $this->order->getCustomerNote(); ?>
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
                                    <?php if($this->order->getMetaValue("net_total")): ?>
                                        <?= CurrencyFormatter::formatEuro($this->order->getMetaValue("net_total")); ?>
                                    <?php else: ?>
                                        <?= CurrencyFormatter::formatEuro($data['line_items'][0]['total']); ?>
                                    <?php endif; ?>
                                </span>
                            </td>
                        </tr>

                        <?php if($this->order->getMetaValue("discount_amount")): ?>
                        <tr class="invoice">
                            <th class="description">Rabatt (<?= $this->order->getMetaValue("discount_percentage"); ?>%)</th>
                            <td class="price align-right"><span class="totals-price">-<?= CurrencyFormatter::formatEuro($this->order->getMetaValue("discount_amount")); ?></span></td>
                        </tr>
                        <?php endif; ?>

                        <?php if($this->order->getMetaValue("discount_amount")): ?>
                        <tr class="invoice">
                            <th class="description">Netto nach Rabatt</th>
                            <td class="price align-right"><span class="totals-price">-<?= CurrencyFormatter::formatEuro($this->order->getMetaValue("net_after_discount")); ?></span></td>
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