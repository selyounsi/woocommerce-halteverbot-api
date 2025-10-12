<div class="analytics-section">

    <!-- Seiten-Performance Übersicht -->
    <?php if (!empty($report['page_metrics']['page_categories'])): ?>
    <div class="postbox">
        <div class="inside">
            <h2 class="hndle"><span>📊 Seiten-Performance nach Kategorie</span></h2>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th>Kategorie</th>
                        <th>Aufrufe</th>
                        <th>Unique Besucher</th>
                        <th>Ø Verweildauer</th>
                        <th>Exit-Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($report['page_metrics']['page_categories'] as $category): ?>
                    <tr>
                        <td><strong><?php echo esc_html($category['page_category']); ?></strong></td>
                        <td><?php echo $category['pageviews']; ?></td>
                        <td><?php echo $category['unique_sessions']; ?></td>
                        <td><?php echo $category['avg_time_minutes']; ?>m</td>
                        <td>
                            <span style="color: <?php echo $category['exit_rate'] < 30 ? '#00a32a' : ($category['exit_rate'] < 60 ? '#dba617' : '#d63638'); ?>">
                                <?php echo $category['exit_rate']; ?>%
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Detaillierte Seiten-Performance -->
    <?php if (!empty($report['page_metrics']['detailed_performance'])): ?>
    <div class="postbox">
        <div class="inside">
            <h2 class="hndle"><span>📈 Detaillierte Seiten-Performance</span></h2>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 40%">Seite</th>
                        <th>Aufrufe</th>
                        <th>Unique</th>
                        <th>Ø Zeit</th>
                        <th>Ø Ladezeit</th>
                        <th>Exit-Rate</th>
                        <th>Anteil</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($report['page_metrics']['detailed_performance'] as $page): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 500;">
                                <?php echo esc_html($page['page_title'] ?: 'Ohne Titel'); ?>
                            </div>
                            <div style="font-size: 11px; color: #666;">
                                <?php echo esc_html($page['url']); ?>
                            </div>
                        </td>
                        <td><strong><?php echo $page['pageviews']; ?></strong></td>
                        <td><?php echo $page['unique_visitors']; ?></td>
                        <td><?php echo $page['avg_time_minutes']; ?>m</td>
                        <td><?php echo round($page['avg_load_time_ms']/1000, 1); ?>s</td>
                        <td>
                            <span style="color: <?php echo $page['exit_rate'] < 30 ? '#00a32a' : ($page['exit_rate'] < 60 ? '#dba617' : '#d63638'); ?>">
                                <?php echo $page['exit_rate']; ?>%
                            </span>
                        </td>
                        <td><?php echo $page['traffic_share']; ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Engagement-Metriken -->
    <?php if (!empty($report['page_metrics']['engagement_metrics'])): ?>
    <div class="postbox">
        <div class="inside">
            <h2 class="hndle"><span>💡 Engagement-Metriken</span></h2>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 40%">Seite</th>
                        <th>Gesamt</th>
                        <th>Engaged</th>
                        <th>Engagement Rate</th>
                        <th>Ø Zeit</th>
                        <th>Max Zeit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($report['page_metrics']['engagement_metrics'] as $page): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 500;">
                                <?php echo esc_html($page['page_title'] ?: 'Ohne Titel'); ?>
                            </div>
                            <div style="font-size: 11px; color: #666;">
                                <?php echo esc_html($page['url']); ?>
                            </div>
                        </td>
                        <td><?php echo $page['total_views']; ?></td>
                        <td><?php echo $page['engaged_views']; ?></td>
                        <td>
                            <strong style="color: <?php echo $page['engagement_rate'] > 50 ? '#00a32a' : ($page['engagement_rate'] > 25 ? '#dba617' : '#d63638'); ?>">
                                <?php echo $page['engagement_rate']; ?>%
                            </strong>
                        </td>
                        <td><?php echo round($page['avg_time_seconds']/60, 1); ?>m</td>
                        <td><?php echo round($page['max_time_seconds']/60, 1); ?>m</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Meistbesuchte Seiten -->
    <?php if (!empty($report['page_metrics']['pages'])): ?>
    <div class="postbox">
        <div class="inside">
            <h2 class="hndle"><span>📈 Meistbesuchte Seiten</span></h2>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 40%">Seite</th>
                        <th>Aufrufe</th>
                        <th>Anteil</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($report['page_metrics']['pages'] as $page): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 500;">
                                <?php echo esc_html($page['page_title'] ?: 'Ohne Titel'); ?>
                            </div>
                            <div style="font-size: 11px; color: #666;">
                                <?php echo esc_html($page['url']); ?>
                            </div>
                        </td>
                        <td><strong><?php echo $page['count']; ?></strong></td>
                        <td><?php echo $page['percentage']; ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Einstiegsseiten -->
    <?php if (!empty($report['page_metrics']['entry_pages'])): ?>
    <div class="postbox">
        <div class="inside">
            <h2 class="hndle"><span>🚪 Einstiegsseiten</span></h2>
            <table class="widefat fixed striped">
                <thead>
                    <tr><th style="width: 40%">Seite</th><th>Einstiege</th><th>Anteil</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($report['page_metrics']['entry_pages'] as $page): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 500;">
                                <?php echo esc_html($page['page_title'] ?: 'Ohne Titel'); ?>
                            </div>
                            <div style="font-size: 11px; color: #666;">
                                <?php echo esc_html($page['url']); ?>
                            </div>
                        </td>
                        <td><strong><?php echo $page['entries']; ?></strong></td>
                        <td><?php echo $page['percentage']; ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Ausstiegsseiten -->
    <?php if (!empty($report['page_metrics']['exit_pages'])): ?>
    <div class="postbox">
        <div class="inside">
            <h2 class="hndle"><span>🚶 Ausstiegsseiten</span></h2>
            <table class="widefat fixed striped">
                <thead>
                    <tr><th style="width: 40%">Seite</th><th>Ausstiege</th><th>Anteil</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($report['page_metrics']['exit_pages'] as $page): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 500;">
                                <?php echo esc_html($page['page_title'] ?: 'Ohne Titel'); ?>
                            </div>
                            <div style="font-size: 11px; color: #666;">
                                <?php echo esc_html($page['url']); ?>
                            </div>
                        </td>
                        <td><strong><?php echo $page['exits']; ?></strong></td>
                        <td><?php echo $page['percentage']; ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Exit-Raten -->
    <?php if (!empty($report['page_metrics']['exit_rates'])): ?>
    <div class="postbox">
        <div class="inside">
            <h2 class="hndle"><span>📉 Höchste Exit-Raten</span></h2>
            <table class="widefat fixed striped">
                <thead>
                    <tr><th style="width: 40%">Seite</th><th>Aufrufe</th><th>Ausstiege</th><th>Rate</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($report['page_metrics']['exit_rates'] as $page): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 500;">
                                <?php echo esc_html($page['page_title'] ?: 'Ohne Titel'); ?>
                            </div>
                            <div style="font-size: 11px; color: #666;">
                                <?php echo esc_html($page['url']); ?>
                            </div>
                        </td>
                        <td><?php echo $page['total_views']; ?></td>
                        <td><?php echo $page['exit_views']; ?></td>
                        <td>
                            <strong style="color: <?php echo $page['exit_rate'] < 30 ? '#00a32a' : ($page['exit_rate'] < 60 ? '#dba617' : '#d63638'); ?>">
                                <?php echo $page['exit_rate']; ?>%
                            </strong>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Seitenfluss -->
    <?php if (!empty($report['page_metrics']['page_flow'])): ?>
    <div class="postbox">
        <div class="inside">
            <h2 class="hndle"><span>🔄 Seitenfluss (Top 8 Seiten)</span></h2>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th>Aktuelle Seite</th>
                        <th>Nächste Seite</th>
                        <th>Übergänge</th>
                        <th>Anteil</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($report['page_metrics']['page_flow'] as $flow): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 500;">
                                <?php echo esc_html(basename($flow['current_page'])); ?>
                            </div>
                            <div style="font-size: 11px; color: #666;">
                                <?php echo esc_html($flow['current_page']); ?>
                            </div>
                        </td>
                        <td>
                            <?php if ($flow['next_page'] === 'Ausstieg'): ?>
                                <span style="color: #d63638;">❌ <?php echo $flow['next_page']; ?></span>
                            <?php else: ?>
                                <div style="font-weight: 500;">
                                    <?php echo esc_html(basename($flow['next_page'])); ?>
                                </div>
                                <div style="font-size: 11px; color: #666;">
                                    <?php echo esc_html($flow['next_page']); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $flow['transitions']; ?></td>
                        <td><?php echo $flow['percentage']; ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Chart Container -->
    <?php if (!empty($report['page_metrics']['chart_data'])): ?>
    
        <!-- Erste Chart-Reihe -->
        <div style="display: flex; gap: 1rem; margin-bottom: 20px; flex-wrap: wrap;">
            <!-- Seiten-Aufrufe nach Kategorie -->
            <div style="flex: 1; min-width: 400px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h3 style="margin: 0 0 15px 0;">📊 Seiten-Aufrufe nach Kategorie</h3>
                <div style="height: 300px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                    <canvas id="pageViewsByCategoryChart"></canvas>
                </div>
            </div>

            <!-- Verweildauer nach Kategorie -->
            <div style="flex: 1; min-width: 400px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h3 style="margin: 0 0 15px 0;">⏱️ Verweildauer nach Kategorie</h3>
                <div style="height: 300px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                    <canvas id="timeByCategoryChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Zweite Chart-Reihe -->
        <div style="display: flex; gap: 1rem; margin-bottom: 20px; flex-wrap: wrap;">
            <!-- Top Seiten - Aufrufe -->
            <div style="flex: 1; min-width: 400px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h3 style="margin: 0 0 15px 0;">📈 Top Seiten - Aufrufe</h3>
                <div style="height: 300px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                    <canvas id="topPagesViewsChart"></canvas>
                </div>
            </div>

            <!-- Top Seiten - Exit-Raten -->
            <div style="flex: 1; min-width: 400px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h3 style="margin: 0 0 15px 0;">📉 Top Seiten - Exit-Raten</h3>
                <div style="height: 300px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                    <canvas id="topPagesExitRatesChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Dritte Chart-Reihe -->
        <div style="display: flex; gap: 1rem; margin-bottom: 20px; flex-wrap: wrap;">
            <!-- Engagement-Raten -->
            <div style="flex: 1; min-width: 400px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h3 style="margin: 0 0 15px 0;">💡 Engagement-Raten</h3>
                <div style="height: 300px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                    <canvas id="engagementRatesChart"></canvas>
                </div>
            </div>

            <!-- Einstiegs vs Ausstiegsseiten -->
            <div style="flex: 1; min-width: 400px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h3 style="margin: 0 0 15px 0;">🔄 Einstiegs vs Ausstiegsseiten</h3>
                <div style="height: 300px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                    <canvas id="entryExitComparisonChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Vierte Chart-Reihe (Full Width) -->
        <div style="display: flex; gap: 1rem; margin-bottom: 20px; flex-wrap: wrap;">
            <!-- Seiten-Performance Vergleich -->
            <div style="flex: 1; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h3 style="margin: 0 0 15px 0;">📊 Seiten-Performance Vergleich</h3>
                <div style="height: 400px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                    <canvas id="pagePerformanceComparisonChart"></canvas>
                </div>
            </div>
        </div>

    <?php endif; ?>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($report['page_metrics']['chart_data'])): ?>
    const chartData = <?php echo json_encode($report['page_metrics']['chart_data']); ?>;
    initPageAnalyticsCharts(chartData);
    <?php endif; ?>
});

