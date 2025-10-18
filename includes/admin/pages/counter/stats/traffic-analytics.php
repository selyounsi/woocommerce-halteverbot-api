<div class="analytics-section">

    <!-- Suchmaschinen -->
    <?php if (!empty($report["traffic_metrics"]["search_engines"])): ?>
        <div class="postbox">
            <div class="inside">
                <h2 class="hndle"><span>Suchmaschinen</span></h2>
                <table class="widefat fixed striped">
                    <tbody>
                        <?php foreach ($report["traffic_metrics"]["search_engines"] as $engine): ?>
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
    <?php if (!empty($report["traffic_metrics"]["social_networks"])): ?>
        <div class="postbox">
            <div class="inside">
                <h2 class="hndle"><span>Soziale Netzwerke</span></h2>
                <table class="widefat fixed striped">
                    <tbody>
                        <?php foreach ($report["traffic_metrics"]["social_networks"] as $social): ?>
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

    <!-- 🟢 Top 10 Keywords -->
    <?php if (!empty($report["traffic_metrics"]["gsc_top_keywords"])): ?>
        <div class="postbox">
            <div class="inside">
                <h2 class="hndle"><span>Top 10 Keywords (nach Klicks)</span></h2>
                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Search term</th>
                            <th>Clicks</th>
                            <th>Impressions</th>
                            <th>Position | Change</th>
                            <th>Last Position | Change</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report["traffic_metrics"]["gsc_top_keywords"] as $row): ?>
                            <tr>
                                <td><?php echo esc_html($row["search_term"]); ?></td>
                                <td><strong><?php echo $row["clicks"]; ?></strong></td>
                                <td><?php echo $row["impressions"]; ?></td>
                                <td>
                                    <?php echo $row["position"]; ?> | 
                                    <?php if ($row["change"] !== null): ?>
                                        <?php if ($row["change"] > 0): ?>
                                            <span style="color:green;">+<?php echo $row["change"]; ?></span>
                                        <?php elseif ($row["change"] < 0): ?>
                                            <span style="color:red;"><?php echo $row["change"]; ?></span>
                                        <?php else: ?>
                                            <span style="color:gray;"><?php echo $row["change"]; ?></span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color:gray;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo $row["last_position"] ?? "-"; ?> | 
                                    <?php if ($row["change"] !== null): ?>
                                        <?php if ($row["change"] > 0): ?>
                                            <span style="color:green;">+<?php echo $row["change"]; ?></span>
                                        <?php elseif ($row["change"] < 0): ?>
                                            <span style="color:red;"><?php echo $row["change"]; ?></span>
                                        <?php else: ?>
                                            <span style="color:gray;"><?php echo $row["change"]; ?></span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color:gray;">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- 🟢 Winner Keywords -->
    <?php if (!empty($report["traffic_metrics"]["gsc_winner_keywords"])): ?>
        <div class="postbox">
            <div class="inside">
                <h2 class="hndle"><span>Winner Keywords (Position verbessert)</span></h2>
                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Search term</th>
                            <th>Clicks</th>
                            <th>Impressions</th>
                            <th>Position | Change</th>
                            <th>Last Position | Change</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report["traffic_metrics"]["gsc_winner_keywords"] as $row): ?>
                            <tr>
                                <td><?php echo esc_html($row["search_term"]); ?></td>
                                <td><strong><?php echo $row["clicks"]; ?></strong></td>
                                <td><?php echo $row["impressions"]; ?></td>
                                <td style="color:green;">
                                    <?php echo $row["position"]; ?> | 
                                    +<?php echo $row["change"]; ?>
                                </td>
                                <td style="color:green;">
                                    <?php echo $row["last_position"] ?? "-"; ?> | 
                                    +<?php echo $row["change"]; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- 🔴 Loser Keywords -->
    <?php if (!empty($report["traffic_metrics"]["gsc_loser_keywords"])): ?>
        <div class="postbox">
            <div class="inside">
                <h2 class="hndle"><span>Loser Keywords (Position verschlechtert)</span></h2>
                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Search term</th>
                            <th>Clicks</th>
                            <th>Impressions</th>
                            <th>Position | Change</th>
                            <th>Last Position | Change</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report["traffic_metrics"]["gsc_loser_keywords"] as $row): ?>
                            <tr>
                                <td><?php echo esc_html($row["search_term"]); ?></td>
                                <td><strong><?php echo $row["clicks"]; ?></strong></td>
                                <td><?php echo $row["impressions"]; ?></td>
                                <td style="color:red;">
                                    <?php echo $row["position"]; ?> | 
                                    <?php echo $row["change"]; ?>
                                </td>
                                <td style="color:red;">
                                    <?php echo $row["last_position"] ?? "-"; ?> | 
                                    <?php echo $row["change"]; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- 🆕 New Keywords -->
    <?php if (!empty($report["traffic_metrics"]["gsc_new_keywords"])): ?>
        <div class="postbox">
            <div class="inside">
                <h2 class="hndle"><span>New Keywords (neue Impressionen)</span></h2>
                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Search term</th>
                            <th>Clicks</th>
                            <th>Impressions</th>
                            <th>Position | Change</th>
                            <th>Last Position | Change</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report["traffic_metrics"]["gsc_new_keywords"] as $row): ?>
                            <tr>
                                <td><?php echo esc_html($row["search_term"]); ?></td>
                                <td><strong><?php echo $row["clicks"]; ?></strong></td>
                                <td><?php echo $row["impressions"]; ?></td>
                                <td>
                                    <?php echo $row["position"]; ?> | 
                                    <span style="color:blue;">new</span>
                                </td>
                                <td>
                                    - | 
                                    <span style="color:blue;">new</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</div>
<style>
    .status-improved { color: green; }
    .status-declined { color: red; }
    .status-stable { color: gray; }
    .status-new { color: blue; }
</style>