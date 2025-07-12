<?php
	use Utils\PDF\Generator;
	use Utils\PDF\Invoice\CustomInvoice;

	$wpo = new CustomInvoice();
	$wpo_pdf_settings = get_option('wpo_wcpdf_settings_general');
?>
<!DOCTYPE html>
<html>
	<head>
		<style type="text/css">
			<?php require(WHA_PLUGIN_PATH . "/data/order_docs/templates/assets/style.css"); ?>
		</style>
	</head>
	<body>
		<?php do_action( 'wpo_wcpdf_before_document', $this->get_type(), $this->order ); ?>
		<table class="head container">
			<tr>
				<td class="header">
					<?php echo $wpo->displayHeaderLogo(); ?>
				</td>
				<td class="shop-info">
					<?php do_action( 'wpo_wcpdf_before_shop_name', $this->get_type(), $this->order ); ?>
					<div class="shop-name"><h3><?php $this->shop_name(); ?></h3></div>
					<?php do_action( 'wpo_wcpdf_after_shop_name', $this->get_type(), $this->order ); ?>
					<?php do_action( 'wpo_wcpdf_before_shop_address', $this->get_type(), $this->order ); ?>
					<div class="shop-address"><?php $this->shop_address(); ?></div>
					<?php do_action( 'wpo_wcpdf_after_shop_address', $this->get_type(), $this->order ); ?>
				</td>
			</tr>
		</table>

		<?php do_action( 'wpo_wcpdf_before_document_label', $this->get_type(), $this->order ); ?>

		<h1 class="document-type-label">
			<?php if ( $this->has_header_logo() ) $this->title(); ?>
		</h1>

		<?php do_action( 'wpo_wcpdf_after_document_label', $this->get_type(), $this->order ); ?>

		<table class="order-data-addresses">
			<tr>
				<td class="address billing-address">
					<!-- <h3><?php _e( 'Billing Address:', 'woocommerce-pdf-invoices-packing-slips' ); ?></h3> -->
					<?php do_action( 'wpo_wcpdf_before_billing_address', $this->get_type(), $this->order ); ?>
					<?php $this->billing_address(); ?>
					<?php do_action( 'wpo_wcpdf_after_billing_address', $this->get_type(), $this->order ); ?>
					<?php if ( isset( $this->settings['display_email'] ) ) : ?>
						<div class="billing-email"><?php $this->billing_email(); ?></div>
					<?php endif; ?>
					<?php if ( isset( $this->settings['display_phone'] ) ) : ?>
						<div class="billing-phone"><?php $this->billing_phone(); ?></div>
					<?php endif; ?>
				</td>
				<td class="address shipping-address">
					<?php if ( $this->show_shipping_address() ) : ?>
						<h3><?php _e( 'Ship To:', 'woocommerce-pdf-invoices-packing-slips' ); ?></h3>
						<?php do_action( 'wpo_wcpdf_before_shipping_address', $this->get_type(), $this->order ); ?>
						<?php $this->shipping_address(); ?>
						<?php do_action( 'wpo_wcpdf_after_shipping_address', $this->get_type(), $this->order ); ?>
						<?php if ( isset( $this->settings['display_phone'] ) ) : ?>
							<div class="shipping-phone"><?php $this->shipping_phone(); ?></div>
						<?php endif; ?>
					<?php endif; ?>
				</td>
				<td class="order-data">
					<table>
						<?php do_action( 'wpo_wcpdf_before_order_data', $this->get_type(), $this->order ); ?>
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
						<?php do_action( 'wpo_wcpdf_after_order_data', $this->get_type(), $this->order ); ?>
					</table>			
				</td>
			</tr>
		</table>

		<?php do_action( 'wpo_wcpdf_before_order_details', $this->get_type(), $this->order ); ?>

		<table class="order-details">
			<thead>
				<tr>
					<th class="product"><?php _e( 'Product', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
					<th class="quantity"><?php _e( 'Quantity', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
					<th class="price"><?php _e( 'Price', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $this->get_order_items() as $item_id => $item ) : ?>
					<tr class="<?php echo apply_filters( 'wpo_wcpdf_item_row_class', 'item-'.$item_id, esc_attr( $this->get_type() ), $this->order, $item_id ); ?>">
						<td class="product">
							<?php $description_label = __( 'Description', 'woocommerce-pdf-invoices-packing-slips' ); // registering alternate label translation ?>
							<span class="item-name"><?php echo $item['name']; ?></span>
							<?php do_action( 'wpo_wcpdf_before_item_meta', $this->get_type(), $item, $this->order  ); ?>
							<span class="item-meta"><?php echo $item['meta']; ?></span>
							<dl class="meta">
								<?php $description_label = __( 'SKU', 'woocommerce-pdf-invoices-packing-slips' ); // registering alternate label translation ?>
								<?php if ( ! empty( $item['sku'] ) ) : ?><dt class="sku"><?php _e( 'SKU:', 'woocommerce-pdf-invoices-packing-slips' ); ?></dt><dd class="sku"><?php echo esc_attr( $item['sku'] ); ?></dd><?php endif; ?>
								<?php if ( ! empty( $item['weight'] ) ) : ?><dt class="weight"><?php _e( 'Weight:', 'woocommerce-pdf-invoices-packing-slips' ); ?></dt><dd class="weight"><?php echo esc_attr( $item['weight'] ); ?><?php echo esc_attr( get_option( 'woocommerce_weight_unit' ) ); ?></dd><?php endif; ?>
							</dl>
							<?php do_action( 'wpo_wcpdf_after_item_meta', $this->get_type(), $item, $this->order  ); ?>
						</td>
						<td class="quantity"><?php echo $item['quantity']; ?></td>
						<td class="price"><?php echo $item['order_price']; ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
			<tfoot>
				<tr class="no-borders">
					<td class="no-borders">
						<div class="document-notes">
							<?php do_action( 'wpo_wcpdf_before_document_notes', $this->get_type(), $this->order ); ?>
							<?php if ( $this->get_document_notes() ) : ?>
								<h3><?php _e( 'Anmerkungen', 'woocommerce-pdf-invoices-packing-slips' ); ?></h3>
								<?php $this->document_notes(); ?>
							<?php endif; ?>
							<?php do_action( 'wpo_wcpdf_after_document_notes', $this->get_type(), $this->order ); ?>
						</div>
						<div class="customer-notes">
							<?php do_action( 'wpo_wcpdf_before_customer_notes', $this->get_type(), $this->order ); ?>
							<?php if ( $this->get_shipping_notes() ) : ?>
								<h3><?php _e( 'Anmerkung zur Bestellung', 'woocommerce-pdf-invoices-packing-slips' ); ?></h3>
								<?php $this->shipping_notes(); ?>
							<?php endif; ?>
							<?php do_action( 'wpo_wcpdf_after_customer_notes', $this->get_type(), $this->order ); ?>
						</div>				
					</td>

					<?php
						// Netto-Gesamtbetrag (Zwischensumme)
						$net_total = wc_price( $this->order->get_subtotal(), ['currency' => $this->order->get_currency()] );

						// Steuern – einzelne Steuersätze anzeigen
						$tax_items = $this->order->get_tax_totals();

						// Gebühren (z. B. PayPal)
						$fees = $this->order->get_fees();

						// Brutto-Endbetrag
						$grand_total = wc_price( $this->order->get_total(), ['currency' => $this->order->get_currency()] );
					?>

					<td class="no-borders" colspan="2">
						<table class="totals">
							<tfoot>
								<tr class="order-subtotal">
									<th class="description">Gesamtnetto</th>
									<td class="price"><span class="totals-price"><?php echo $net_total; ?></span></td>
								</tr>

								<?php foreach ( $fees as $fee ) : ?>
									<tr class="order-fee">
										<th class="description"><?php echo esc_html( $fee->get_name() ); ?></th>
										<td class="price"><span class="totals-price"><?php echo wc_price( $fee->get_total(), ['currency' => $this->order->get_currency()] ); ?></span></td>
									</tr>
								<?php endforeach; ?>

								<?php foreach ( $tax_items as $tax ) : ?>
									<?php
									// Prozentsatz aus dem Label extrahieren – z. B. "MwSt. 19 % DE" → "19 %"
									preg_match( '/(\d{1,2}\s?%)/', $tax->label, $matches );
									$tax_rate_label = isset( $matches[1] ) ? $matches[1] : $tax->label;
									?>
									<tr class="tax-rate">
										<th class="description">MwSt (<?php echo esc_html( $tax_rate_label ); ?>)</th>
										<td class="price"><span class="totals-price"><?php echo $tax->formatted_amount; ?></span></td>
									</tr>
								<?php endforeach; ?>

								<tr class="order-total">
									<th class="description">Gesamtbrutto</th>
									<td class="price"><span class="totals-price"><?php echo $grand_total; ?></span></td>
								</tr>
							</tfoot>
						</table>
					</td>

				</tr>
			</tfoot>
		</table>

		<?php
			if (method_exists($this, 'get_footer')) {
				$footer = $this->get_footer();
				if (!empty($footer)) {
					echo html_entity_decode($footer);
				}
			}
		?>

		<!-- FOOTER
		--------------------------------->
		<div class="bottom-spacer"></div>
		<htmlpagefooter name="docFooter">
			<div id="footer">
				<span class="separator"></span>
				<table style="padding: 0;">
					<tr>
						<td>
							<?php
								echo $this->get_extra_1();
							?>
						</td>
						<td style="padding: 0 20px;">
							<?php
								echo $this->get_extra_2();
							?>
						</td>
						<td>
							<?php
								echo $this->get_extra_3();
							?>
						</td>
					</tr>
				</table>
			</div>
		</htmlpagefooter>
	</body>
</html>