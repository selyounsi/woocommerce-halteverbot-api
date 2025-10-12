<!-- BEWERTUNGS ANALYTICS -->
<div class="postbox" style="width: 100%; flex-basis: 100%">
    <div class="inside">
        <h2 class="hndle" style="margin-bottom: 20px;"><span>⭐ Bewertungs Analytics</span></h2>
        
        <!-- Bewertungs Übersicht -->
        <div style="display: flex; gap: 1rem; margin-bottom: 20px; flex-wrap: wrap;">
            <?php 
            $reviews_stats = $report['reviews_metrics']['stats'] ?? [];
            $rating_distribution = $report['reviews_metrics']['rating_distribution'] ?? [];
            ?>
            
            <!-- Gesamt-Statistiken -->
            <div style="flex: 1; min-width: 200px; padding: 15px; background: #fff3cd; border-radius: 5px; border-left: 4px solid #ffc107;">
                <h3 style="margin: 0 0 15px 0; color: #856404;">📊 Gesamtstatistik</h3>
                <table style="width: 100%;">
                    <tr>
                        <td>Gesamt Bewertungen:</td>
                        <td style="text-align: right; font-weight: bold;"><?php echo $reviews_stats['total_reviews'] ?? 0; ?></td>
                    </tr>
                    <tr>
                        <td>Durchschnittliche Bewertung:</td>
                        <td style="text-align: right; font-weight: bold;">
                            <?php echo number_format($reviews_stats['avg_rating'] ?? 0, 1); ?> ⭐
                        </td>
                    </tr>
                    <tr>
                        <td>Bewertungen mit Text:</td>
                        <td style="text-align: right; font-weight: bold;"><?php echo $reviews_stats['reviews_with_text'] ?? 0; ?></td>
                    </tr>
                    <tr>
                        <td>Angezeigt:</td>
                        <td style="text-align: right; font-weight: bold;"><?php echo $reviews_stats['shown_reviews'] ?? 0; ?></td>
                    </tr>
                </table>
            </div>

            <!-- Sterne-Verteilung -->
            <div style="flex: 1; min-width: 250px; padding: 15px; background: #e8f5e8; border-radius: 5px; border-left: 4px solid #28a745;">
                <h3 style="margin: 0 0 15px 0; color: #155724;">⭐ Sterne-Verteilung</h3>
                <table style="width: 100%;">
                    <?php foreach ($rating_distribution as $rating): ?>
                        <tr>
                            <td>
                                <?php echo str_repeat('⭐', $rating['rating']); ?> 
                                (<?php echo $rating['rating']; ?> Sterne):
                            </td>
                            <td style="text-align: right; font-weight: bold;">
                                <?php echo $rating['count']; ?> (<?php echo $rating['percentage']; ?>%)
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <!-- Bewertungs-Quellen -->
            <div style="flex: 1; min-width: 200px; padding: 15px; background: #e3f2fd; border-radius: 5px; border-left: 4px solid #2196F3;">
                <h3 style="margin: 0 0 15px 0; color: #0d47a1;">🔍 Top Quellen</h3>
                <?php if (!empty($report['reviews_metrics']['sources'])): ?>
                    <table style="width: 100%; font-size: 12px;">
                        <?php foreach (array_slice($report['reviews_metrics']['sources'], 0, 5) as $source): ?>
                            <tr>
                                <td><?php echo esc_html($source['source']); ?></td>
                                <td style="text-align: right; font-weight: bold;">
                                    <?php echo $source['count']; ?> (Ø <?php echo number_format($source['avg_rating'], 1); ?>⭐)
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #666;">Keine Quellen-Daten</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Bewertungs Charts -->
        <div style="display: flex; gap: 1rem; margin-bottom: 20px; flex-wrap: wrap;">
            <!-- Sterne-Verteilung Chart -->
            <div style="flex: 1; min-width: 400px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h3 style="margin: 0 0 15px 0;">📊 Sterne-Verteilung (Diesen Monat)</h3>
                <div style="height: 300px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                    <canvas id="ratingDistributionChart"></canvas>
                </div>
            </div>

            <!-- Bewertungs-Trends -->
            <div style="flex: 1; min-width: 400px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h3 style="margin: 0 0 15px 0;">📈 Bewertungs-Trends (6 Monate)</h3>
                <div style="height: 300px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                    <canvas id="ratingTrendsChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Tägliche & Monatliche Bewertungen -->
        <div style="display: flex; gap: 1rem; margin-bottom: 20px; flex-wrap: wrap;">
            <!-- Tägliche Bewertungen (30 Tage) -->
            <div style="flex: 1; min-width: 400px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h3 style="margin: 0 0 15px 0;">📅 Tägliche Bewertungen (30 Tage)</h3>
                <div style="height: 300px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                    <canvas id="dailyReviews30dChart"></canvas>
                </div>
            </div>

            <!-- Tägliche Bewertungen (7 Tage) -->
            <div style="flex: 1; min-width: 400px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h3 style="margin: 0 0 15px 0;">📅 Tägliche Bewertungen (7 Tage)</h3>
                <div style="height: 300px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                    <canvas id="dailyReviews7dChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Monatliche Bewertungen -->
        <div style="display: flex; gap: 1rem; margin-bottom: 20px; flex-wrap: wrap;">
            <!-- Monatliche Übersicht -->
            <div style="flex: 1; min-width: 400px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h3 style="margin: 0 0 15px 0;">📅 Monatliche Bewertungen (12 Monate)</h3>
                <div style="height: 300px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                    <canvas id="monthlyReviewsChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Top Bewertungen -->
        <div class="postbox" style="width: 100%; flex-basis: 100%">
            <div class="inside">
                <h2 class="hndle" style="margin-bottom: 10px;"><span>🏆 Top Bewertungen (diesen Monat)</span></h2>
                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Bewertung</th>
                            <th>Text</th>
                            <th>Quelle</th>
                            <th>Datum</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($report['reviews_metrics']['top_reviews'])): ?>
                            <?php foreach ($report['reviews_metrics']['top_reviews'] as $review): ?>
                                <tr>
                                    <td style="width: 100px;">
                                        <div style="font-size: 18px; color: #ffc107;">
                                            <?php echo str_repeat('⭐', $review['rating']); ?>
                                        </div>
                                        <div style="font-size: 12px; color: #666;">
                                            <?php echo $review['rating']; ?> Sterne
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 500;">
                                            <?php echo esc_html($review['review_text']); ?>
                                        </div>
                                        <?php if ($review['order_id']): ?>
                                            <div style="font-size: 11px; color: #666; margin-top: 2px;">
                                                Bestellung #<?php echo $review['order_id']; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="width: 150px;">
                                        <?php echo esc_html($review['referral_source']); ?>
                                    </td>
                                    <td style="width: 120px;">
                                        <?php echo date('d.m.Y', strtotime($review['created_at'])); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center;">Keine Bewertungen mit Text verfügbar</td>
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

    const chartData = <?php echo json_encode($report['reviews_metrics']['chart_data'] ?? []); ?>;      
    
    // 1. Sterne-Verteilung Chart
    if (chartData.rating_distribution && chartData.rating_distribution.length > 0) {
        const ratingLabels = chartData.rating_distribution.map(r => `${r.rating} Sterne`);
        const ratingData = chartData.rating_distribution.map(r => r.count);
        const ratingColors = ['#ff6b6b', '#ffa726', '#ffee58', '#9ccc65', '#66bb6a'];
        
        new Chart(document.getElementById('ratingDistributionChart'), {
            type: 'doughnut',
            data: {
                labels: ratingLabels,
                datasets: [{
                    data: ratingData,
                    backgroundColor: ratingColors,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.raw / total) * 100).toFixed(1);
                                return `${context.label}: ${context.raw} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
    
    // 2. Bewertungs-Trends Chart (6 Monate)
    if (chartData.rating_trends_6m && chartData.rating_trends_6m.length > 0) {
        const trendsMonths = chartData.rating_trends_6m.map(t => t.month);
        const trendsRatings = chartData.rating_trends_6m.map(t => parseFloat(t.avg_rating));
        const trendsCount = chartData.rating_trends_6m.map(t => parseInt(t.review_count));
        
        new Chart(document.getElementById('ratingTrendsChart'), {
            type: 'line',
            data: {
                labels: trendsMonths,
                datasets: [
                    {
                        label: 'Durchschnittliche Bewertung',
                        data: trendsRatings,
                        borderColor: '#ffc107',
                        backgroundColor: 'rgba(255, 193, 7, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        yAxisID: 'y',
                        fill: true
                    },
                    {
                        label: 'Anzahl Bewertungen',
                        data: trendsCount,
                        borderColor: '#2196F3',
                        backgroundColor: 'rgba(33, 150, 243, 0.1)',
                        borderWidth: 2,
                        type: 'bar',
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
                        min: 1,
                        max: 5,
                        title: {
                            display: true,
                            text: 'Durchschnittliche Bewertung'
                        },
                        ticks: {
                            stepSize: 0.5
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Anzahl Bewertungen'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
    }
    
    // 3. Tägliche Bewertungen (30 Tage)
    if (chartData.reviews_daily_30d) {
        const dailyDates30d = Object.keys(chartData.reviews_daily_30d);
        const dailyReviews30d = dailyDates30d.map(date => chartData.reviews_daily_30d[date].reviews);
        const dailyRatings30d = dailyDates30d.map(date => chartData.reviews_daily_30d[date].avg_rating);
        
        new Chart(document.getElementById('dailyReviews30dChart'), {
            type: 'bar',
            data: {
                labels: dailyDates30d,
                datasets: [
                    {
                        label: 'Anzahl Bewertungen',
                        data: dailyReviews30d,
                        backgroundColor: 'rgba(33, 150, 243, 0.6)',
                        borderColor: '#2196F3',
                        borderWidth: 1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Ø Bewertung',
                        data: dailyRatings30d,
                        borderColor: '#ffc107',
                        backgroundColor: 'rgba(255, 193, 7, 0.1)',
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
                        title: {
                            display: true,
                            text: 'Anzahl Bewertungen'
                        },
                        beginAtZero: true
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Ø Bewertung'
                        },
                        min: 1,
                        max: 5,
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
    }
    
    // 4. Tägliche Bewertungen (7 Tage)
    if (chartData.reviews_daily_7d) {
        const dailyDates7d = Object.keys(chartData.reviews_daily_7d);
        const dailyReviews7d = dailyDates7d.map(date => chartData.reviews_daily_7d[date].reviews);
        const dailyRatings7d = dailyDates7d.map(date => chartData.reviews_daily_7d[date].avg_rating);
        
        new Chart(document.getElementById('dailyReviews7dChart'), {
            type: 'bar',
            data: {
                labels: dailyDates7d,
                datasets: [
                    {
                        label: 'Anzahl Bewertungen',
                        data: dailyReviews7d,
                        backgroundColor: 'rgba(76, 175, 80, 0.6)',
                        borderColor: '#4CAF50',
                        borderWidth: 1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Ø Bewertung',
                        data: dailyRatings7d,
                        borderColor: '#ff9800',
                        backgroundColor: 'rgba(255, 152, 0, 0.1)',
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
                        title: {
                            display: true,
                            text: 'Anzahl Bewertungen'
                        },
                        beginAtZero: true
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Ø Bewertung'
                        },
                        min: 1,
                        max: 5,
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
    }
    
    // 5. Monatliche Bewertungen (12 Monate)
    if (chartData.reviews_monthly_12m && chartData.reviews_monthly_12m.length > 0) {
        const monthlyMonths = chartData.reviews_monthly_12m.map(m => m.month);
        const monthlyReviews = chartData.reviews_monthly_12m.map(m => parseInt(m.total_reviews));
        const monthlyPositive = chartData.reviews_monthly_12m.map(m => parseInt(m.positive_reviews));
        
        new Chart(document.getElementById('monthlyReviewsChart'), {
            type: 'bar',
            data: {
                labels: monthlyMonths,
                datasets: [
                    {
                        label: 'Gesamt Bewertungen',
                        data: monthlyReviews,
                        backgroundColor: 'rgba(156, 39, 176, 0.6)',
                        borderColor: '#9C27B0',
                        borderWidth: 1
                    },
                    {
                        label: 'Positive Bewertungen (4-5⭐)',
                        data: monthlyPositive,
                        backgroundColor: 'rgba(76, 175, 80, 0.6)',
                        borderColor: '#4CAF50',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Anzahl Bewertungen'
                        }
                    }
                }
            }
        });
    }

    });
</script>