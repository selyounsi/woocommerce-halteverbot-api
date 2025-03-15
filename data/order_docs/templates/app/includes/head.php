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