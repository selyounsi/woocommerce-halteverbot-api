<div class="analytics-section">

    <!-- Bildschirmauflösungen -->
    <?php if (!empty($report["misc_metrics"]["screen_resolutions"])): ?>
        <div class="postbox">
            <div class="inside">
                <h2 class="hndle"><span>Bildschirmauflösungen</span></h2>
                <table class="widefat fixed striped">
                    <thead><tr><th>Auflösung</th><th>Sessions</th><th>Anteil</th></tr></thead>
                    <tbody>
                        <?php foreach ($report["misc_metrics"]["screen_resolutions"] as $resolution): ?>
                            <tr>
                                <td><?php echo esc_html($resolution["screen_resolution"]); ?></td>
                                <td><strong><?php echo $resolution["count"]; ?></strong></td>
                                <td><?php echo $resolution["percentage"]; ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Sprachen -->
    <?php if (!empty($report["misc_metrics"]["languages"])): ?>
        <div class="postbox">
            <div class="inside">
                <h2 class="hndle"><span>Sprachen</span></h2>
                <table class="widefat fixed striped">
                    <tbody>
                        <?php foreach ($report["misc_metrics"]["languages"] as $language): ?>
                            <tr>
                                <td><?php echo esc_html($language["language_clean"]); ?></td>
                                <td><strong><?php echo $language["count"]; ?></strong></td>
                                <td><?php echo $language["percentage"]; ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Besuchszeiten -->
    <?php if (!empty($report["misc_metrics"]["visit_times"])): ?>
        <div class="postbox">
            <div class="inside">
                <h2 class="hndle"><span>Besuchszeiten (Tageszeit)</span></h2>
                <table class="widefat fixed striped">
                    <thead><tr><th>Uhrzeit</th><th>Sessions</th></tr></thead>
                    <tbody>
                        <?php foreach ($report["misc_metrics"]["visit_times"] as $time): ?>
                            <tr>
                                <td><?php echo sprintf("%02d:00 - %02d:59", $time["hour"], $time["hour"]); ?></td>
                                <td><strong><?php echo $time["count"]; ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</div>
