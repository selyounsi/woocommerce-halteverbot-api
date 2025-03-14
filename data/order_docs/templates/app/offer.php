<?php

	use Utils\CurrencyFormatter;
	use Utils\DateUtils;

	$settings 	= get_option('wpo_wcpdf_settings_general');
	$positions	= $template->getMetaValue($data["meta_data"], "wpo_wcpdf_invoice_positions");
?>
<!DOCTYPE html>
<html>
	<head>
		<style type="text/css">
			<?php // $this->wpo->template_styles(); ?>
			<?php require(WHA_PLUGIN_PATH . "/data/order_docs/templates/assets/style.css"); ?>
		</style>
	</head>
	<body>

	<!-- LOGO
	--------------------------------->
	<table class="head container">
		<tr>
			<td class="header">
			<?php
				if ( $this->wpo->has_header_logo() ) {
					echo '<img style="height: ' . esc_attr($this->wpo->get_header_logo_height()) . ';" src="' . esc_attr($this->getHeaderLogo()) . '" alt="Shop Logo">';
				} else {
					$this->wpo->title();
				}
			?>
			</td>
			<td class="shop-info">
				<div class="shop-name"><h3><?php echo $this->wpo->shop_name(); ?></h3></div>
				<div class="shop-address"><?php echo $this->wpo->get_shop_address(); ?></div>
			</td>
		</tr>
	</table>

	<?php if ( $this->wpo->has_header_logo() ) : ?>
		<h1 class="document-type-label">Angebot</h1>
	<?php endif; ?>

	<!-- HEAD
	--------------------------------->
	<table class="order-data-addresses">
		<tr>
			<td class="address billing-address">

				<?php if(isset($data["billing"]["company"]) && $data["billing"]["company"] !== ''): ?>
					<div class="billing-company"><?= $data["billing"]["company"]; ?></div>
				<?php endif; ?>

				<?php if(isset($data["billing"]["address_1"]) && $data["billing"]["address_1"] !== ''): ?>
					<div class="billing-address_1"><?= $data["billing"]["address_1"]; ?></div>
				<?php endif; ?>

				<?php if(isset($data["billing"]["postcode"]) && $data["billing"]["postcode"] !== ''): ?>
					<div class="billing-postcode"><?= $data["billing"]["postcode"]; ?> <?= $data["billing"]["city"]; ?></div>
				<?php endif; ?>

				
				<?php if(isset($data["billing"]["emailX"]) && $data["billing"]["emailX"] !== ''): ?>
					<div class="billing-emailX"><?= $data["billing"]["emailX"]; ?></div>
				<?php endif; ?>

				<?php if(isset($data["billing"]["phoneX"]) && $data["billing"]["phoneX"] !== ''): ?>
					<div class="billing-phoneX"><?= $data["billing"]["phoneX"]; ?></div>
				<?php endif; ?>
			</td>
			<td class="order-data">
				<table>
					<?php if($template->getMetaValue($data["meta_data"], "document_number")): ?>
						<tr class="invoice-number">
							<th>Angebotssnummer:</th>
							<td><?= $template->getMetaValue($data["meta_data"], "document_number"); ?></td>
						</tr>
					<?php endif; ?>

					<?php if($template->getMetaValue($data["meta_data"], "document_created")): ?>
						<tr class="invoice-date">
							<th>Erstellungsdatum:</th>
							<td><?= DateUtils::formatToGermanDate($template->getMetaValue($data["meta_data"], "document_created")); ?></td>
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

	<ul>
		<li>
			- Ausführungszeitraum: <?= DateUtils::formatToGermanDate($this->getLineItemMeta($data['line_items'], 'Startdatum')); ?> bis <?= DateUtils::formatToGermanDate($this->getLineItemMeta($data['line_items'], 'Enddatum')); ?> (<?= $this->getLineItemMeta($data['line_items'], 'Anzahl der Tage') ?> Tag/e)
		</li>
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
								<td class="price align-right"><span class="totals-price"><?= CurrencyFormatter::formatEuro($data['line_items'][0]['total']); ?></span></td>
							</tr>
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

    Dieses Angebot wurde maschinell erstellt und ist ohne Unterschrift gültig. Änderungen und Irrtümer vorbehalten.

	<!-- FOOTER
	--------------------------------->
	<div class="bottom-spacer">
    </div>
		<htmlpagefooter name="docFooter">
			<div id="footer">
				<table>
					<tr>
						<td style="padding: 4px 10px;">
							<?php
								echo $this->wpo->get_extra_1();
							?>
						</td>
						<td style="padding: 4px 10px;">
							<?php
								echo $this->wpo->get_extra_2();
							?>
						</td>
						<td style="padding: 4px 10px;">
							<?php
								echo $this->wpo->get_extra_3();
							?>
						</td>
					</tr>
				</table>
			</div>
		</htmlpagefooter>
	</body>
</html>