<!-- ORDER ANALYTICS SECTION -->
<div class="postbox" style="width: 100%; flex-basis: 100%">
    <div class="inside">

        <!-- Order Charts -->
        <div style="display: flex; gap: 1rem; margin-bottom: 20px; flex-wrap: wrap;">
            <!-- Tägliche Bestellungen (30 Tage) -->
            <div style="flex: 1; min-width: 400px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h3 style="margin: 0 0 15px 0;">📈 Tägliche Bestellungen (30 Tage)</h3>
                <div style="height: 300px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                    <canvas id="dailyOrders30dChart"></canvas>
                </div>
            </div>

            <!-- Tägliche Bestellungen (7 Tage) -->
            <div style="flex: 1; min-width: 400px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h3 style="margin: 0 0 15px 0;">📈 Tägliche Bestellungen (7 Tage)</h3>
                <div style="height: 300px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                    <canvas id="dailyOrders7dChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Status & Payment Charts -->
        <div style="display: flex; gap: 1rem; margin-bottom: 20px; flex-wrap: wrap;">
            <!-- Bestellstatus Verteilung -->
            <div style="flex: 1; min-width: 400px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h3 style="margin: 0 0 15px 0;">📊 Bestellstatus Verteilung</h3>
                <div style="height: 300px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                    <canvas id="orderStatusChart"></canvas>
                </div>
            </div>

            <!-- Payment Methoden -->
            <div style="flex: 1; min-width: 400px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h3 style="margin: 0 0 15px 0;">💳 Payment Methoden</h3>
                <div style="height: 300px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                    <canvas id="paymentMethodsChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Weitere Order Charts -->
        <div style="display: flex; gap: 1rem; margin-bottom: 20px; flex-wrap: wrap;">
            <!-- Monatliche Bestellungen -->
            <div style="flex: 1; min-width: 400px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h3 style="margin: 0 0 15px 0;">📅 Monatliche Bestellungen (12 Monate)</h3>
                <div style="height: 300px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                    <canvas id="monthlyOrdersChart"></canvas>
                </div>
            </div>

            <!-- Bestellzeiten Heatmap -->
            <div style="flex: 1; min-width: 400px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h3 style="margin: 0 0 15px 0;">🕒 Bestellzeiten Heatmap</h3>
                <div style="height: 300px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                    <canvas id="orderTimeHeatmapChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Top Produkte Tabelle -->
        <div class="postbox" style="width: 100%; flex-basis: 100%">
            <div class="inside">
                <h2 class="hndle" style="margin-bottom: 10px;"><span>🏆 Top Produkte nach Umsatz</span></h2>
                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Produkt</th>
                            <th>Umsatz</th>
                            <th>Menge</th>
                            <th>Bestellungen</th>
                            <th>Ø Preis</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($report['order_metrics']['current_period']['top_products'])): ?>
                            <?php foreach ($report['order_metrics']['current_period']['top_products'] as $product): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 500;">
                                            <?php echo esc_html($product['product_name']); ?>
                                        </div>
                                        <div style="font-size: 11px; color: #666;">
                                            ID: <?php echo $product['product_id']; ?>
                                        </div>
                                    </td>
                                    <td style="font-weight: bold; color: #2e7d32;">
                                        <?php echo number_format($product['total_revenue'], 2, ',', '.'); ?> €
                                    </td>
                                    <td><?php echo $product['total_quantity']; ?></td>
                                    <td><?php echo $product['order_count']; ?></td>
                                    <td><?php echo number_format($product['avg_product_price'], 2, ',', '.'); ?> €</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center;">Keine Produktdaten verfügbar</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Status Distribution Tabelle -->
        <div class="postbox" style="width: 100%; flex-basis: 100%">
            <div class="inside">
                <h2 class="hndle" style="margin-bottom: 10px;"><span>📋 Bestellstatus Übersicht</span></h2>
                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Anzahl</th>
                            <th>Umsatz</th>
                            <th>Anteil</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($report['order_metrics']['current_period']['status_distribution'])): ?>
                            <?php foreach ($report['order_metrics']['current_period']['status_distribution'] as $status): ?>
                                <tr>
                                    <td><?php echo esc_html($status['status']); ?></td>
                                    <td><strong><?php echo $status['count']; ?></strong></td>
                                    <td><?php echo number_format($status['revenue'], 2, ',', '.'); ?> €</td>
                                    <td><?php echo $status['percentage']; ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center;">Keine Status-Daten verfügbar</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>

