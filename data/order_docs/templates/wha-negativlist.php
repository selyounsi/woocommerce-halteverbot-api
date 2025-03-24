<?php

    use Utils\DateUtils;
    use Utils\WPCAFields;

    $wpca = new WPCAFields($this->order->getOrder());
    $wpcaFields = $wpca->getMetaFieldsets();
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
        <br>

        <h1 class="document-type-label">Negativliste für den Auftrag <?php echo $this->order->getOrder()->get_order_number(); ?></h1>
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
                        if($this->order->getOrder()->get_id()) {
                            update_post_meta($this->order->getOrder()->get_id(), 'installer_name', $this->order->getMetaValue("installer_name"));
                            update_post_meta($this->order->getOrder()->get_id(), 'installer_date', $this->order->getMetaValue("installer_date"));
                        }
                    ?>
                                
                    <?php if($this->order->getMetaValue("installer_name")): ?>
                        <tr><td style="font-weight:bold; border: none;"><?= esc_html__('Aufsteller') ?>:</td>
                        <td style="border: none;"><?= esc_html($this->order->getMetaValue("installer_name")) ?></td></tr>
                    <?php endif; ?>
            
                    <?php if($this->order->getMetaValue("installer_date")): ?>
                        <tr><td style="font-weight:bold; border: none;"><?= esc_html__('Aufgestellt am') ?>:</td>
                        <td style="border: none;"><?= esc_html(DateUtils::formatToGermanDate($this->order->getMetaValue("installer_date")) ?? '') ?></td></tr>
                    <?php endif; ?>
        
                </tbody>
            </table>
        <?php endforeach; ?>

        <?php 
            $Z286 = "";
            $Z283 = "";
            $Z286_Z283 = "";
            $Z283_Z283 = "";

            $measures = $this->order->getMetaValue("_traffic_measures");
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
            $license_protocols = $this->order->getMetaValue("_order_license_protocols");
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

        <!-- FOOTER
        --------------------------------->
        <div class="bottom-spacer"></div>
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