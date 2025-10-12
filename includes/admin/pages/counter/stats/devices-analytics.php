<div class="analytics-section">

    <!-- Gerätetypen -->
    <?php if (!empty($report["geo_metrics"]["device_types"])): ?>
        <div class="postbox">
            <div class="inside">
                <h2 class="hndle"><span>Gerätetypen</span></h2>
                <table class="widefat fixed striped">
                    <tbody>
                        <?php foreach ($report["geo_metrics"]["device_types"] as $device): ?>
                            <tr>
                                <td><?php echo esc_html($device["device_type"]); ?></td>
                                <td><strong><?php echo $device["count"]; ?></strong></td>
                                <td><?php echo $device["percentage"]; ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Geräte-Marken -->
    <?php if (!empty($report["geo_metrics"]["device_brands"])): ?>
        <div class="postbox">
            <div class="inside">
                <h2 class="hndle"><span>Top Geräte-Marken</span></h2>
                <table class="widefat fixed striped">
                    <tbody>
                        <?php foreach ($report["geo_metrics"]["device_brands"] as $brand): ?>
                            <tr>
                                <td><?php echo esc_html($brand["brand"]); ?></td>
                                <td><strong><?php echo $brand["count"]; ?></strong></td>
                                <td><?php echo $brand["percentage"]; ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Betriebssysteme -->
    <?php if (!empty($report["geo_metrics"]["operating_systems"])): ?>
        <div class="postbox">
            <div class="inside">
                <h2 class="hndle"><span>Betriebssysteme</span></h2>
                <table class="widefat fixed striped">
                    <tbody>
                        <?php foreach ($report["geo_metrics"]["operating_systems"] as $os): ?>
                            <tr>
                                <td><?php echo esc_html($os["platform"]); ?></td>
                                <td><strong><?php echo $os["count"]; ?></strong></td>
                                <td><?php echo $os["percentage"]; ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Browser -->
    <?php if (!empty($report["geo_metrics"]["browsers"])): ?>
        <div class="postbox">
            <div class="inside">
                <h2 class="hndle"><span>Browser</span></h2>
                <table class="widefat fixed striped">
                    <tbody>
                        <?php foreach ($report["geo_metrics"]["browsers"] as $browser): ?>
                            <tr>
                                <td><?php echo esc_html($browser["browser_name"]); ?></td>
                                <td><strong><?php echo $browser["count"]; ?></strong></td>
                                <td><?php echo $browser["percentage"]; ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</div>