document.addEventListener('DOMContentLoaded', function() {

    // Order Analytics Charts
    const orderChartData = <?php echo json_encode($report['order_metrics']['chart_data'] ?? []); ?>;

    // 1. Tägliche Bestellungen (30 Tage)
    if (orderChartData.orders_daily_30d && Object.keys(orderChartData.orders_daily_30d).length > 0) {
        const dates30d = Object.keys(orderChartData.orders_daily_30d);
        const orders30d = dates30d.map(date => orderChartData.orders_daily_30d[date].orders || 0);
        const revenue30d = dates30d.map(date => orderChartData.orders_daily_30d[date].revenue || 0);
        
        new Chart(document.getElementById('dailyOrders30dChart'), {
            type: 'line',
            data: {
                labels: dates30d,
                datasets: [
                    {
                        label: 'Bestellungen',
                        data: orders30d,
                        borderColor: '#2271b1',
                        backgroundColor: 'rgba(34, 113, 177, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Umsatz (€)',
                        data: revenue30d,
                        borderColor: '#2e7d32',
                        backgroundColor: 'rgba(46, 125, 50, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: { display: true, text: 'Bestellungen' },
                        beginAtZero: true
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: { display: true, text: 'Umsatz (€)' },
                        grid: { drawOnChartArea: false },
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // 2. Tägliche Bestellungen (7 Tage)
    if (orderChartData.orders_daily_7d && Object.keys(orderChartData.orders_daily_7d).length > 0) {
        const dates7d = Object.keys(orderChartData.orders_daily_7d);
        const orders7d = dates7d.map(date => orderChartData.orders_daily_7d[date].orders || 0);
        const revenue7d = dates7d.map(date => orderChartData.orders_daily_7d[date].revenue || 0);
        
        new Chart(document.getElementById('dailyOrders7dChart'), {
            type: 'bar',
            data: {
                labels: dates7d,
                datasets: [
                    {
                        label: 'Bestellungen',
                        data: orders7d,
                        backgroundColor: 'rgba(34, 113, 177, 0.6)',
                        borderColor: '#2271b1',
                        borderWidth: 1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Umsatz (€)',
                        data: revenue7d,
                        borderColor: '#2e7d32',
                        backgroundColor: 'rgba(46, 125, 50, 0.1)',
                        borderWidth: 2,
                        type: 'line',
                        tension: 0.4,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: { display: true, text: 'Bestellungen' },
                        beginAtZero: true
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: { display: true, text: 'Umsatz (€)' },
                        grid: { drawOnChartArea: false },
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // 3. Bestellstatus Verteilung
    if (orderChartData.order_status_distribution && orderChartData.order_status_distribution.length > 0) {
        const statusLabels = orderChartData.order_status_distribution.map(s => s.status_name);
        const statusData = orderChartData.order_status_distribution.map(s => s.count);
        
        new Chart(document.getElementById('orderStatusChart'), {
            type: 'doughnut',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusData,
                    backgroundColor: ['#4CAF50', '#2196F3', '#FF9800', '#9C27B0', '#607D8B', '#795548']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }

    // 4. Payment Methoden
    if (orderChartData.order_sources && orderChartData.order_sources.length > 0) {
        const paymentLabels = orderChartData.order_sources.map(s => s.payment_method);
        const paymentData = orderChartData.order_sources.map(s => s.count);
        
        new Chart(document.getElementById('paymentMethodsChart'), {
            type: 'pie',
            data: {
                labels: paymentLabels,
                datasets: [{
                    data: paymentData,
                    backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }

    // 5. Monatliche Bestellungen
    if (orderChartData.orders_monthly_12m && orderChartData.orders_monthly_12m.length > 0) {
        const monthlyMonths = orderChartData.orders_monthly_12m.map(m => m.month);
        const monthlyOrders = orderChartData.orders_monthly_12m.map(m => parseInt(m.total_orders));
        const monthlyRevenue = orderChartData.orders_monthly_12m.map(m => parseFloat(m.total_revenue));
        
        new Chart(document.getElementById('monthlyOrdersChart'), {
            type: 'bar',
            data: {
                labels: monthlyMonths,
                datasets: [
                    {
                        label: 'Bestellungen',
                        data: monthlyOrders,
                        backgroundColor: 'rgba(34, 113, 177, 0.6)',
                        borderColor: '#2271b1',
                        borderWidth: 1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Umsatz (€)',
                        data: monthlyRevenue,
                        borderColor: '#2e7d32',
                        backgroundColor: 'rgba(46, 125, 50, 0.1)',
                        borderWidth: 2,
                        type: 'line',
                        tension: 0.4,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: { display: true, text: 'Bestellungen' },
                        beginAtZero: true
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: { display: true, text: 'Umsatz (€)' },
                        grid: { drawOnChartArea: false },
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // 6. Bestellzeiten Heatmap (vereinfacht als Balkendiagramm)
    if (orderChartData.order_time_heatmap && orderChartData.order_time_heatmap.length > 0) {
        // Gruppiere nach Stunden
        const hourData = {};
        orderChartData.order_time_heatmap.forEach(item => {
            const hour = parseInt(item.hour);
            if (!hourData[hour]) hourData[hour] = 0;
            hourData[hour] += parseInt(item.orders);
        });
        
        const hours = Object.keys(hourData).sort((a, b) => a - b);
        const ordersByHour = hours.map(hour => hourData[hour]);
        
        new Chart(document.getElementById('orderTimeHeatmapChart'), {
            type: 'bar',
            data: {
                labels: hours.map(h => `${h}:00`),
                datasets: [{
                    label: 'Bestellungen',
                    data: ordersByHour,
                    backgroundColor: '#2196F3',
                    borderColor: '#1976D2',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Bestellungen' }
                    },
                    x: {
                        title: { display: true, text: 'Uhrzeit' }
                    }
                }
            }
        });
    }
});
</script>