function initPageAnalyticsCharts(chartData) {
    // 1. Seiten-Aufrufe nach Kategorie
    if (chartData.page_performance?.categories) {
        new Chart(document.getElementById('pageViewsByCategoryChart'), {
            type: 'doughnut',
            data: {
                labels: chartData.page_performance.categories.labels,
                datasets: [{
                    data: chartData.page_performance.categories.pageviews,
                    backgroundColor: [
                        '#2271b1', '#00a32a', '#d63638', '#dba617', 
                        '#8b5cf6', '#06b6d4', '#f97316', '#84cc16'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    title: { display: false }
                }
            }
        });
    }

    // 2. Verweildauer nach Kategorie
    if (chartData.page_performance?.categories) {
        new Chart(document.getElementById('timeByCategoryChart'), {
            type: 'bar',
            data: {
                labels: chartData.page_performance.categories.labels,
                datasets: [{
                    label: 'Ø Verweildauer (Sekunden)',
                    data: chartData.page_performance.categories.avg_times,
                    backgroundColor: '#2271b1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }

    // 3. Top Seiten - Aufrufe
    if (chartData.page_performance?.top_pages) {
        new Chart(document.getElementById('topPagesViewsChart'), {
            type: 'bar',
            data: {
                labels: chartData.page_performance.top_pages.labels,
                datasets: [{
                    label: 'Aufrufe',
                    data: chartData.page_performance.top_pages.pageviews,
                    backgroundColor: '#00a32a'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                scales: {
                    x: { beginAtZero: true }
                }
            }
        });
    }

    // 4. Top Seiten - Exit-Raten
    if (chartData.page_performance?.top_pages) {
        new Chart(document.getElementById('topPagesExitRatesChart'), {
            type: 'bar',
            data: {
                labels: chartData.page_performance.top_pages.labels,
                datasets: [{
                    label: 'Exit-Rate (%)',
                    data: chartData.page_performance.top_pages.exit_rates,
                    backgroundColor: chartData.page_performance.top_pages.exit_rates.map(rate => 
                        rate < 30 ? '#00a32a' : rate < 60 ? '#dba617' : '#d63638'
                    )
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                scales: {
                    x: { 
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });
    }

    // 5. Engagement-Raten
    if (chartData.engagement) {
        new Chart(document.getElementById('engagementRatesChart'), {
            type: 'bar',
            data: {
                labels: chartData.engagement.labels,
                datasets: [{
                    label: 'Engagement Rate (%)',
                    data: chartData.engagement.engagement_rates,
                    backgroundColor: chartData.engagement.engagement_rates.map(rate => 
                        rate > 50 ? '#00a32a' : rate > 25 ? '#dba617' : '#d63638'
                    )
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                scales: {
                    x: { 
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });
    }

    // 6. Einstiegs vs Ausstiegsseiten
    if (chartData.traffic_flow?.entry_pages && chartData.traffic_flow?.exit_pages) {
        new Chart(document.getElementById('entryExitComparisonChart'), {
            type: 'bar',
            data: {
                labels: chartData.traffic_flow.entry_pages.labels.slice(0, 6),
                datasets: [
                    {
                        label: 'Einstiege',
                        data: chartData.traffic_flow.entry_pages.entries.slice(0, 6),
                        backgroundColor: '#00a32a'
                    },
                    {
                        label: 'Ausstiege',
                        data: chartData.traffic_flow.exit_pages.exits.slice(0, 6),
                        backgroundColor: '#d63638'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { 
                        grid: { display: false }
                    },
                    y: { beginAtZero: true }
                }
            }
        });
    }

    // 7. Seiten-Performance Vergleich
    if (chartData.page_comparison?.performance) {
        new Chart(document.getElementById('pagePerformanceComparisonChart'), {
            type: 'radar',
            data: {
                labels: chartData.page_comparison.performance.labels,
                datasets: [
                    {
                        label: 'Ø Verweildauer (sek)',
                        data: chartData.page_comparison.performance.avg_times,
                        borderColor: '#2271b1',
                        backgroundColor: 'rgba(34, 113, 177, 0.1)'
                    },
                    {
                        label: 'Exit-Rate (%)',
                        data: chartData.page_comparison.performance.exit_rates,
                        borderColor: '#d63638',
                        backgroundColor: 'rgba(214, 54, 56, 0.1)'
                    },
                    {
                        label: 'Ladezeit (sek)',
                        data: chartData.page_comparison.performance.load_times,
                        borderColor: '#00a32a',
                        backgroundColor: 'rgba(0, 163, 42, 0.1)'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        beginAtZero: true,
                        ticks: { display: false }
                    }
                }
            }
        });
    }
}
</script>