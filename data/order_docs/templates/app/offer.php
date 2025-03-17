	<?php require_once __DIR__ . "/includes/head.php"; ?>

	<h1 class="document-type-label">Angebot</h1>

	<!-- HEAD
	--------------------------------->
	<table class="order-data-addresses">
		<tr>
			<?php require_once __DIR__ . "/includes/billing-address.php"; ?>
			<td class="order-data">
				<table>
					<?php if($template->getMetaValue("document_number", $data["meta_data"])): ?>
						<tr class="invoice-number">
							<th>Angebotssnummer:</th>
							<td><?= $template->getMetaValue("document_number", $data["meta_data"]); ?></td>
						</tr>
					<?php endif; ?>

					<?php if($template->getMetaValue("document_created", $data["meta_data"])): ?>
						<tr class="invoice-date">
							<th>Erstellungsdatum:</th>
							<td><?= Utils\DateUtils::formatToGermanDate($template->getMetaValue("document_created", $data["meta_data"])); ?></td>
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

	<?php require_once __DIR__ . "/includes/order-details.php"; ?>

    Dieses Angebot wurde maschinell erstellt und ist ohne Unterschrift gültig. Änderungen und Irrtümer vorbehalten.

	<?php require_once __DIR__ . "/includes/footer.php"; ?>