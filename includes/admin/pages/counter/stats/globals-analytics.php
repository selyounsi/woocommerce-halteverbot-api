<!-- Besucher im Überblick -->
<div class="postbox" style="flex: 1;">
    <div class="inside">
        <h2 class="hndle" style="margin-bottom: 10px;"><span>Besucher im Überblick</span></h2>
        <table class="widefat fixed striped">
            <tbody>
                <tr><td>Heute</td><td><strong><?php echo $analyticsInstance->visitors_today(); ?></strong></td></tr>
                <tr><td>Gestern</td><td><strong><?php echo $analyticsInstance->visitors_yesterday(); ?></strong></td></tr>
                <tr><td>Diese Woche</td><td><strong><?php echo $analyticsInstance->visitors_this_week(); ?></strong></td></tr>
                <tr><td>Dieser Monat</td><td><strong><?php echo $analyticsInstance->visitors_this_month(); ?></strong></td></tr>
                <tr><td>Letzter Monat</td><td><strong><?php echo $analyticsInstance->visitors_last_month(); ?></strong></td></tr>
                <tr><td>Dieses Jahr</td><td><strong><?php echo $analyticsInstance->visitors_this_year(); ?></strong></td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Session Metriken -->
<div class="postbox" style="flex: 1;">
    <div class="inside">
        <h2 class="hndle" style="margin-bottom: 10px;"><span>Session Metriken</span></h2>
        <table class="widefat fixed striped">
            <tbody>
                <tr><td>Ø Session-Dauer</td><td><strong><?php echo $report['session_metrics']['avg_duration']; ?>s</strong></td></tr>
                <tr><td>Ø Seiten/Session</td><td><strong><?php echo $report['session_metrics']['avg_pages']; ?></strong></td></tr>
                <tr><td>Bounce Rate</td><td><strong><?php echo $report['session_metrics']['bounce_rate']; ?>%</strong></td></tr>
                <tr><td>Ø Zeit/Seite</td><td><strong><?php echo $report['session_metrics']['avg_time_on_page']; ?>s</strong></td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Visitor Types -->
<div class="postbox" style="flex: 1;">
    <div class="inside">
        <h2 class="hndle" style="margin-bottom: 10px;"><span>Besucher-Typen</span></h2>
        <table class="widefat fixed striped">
            <tbody>
                <?php foreach ($report["visitor_types"] as $visitor): ?>
                    <tr>
                        <td><?php echo esc_html($visitor["visitor_type"]); ?></td>
                        <td><strong><?php echo $visitor["count"]; ?></strong></td>
                        <td><?php echo $visitor["percentage"]; ?>%</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Traffic Channels -->
<div class="postbox" style="flex: 1;">
    <div class="inside">
        <h2 class="hndle" style="margin-bottom: 10px;"><span>Traffic-Kanäle</span></h2>
        <table class="widefat fixed striped">
            <tbody>
                <?php foreach ($report["traffic_channels"] as $channel): ?>
                    <tr>
                        <td><?php echo esc_html($channel["source_channel"]); ?></td>
                        <td><strong><?php echo $channel["count"]; ?></strong></td>
                        <td><?php echo $channel["percentage"]; ?>%</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
