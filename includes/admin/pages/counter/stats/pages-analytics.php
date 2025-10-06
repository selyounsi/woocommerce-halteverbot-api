<div class="analytics-section">

    <!-- Meistbesuchte Seiten -->
    <?php if (!empty($report["pages"])): ?>
        <div class="postbox">
            <div class="inside">
                <h2 class="hndle"><span>Meistbesuchte Seiten</span></h2>
                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Seite</th>
                            <th>Aufrufe</th>
                            <th>Anteil</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report["pages"] as $page): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 500;">
                                        <?php echo esc_html($page["page_title"] ?: 'Ohne Titel'); ?>
                                    </div>
                                    <div style="font-size: 11px; color: #666;">
                                        <?php echo esc_html($page["url"]); ?>
                                    </div>
                                </td>
                                <td><strong><?php echo $page["count"]; ?></strong></td>
                                <td><?php echo $page["percentage"]; ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>


    <!-- Einstiegsseiten -->
    <?php if (!empty($report["entry_pages"])): ?>
        <div class="postbox">
            <div class="inside">
                <h2 class="hndle"><span>Einstiegsseiten</span></h2>
                <table class="widefat fixed striped">
                    <thead>
                        <tr><th>Seite</th><th>Einstiege</th><th>Anteil</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report["entry_pages"] as $page): ?>
                            <tr>
                                <td><?php echo esc_html($page["page_title"] ?: 'Ohne Titel'); ?></td>
                                <td><strong><?php echo $page["entries"]; ?></strong></td>
                                <td><?php echo $page["percentage"]; ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>


    <!-- Ausstiegsseiten -->
    <?php if (!empty($report["exit_pages"])): ?>
        <div class="postbox">
            <div class="inside">
                <h2 class="hndle"><span>Ausstiegsseiten</span></h2>
                <table class="widefat fixed striped">
                    <thead>
                        <tr><th>Seite</th><th>Ausstiege</th><th>Anteil</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report["exit_pages"] as $page): ?>
                            <tr>
                                <td><?php echo esc_html($page["page_title"]); ?></td>
                                <td><strong><?php echo $page["exits"]; ?></strong></td>
                                <td><?php echo $page["percentage"]; ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>


    <!-- Exit-Raten -->
    <?php if (!empty($report["exit_rates"])): ?>
        <div class="postbox">
            <div class="inside">
                <h2 class="hndle"><span>Exit-Raten</span></h2>
                <table class="widefat fixed striped">
                    <thead>
                        <tr><th>Seite</th><th>Aufrufe</th><th>Ausstiege</th><th>Rate</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report["exit_rates"] as $page): ?>
                            <tr>
                                <td><?php echo esc_html($page["page_title"]); ?></td>
                                <td><?php echo $page["total_views"]; ?></td>
                                <td><?php echo $page["exit_views"]; ?></td>
                                <td><?php echo $page["exit_rate"]; ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</div>
