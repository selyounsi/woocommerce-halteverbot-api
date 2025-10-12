<div class="analytics-section">

    <!-- Länder -->
    <?php if (!empty($report["geo_metrics"]["countries"])): ?>
        <div class="postbox">
            <div class="inside">
                <h2 class="hndle"><span>Länder</span></h2>
                <table class="widefat fixed striped">
                    <thead><tr><th>Land</th><th>Sessions</th><th>Anteil</th></tr></thead>
                    <tbody>
                        <?php foreach ($report["geo_metrics"]["countries"] as $country): ?>
                            <tr>
                                <td><?php echo esc_html($country["country_name"]); ?></td>
                                <td><strong><?php echo $country["count"]; ?></strong></td>
                                <td><?php echo $country["percentage"]; ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Städte -->
    <?php if (!empty($report["geo_metrics"]["cities"])): ?>
        <div class="postbox">
            <div class="inside">
                <h2 class="hndle"><span>Top Städte</span></h2>
                <table class="widefat fixed striped">
                    <thead><tr><th>Stadt</th><th>Land</th><th>Sessions</th><th>Anteil</th></tr></thead>
                    <tbody>
                        <?php foreach ($report["geo_metrics"]["cities"] as $city): ?>
                            <tr>
                                <td><?php echo esc_html($city["city"]); ?></td>
                                <td><?php echo esc_html($city["country_name"]); ?></td>
                                <td><strong><?php echo $city["count"]; ?></strong></td>
                                <td><?php echo $city["percentage"]; ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</div>
