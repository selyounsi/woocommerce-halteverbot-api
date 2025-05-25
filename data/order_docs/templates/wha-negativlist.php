<?php

    use Utils\DateUtils;
    use Utils\Order\OrderBuilder;
    use Utils\PDF\Invoice\CustomInvoice;
use Utils\PDF\PDFHelper;
use Utils\WPCAFields;

	$wpo = new CustomInvoice();

	if($this instanceof \Utils\PDF\Generator) {
		$order = new OrderBuilder($this->data);
	} else {
		$order = new OrderBuilder([
			"id" => $order->get_id()
		]);
	}

    $wpca = new WPCAFields($order->getOrder());
    $wpcaFields = $wpca->getMetaFieldsets();
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
			</tr>
		</table>
        <br>

        <h1 class="document-type-label">Negativliste für den Auftrag <?php echo $order->getOrder()->get_order_number(); ?></h1>
        <?php foreach($wpcaFields as $fields): ?>

            <table border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse: collapse; margin-bottom: 30px; border: none;">
                <tbody>
                    
                    <tr><td style="font-weight:bold; border: none;"><?=  esc_html__('Ort der Aufstellung') ?>:</td>
                    <td style="border: none;"><?=  esc_html($fields['address'] . ', ' . $fields['postalcode'] . ' ' . $fields['place']) ?></td></tr>
                    <tr><td style="font-weight:bold; border: none;"><?=  esc_html__('Länge HVZ') ?>:</td>
                    <td style="border: none;"><?=  esc_html($fields['distance_unit'] ?? '') ?></td></tr>
                    <tr><td style="font-weight:bold; border: none;"><?=  esc_html__('Kunde') ?>:</td>
                    <td style="border: none;"><?=  esc_html($fields['client_fname'] . ' ' . $fields['client_lname']) ?></td></tr>
                    <tr><td style="font-weight:bold; border: none;"><?=  esc_html__('Ausstellungsgrund') ?>:</td>
                    <td style="border: none;"><?=  esc_html($fields['reason'] ?? '') ?></td></tr>
                    
                    <?php 
                        // Add date and time information
                        $startDate = $fields['startdate'] ?? '';
                        $startTime = $fields['starttime'] ?? '';
                        $endDate = $fields['enddate'] ?? '';
                        $endTime = $fields['endtime'] ?? '';
                    ?>
                    <tr>
                        <td style="font-weight:bold; border: none;"><?= esc_html__('Aufstellungsdatum') ?>:</td>
                        <td style="border: none;">
                            <?php 
                                if ($startDate === $endDate) {
                                    echo esc_html(DateUtils::formatToGermanDate($startDate) . ' ' . $startTime . ' - ' . $endTime);
                                } else {
                                    echo esc_html(DateUtils::formatToGermanDate($startDate) . ' ' . $startTime . ' - ' . DateUtils::formatToGermanDate($endDate) . ' ' . $endTime);
                                }
                            ?>
                        </td>
                    </tr>

                    <?php 
                        if($order->getOrder()->get_id()) {
                            update_post_meta($order->getOrder()->get_id(), 'installer_name', $order->getMetaValue("installer_name"));
                            update_post_meta($order->getOrder()->get_id(), 'installer_date', $order->getMetaValue("installer_date"));
                        }

                        $installer_name = $order->getMetaValue("installer_name") ?: $order->getMetaValue("_file_upload_negativliste_installer");
                        $installer_date = $order->getMetaValue("installer_date") ?: $order->getMetaValue("_file_upload_negativliste_date");

                        PDFHelper::deleteFileAndMeta($order->getOrder(), '_file_upload_negativliste');
                    ?>
                                
                    <?php if($installer_name): ?>
                        <tr><td style="font-weight:bold; border: none;"><?= esc_html__('Aufsteller') ?>:</td>
                        <td style="border: none;"><?= esc_html($installer_name) ?></td></tr>
                    <?php endif; ?>
            
                    <?php if($installer_date): ?>
                        <tr><td style="font-weight:bold; border: none;"><?= esc_html__('Aufgestellt am') ?>:</td>
                        <td style="border: none;"><?= esc_html(DateUtils::formatToGermanDate($installer_date) ?? '') ?></td></tr>
                    <?php endif; ?>
        
                </tbody>
            </table>
        <?php endforeach; ?>

        <?php 
            $Z286 = "";
            $Z283 = "";
            $Z286_Z283 = "";
            $Z283_Z283 = "";

            $measures = $order->getMetaValue("_traffic_measures");
            foreach($measures as $measure) 
            {
                if($measure["main"] == "286-50" || $measure["main"] == "286") {
                    $Z286 = "checked";
                }
                if($measure["main"] == "283-50" || $measure["main"] == "283") {
                    $Z283 = "checked";
                }
                if($measure["main"] == "286-50-ggue-283-50" || $measure["main"] == "286-ggue-283") {
                    $Z286_Z283 = "checked";
                }
                if($measure["main"] == "283-10-ggue-283-20" || $measure["main"] == "283-ggue-283") {
                    $Z283_Z283 = "checked";
                }
            }
        ?>

        <table border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse: collapse; margin-bottom: 30px; border: none;">
            <tbody>
                <tr><td style="border: none;"><input type="checkbox" <?= $Z286 ?> style="border: none; position: relative; top: 6px;"> Z 286 StVO</td>
                <td style="border: none;"><input type="checkbox" <?= $Z283 ?> style="border: none; position: relative; top: 6px;"> Z 283 StVO</td></tr>

                <tr><td style="border: none;"><input type="checkbox" <?= $Z286_Z283 ?> style="border: none; position: relative; top: 6px;"> Z 286 StVO und gegenüber Z 283 StVO</td>
                <td style="border: none;"><input type="checkbox" <?= $Z283_Z283 ?> style="border: none; position: relative; top: 6px;"> Z 283 StVO und gegenüber Z 283 StVO</td></tr>
            </tbody>
        </table>

        <?php 
            $license_protocols = $order->getMetaValue("_order_license_protocols");
            if (!is_array($license_protocols)) {
                $license_protocols = []; // Default to an empty array if it's not an array
            }
        ?>

        <table border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th>Kennzeichen</th>
                    <th>Fahrzeugtyp</th>
                    <th>Farbe</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                    $maxRows = 10; 
                    $protocolCount = count($license_protocols);
                ?>
                <?php for ($i = 0; $i < max($maxRows, $protocolCount); $i++): ?>
                    <?php
                        $license_data = $license_protocols[$i] ?? [];
                        $license_plate = esc_html($license_data['license_plate'] ?? '&nbsp;');
                        $vehicle_type = esc_html($license_data['vehicle_type'] ?? '&nbsp;');
                        $color = esc_html($license_data['color'] ?? '&nbsp;');
                    ?>
                    <tr>
                        <td><?= $license_plate ?></td>
                        <td><?= $vehicle_type ?></td>
                        <td><?= $color ?></td>
                    </tr>
                <?php endfor; ?>
            </tbody>
        </table>

    </body>
</html>