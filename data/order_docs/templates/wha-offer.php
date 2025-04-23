<?php include __DIR__ . "/includes/head.php";  ?>

<h1 class="document-type-label">Angebot</h1>

<!-- HEAD
--------------------------------->
<table class="order-data-addresses">
	<tr>
		<?php include __DIR__ . "/includes/billing-address.php"; ?>
		<td class="order-data">
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
		</td>
	</tr>
</table>

<p>Sehr geehrte Damen und Herren,</p>
<p>wir bedanken uns für Ihre Anfrage und bieten Ihnen wie folgt auf Grundlage unserer Allgemeinen Mietbedingungen
freibleibend an:</p>
<br>

<?php include __DIR__ . "/includes/order-details.php"; ?>

Dieses Angebot wurde maschinell erstellt und ist ohne Unterschrift gültig. Änderungen und Irrtümer vorbehalten.

<?php include __DIR__ . "/includes/footer.php"; ?>