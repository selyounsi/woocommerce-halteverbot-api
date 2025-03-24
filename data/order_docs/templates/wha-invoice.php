	<?php require_once __DIR__ . "/includes/head.php"; ?>

	<h1 class="document-type-label"><?php $this->wpo->title(); ?></h1>

	<table class="order-data-addresses">
		<tr>
			<?php require_once __DIR__ . "/includes/billing-address.php"; ?>
			<td class="order-data">
				<table>
					<?php if($this->order->getMetaValue("document_number")): ?>
						<tr class="invoice-number">
							<th>Rechnungsnummer:</th>
							<td><?= $this->order->getMetaValue("document_number"); ?></td>
						</tr>
					<?php endif; ?>

					<?php if($this->order->getMetaValue("document_created")): ?>
						<tr class="invoice-date">
							<th>Erstellungsdatum:</th>
							<td><?= Utils\DateUtils::formatToGermanDate($this->order->getMetaValue("document_created")); ?></td>
						</tr>
					<?php endif; ?>
				</table>
			</td>
		</tr>
	</table>

	<p>Sehr geehrte Damen und Herren,</p>
	<p>vielen Dank für Ihren Auftrag. Wir freuen uns darauf, Ihre Anforderungen zu Ihrer Zufriedenheit umzusetzen.</p>
	<br>

	<?php require_once __DIR__ . "/includes/order-details.php"; ?>

	Sollten Sie die Zahlungsoption "Banküberweisung" gewählt haben, möchten wir Sie bitten, den Gesamtbetrag auf unser Konto zu überweisen. Erst nach Zahlungseingang wird Ihre Halteverbotsbestellung bearbeitet.
	Diese Rechnung wurde maschinell erstellt und ist ohne Unterschrift gültig.

	<?php require_once __DIR__ . "/includes/footer.php"; ?>