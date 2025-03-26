<?php include __DIR__ . "/includes/head.php";  ?>

<h1 class="document-type-label">Rechnung</h1>
<table class="order-data-addresses">
	<tr>
		<?php include __DIR__ . "/includes/billing-address.php"; ?>
		<td class="order-data">

			<?php if($this instanceof WPO\IPS\Documents\Invoice):  ?>
				<table>
					<?php if ( isset( $this->settings['display_number'] ) ) : ?>
						<tr class="invoice-number">
							<th><?php $this->number_title(); ?></th>
							<td><?php $this->number( $this->get_type() ); ?></td>
						</tr>
					<?php endif; ?>
					<?php if ( isset( $this->settings['display_date'] ) ) : ?>
						<tr class="invoice-date">
							<th><?php $this->date_title(); ?></th>
							<td><?php $this->date( $this->get_type() ); ?></td>
						</tr>
					<?php endif; ?>
					<tr class="order-number">
						<th><?php _e( 'Order Number:', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
						<td><?php $this->order_number(); ?></td>
					</tr>
					<tr class="order-date">
						<th><?php _e( 'Order Date:', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
						<td><?php $this->order_date(); ?></td>
					</tr>
					<?php if ( $this->get_payment_method() ) : ?>
					<tr class="payment-method">
						<th><?php _e( 'Payment Method:', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
						<td><?php $this->payment_method(); ?></td>
					</tr>
					<?php endif; ?>
				</table>	
			<?php else: ?>
				<table>
					<?php if($order->getMetaValue("document_number")): ?>
						<tr class="invoice-number">
							<th>Rechnungsnummer:</th>
							<td><?= $order->getMetaValue("document_number"); ?></td>
						</tr>
					<?php endif; ?>

					<?php if($order->getMetaValue("document_created")): ?>
						<tr class="invoice-date">
							<th>Erstellungsdatum:</th>
							<td><?= Utils\DateUtils::formatToGermanDate($order->getMetaValue("document_created")); ?></td>
						</tr>
					<?php endif; ?>
				</table>
			<?php endif; ?>
		</td>
	</tr>
</table>

<p>Sehr geehrte Damen und Herren,</p>
<p>vielen Dank für Ihren Auftrag. Wir freuen uns darauf, Ihre Anforderungen zu Ihrer Zufriedenheit umzusetzen.</p>
<br>

<?php include __DIR__ . "/includes/order-details.php"; ?>

Sollten Sie die Zahlungsoption "Banküberweisung" gewählt haben, möchten wir Sie bitten, den Gesamtbetrag auf unser Konto zu überweisen. Erst nach Zahlungseingang wird Ihre Halteverbotsbestellung bearbeitet.
Diese Rechnung wurde maschinell erstellt und ist ohne Unterschrift gültig.

<?php include __DIR__ . "/includes/footer.php"; ?>