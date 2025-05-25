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

						<!-- Rechnungsnummer -->
						<?php if ($order->getMetaValue("document_number") || $order->getOrder()->get_meta('_wcpdf_invoice_number')): ?>
							<tr class="invoice-number">
								<th>Rechnungsnummer:</th>
								<td>
									<?= $order->getMetaValue("document_number") ?: $order->getOrder()->get_meta('_wcpdf_invoice_number'); ?>
								</td>
							</tr>
						<?php endif; ?>

						<!-- Erstellungsdatum -->
						<?php if($order->getMetaValue("document_created")): ?>
							<tr class="invoice-date"> 
								<th>Erstellungsdatum:</th>
								<td><?= Utils\DateUtils::formatToGermanDate($order->getMetaValue("document_created")); ?></td>
							</tr>
						<?php endif; ?>

						<!-- Bestellnummer -->
						<?php if($order->getMetaValue("order_number") || $order->getOrder()->get_order_number()): ?>
							<tr class="invoice-number">
								<th>Bestellnummer:</th>
								<td><?= $order->getMetaValue("order_number") ?: $order->getOrder()->get_order_number() ?></td>
							</tr>
						<?php endif; ?>>

						<!-- Zahlungsart -->
						<?php if($order->getOrder()->get_payment_method_title()): ?>
							<tr class="invoice-date"> 
								<th>Zahlungsart:</th>
								<td><?= $order->getOrder()->get_payment_method_title(); ?></td>
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

	<!-- ORDER DETAILS
	--------------------------------->
	<?php include __DIR__ . "/includes/order-details.php"; ?>

	<!-- FOOTER
	--------------------------------->
	<?php include __DIR__ . "/includes/footer.php"; ?>