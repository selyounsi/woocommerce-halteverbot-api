<?php
    use Utils\PDF\Invoice\CustomInvoice;
	use Utils\Order\OrderBuilder;

	$wpo = new CustomInvoice();

	if($this instanceof \Utils\PDF\Generator) {
		$order = new OrderBuilder($this->data);
	} else {
		$order = new OrderBuilder([
			"id" => $this->order->get_id()
		]);
	}
?>
<!DOCTYPE html>
<html>
	<head>
		<style type="text/css">
			<?php require(WHA_PLUGIN_PATH . "/data/order_docs/templates/assets/style.css"); ?>
		</style>
	</head>
	<body>
		
		<!-- LOGO
		--------------------------------->
		<table class="head container">
			<tr>
				<td class="header">
				<?php echo $wpo->displayHeaderLogo(); ?>
				</td>
				<td class="shop-info">
					<div class="shop-name"><h3><?php echo $wpo->getTemplatePart("shop_name"); ?></h3></div>
					<div class="shop-address"><?php echo $wpo->getTemplatePart("shop_address"); ?></div>
				</td>
			</tr>
		</table>