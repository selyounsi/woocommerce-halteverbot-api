<?php
    use Utils\CurrencyFormatter;
    use Utils\DateUtils;

    $positions	= $order->getMetaValue("wpo_wcpdf_invoice_positions");
?>

<ul>
    <?php if($order->getMetaValue("order_time_type") === "range" || !$order->getMetaValue("order_time_type")): ?>
        <?php if(is_string($order->getLineItemMeta('Startdatum')) && is_string($order->getLineItemMeta('Enddatum'))): ?>
        <li>
            - Ausführungszeitraum: <?= DateUtils::formatToGermanDate($order->getLineItemMeta('Startdatum')); ?> bis <?= DateUtils::formatToGermanDate($order->getLineItemMeta('Enddatum')); ?> (<?= $order->getLineItemMeta('Anzahl der Tage') ?> Tag/e)
        </li>
        <?php endif; ?>
    <?php else: ?>
        <li>
        - Ausführungszeitraum: <?= $order->getMetaValue("order_time_duration") ?> <?= DateUtils::checkTimeUnit($order->getMetaValue("order_time_type")); ?> 
        </li>
    <?php endif; ?>
    <li>- Ausführungsort: <?= $order->getLineItemMeta("Straße + Hausnummer") ?>, <?= $order->getLineItemMeta("Postleitzahl") ?> <?= $order->getLineItemMeta("Ort") ?></li>
    <li>- Grund: <?= $order->getLineItemMeta("Grund"); ?> (<?= $order->getLineItemMeta("Strecke"); ?>)</li>
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
        <?php if(is_array($positions)): ?>
            <?php foreach($positions as $position): ?>
                <tr>
                    <td class="product">
                        <?= $position["description"]; ?>
                    </td>
                    <td class="quantity"><?= $position["quantity"]; ?></td>
                    <td class="price align-right"><?= CurrencyFormatter::formatEuro($position["netto"] ?? $position["total"]); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        <tr class="spacer-row">
            <td colspan="3" style="height: 20px; border: none;"></td>
        </tr>
    </tbody>
    <tfoot>
        <tr class="no-borders">
            <td class="no-borders">
                <?php if($order->getDocumentNote()): ?>
                    <div class="document-notes">
                        <h3><?php _e( 'Anmerkungen', 'woocommerce-pdf-invoices-packing-slips' ); ?></h3>
                        <?= $order->getDocumentNote(); ?>
                    </div>
                <?php endif; ?>

                <?php if($order->getCustomerNote()): ?>
                    <div class="customer-notes">
                        <h3><?php _e( 'Anmerkung zur Bestellung', 'woocommerce-pdf-invoices-packing-slips' ); ?></h3>
                        <?= $order->getCustomerNote(); ?>
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
                                    <?= CurrencyFormatter::formatEuro($order->getInvoiceData("net_total")); ?>
                                </span>
                            </td>
                        </tr>

                        <?php if($order->getInvoiceData("discount_amount")): ?>
                        <tr class="invoice">
                            <th class="description">Rabatt (<?= $order->getInvoiceData("discount_percentage"); ?>%)</th>
                            <td class="price align-right"><span class="totals-price">-<?= CurrencyFormatter::formatEuro($order->getInvoiceData("discount_amount")); ?></span></td>
                        </tr>
                        <?php endif; ?>

                        <?php if($order->getInvoiceData("discount_amount")): ?>
                        <tr class="invoice">
                            <th class="description">Netto nach Rabatt</th>
                            <td class="price align-right"><span class="totals-price"><?= CurrencyFormatter::formatEuro($order->getInvoiceData("net_after_discount")); ?></span></td>
                        </tr>
                        <?php endif; ?>

                        <tr class="invoice">
                            <th class="description">MwSt (<?= $order->getInvoiceData("vat_percentage"); ?>%)</th>
                            <td class="price align-right"><span class="totals-price"><?= CurrencyFormatter::formatEuro($order->getInvoiceData("vat_amount")); ?></span></td>
                        </tr>
                        <tr class="invoice">
                            <th class="description">Gesamtbrutto</th>
                            <td class="price align-right"><span class="totals-price"><?= CurrencyFormatter::formatEuro($order->getInvoiceData("total_amount")); ?></span></td>
                        </tr>
                    </tfoot>
                </table>
            </td>
        </tr>
    </tfoot>
</table>