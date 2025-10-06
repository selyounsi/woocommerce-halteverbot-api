<div class="analytics-section">

    <!-- Suchmaschinen -->
    <?php if (!empty($report["search_engines"])): ?>
        <div class="postbox">
            <div class="inside">
                <h2 class="hndle"><span>Suchmaschinen</span></h2>
                <table class="widefat fixed striped">
                    <tbody>
                        <?php foreach ($report["search_engines"] as $engine): ?>
                            <tr>
                                <td><?php echo esc_html($engine["source_name"]); ?></td>
                                <td><strong><?php echo $engine["count"]; ?></strong></td>
                                <td><?php echo $engine["percentage"]; ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Soziale Netzwerke -->
    <?php if (!empty($report["social_networks"])): ?>
        <div class="postbox">
            <div class="inside">
                <h2 class="hndle"><span>Soziale Netzwerke</span></h2>
                <table class="widefat fixed striped">
                    <tbody>
                        <?php foreach ($report["social_networks"] as $social): ?>
                            <tr>
                                <td><?php echo esc_html($social["source_name"]); ?></td>
                                <td><strong><?php echo $social["count"]; ?></strong></td>
                                <td><?php echo $social["percentage"]; ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Google Search Console -->
    <?php if (!empty($report["gsc_keywords"])): ?>
        <div class="postbox">
            <div class="inside">
                <h2 class="hndle"><span>Google Search Console Keywords (Letzten 16 Monate)</span></h2>
                <table class="widefat fixed striped">
                    <thead><tr><th>Keyword</th><th>Clicks</th><th>Anteil</th></tr></thead>
                    <tbody>
                        <?php foreach ($report["gsc_keywords"] as $keyword): ?>
                            <tr>
                                <td><?php echo esc_html($keyword["keywords"]); ?></td>
                                <td><strong><?php echo $keyword["count"]; ?></strong></td>
                                <td><?php echo $keyword["percentage"]; ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</div>
