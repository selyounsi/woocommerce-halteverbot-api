<!-- VISITOR ANALYTICS CHARTS -->
<div class="postbox" style="width: 100%; flex-basis: 100%">
    <div class="inside">
        <h2 class="hndle" style="margin-bottom: 20px;"><span>📊 Visitor Analytics Charts</span></h2>
        
        <!-- Zeitleiste Charts -->
        <div style="display: flex; gap: 1rem; margin-bottom: 20px; flex-wrap: wrap;">
            <!-- Tägliche Besucher (30 Tage) -->
            <div style="flex: 1; min-width: 400px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h3 style="margin: 0 0 15px 0;">📈 Tägliche Besucher (30 Tage)</h3>
                <div style="height: 300px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                    <canvas id="dailyVisitorsChart30d"></canvas>
                </div>
            </div>
            
            <!-- Tägliche Besucher (7 Tage) -->
            <div style="flex: 1; min-width: 400px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h3 style="margin: 0 0 15px 0;">📈 Tägliche Besucher (7 Tage)</h3>
                <div style="height: 300px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                    <canvas id="dailyVisitorsChart7d"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Verteilungs-Charts -->
        <div style="display: flex; gap: 1rem; margin-bottom: 20px; flex-wrap: wrap;">
            <!-- Geräteverteilung -->
            <div style="flex: 1; min-width: 300px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h3 style="margin: 0 0 15px 0;">📱 Geräteverteilung</h3>
                <div style="height: 250px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                    <canvas id="deviceDistributionChart"></canvas>
                </div>
            </div>
            
            <!-- Browser-Verteilung -->
            <div style="flex: 1; min-width: 300px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h3 style="margin: 0 0 15px 0;">🌐 Browser-Verteilung</h3>
                <div style="height: 250px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                    <canvas id="browserDistributionChart"></canvas>
                </div>
            </div>
            
            <!-- Traffic-Quellen -->
            <div style="flex: 1; min-width: 300px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h3 style="margin: 0 0 15px 0;">🚦 Traffic-Quellen</h3>
                <div style="height: 250px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                    <canvas id="trafficSourcesChart"></canvas>
                </div>
            </div>

            <!-- Suchmaschinen-Verteilung -->
            <div style="flex: 1; min-width: 300px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h3 style="margin: 0 0 15px 0;">🔍 Suchmaschinen</h3>
                <div style="height: 250px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                    <canvas id="searchEngineDistributionChart"></canvas>
                </div>
            </div>

        </div>
        
        <!-- Heatmap und Karten -->
        <div style="display: flex; gap: 1rem; margin-bottom: 20px; flex-wrap: wrap;">
            <!-- Besuchszeiten Heatmap -->
            <div style="flex: 1; min-width: 400px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h3 style="margin: 0 0 15px 0;">🕒 Besuchszeiten Heatmap</h3>
                <div style="height: 300px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                    <canvas id="visitHeatmapChart"></canvas>
                </div>
            </div>
            
            <!-- Top Deutsche Städte -->
            <div style="flex: 1; min-width: 400px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h3 style="margin: 0 0 15px 0;">🗺️ Top Deutsche Städte</h3>
                <div style="height: 300px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                    <canvas id="germanCitiesChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Seiten-Performance -->
        <div style="display: flex; gap: 1rem; margin-bottom: 20px; flex-wrap: wrap;">
            <!-- Seiten Performance -->
            <div style="flex: 1; min-width: 400px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h3 style="margin: 0 0 15px 0;">📄 Seiten Performance</h3>
                <div style="height: 300px; background: white; border: 1px solid #ddd; border-radius: 4px; padding: 10px;">
                    <canvas id="pagePerformanceChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const chartData = <?php echo json_encode($report['visitor_metrics']['chart_data'] ?? []); ?>;

    // 1. Tägliche Besucher (7-30 Tage)
    function createDailyVisitorsChart(canvasId, data, title) {
        if (!data || Object.keys(data).length === 0) {
            document.getElementById(canvasId).innerHTML = 
                `<div style="display: flex; justify-content: center; align-items: center; height: 100%; color: #666;">
                    Keine Daten verfügbar für ${title}
                </div>`;
            return;
        }

        const dates = Object.keys(data);
        const visitors = dates.map(date => data[date].visitors || 0);
        const pageviews = dates.map(date => data[date].page_views || 0);

        new Chart(document.getElementById(canvasId), {
            type: 'line',
            data: {
                labels: dates,
                datasets: [
                    {
                        label: 'Besucher',
                        data: visitors,
                        borderColor: '#2271b1',
                        backgroundColor: 'rgba(34, 113, 177, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Page Views',
                        data: pageviews,
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
                plugins: {
                    title: {
                        display: true,
                        text: title,
                        font: {
                            size: 14,
                            weight: 'bold'
                        }
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: { 
                            display: true, 
                            text: 'Besucher' 
                        },
                        beginAtZero: true
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: { 
                            display: true, 
                            text: 'Page Views' 
                        },
                        grid: { 
                            drawOnChartArea: false 
                        },
                        beginAtZero: true
                    }
                },
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                }
            }
        });
    }

    // 1. Tägliche Besucher (30 Tage)
    createDailyVisitorsChart(
        'dailyVisitorsChart30d', 
        chartData.daily_visitors_30d,
        'Tägliche Besucher (30 Tage)'
    );
    
    // 2. Tägliche Besucher (7 Tage)
    createDailyVisitorsChart(
        'dailyVisitorsChart7d', 
        chartData.daily_visitors_7d,
        'Tägliche Besucher (7 Tage)'
    );
    
    // 2. Geräteverteilung
    if (chartData.device_distribution) {
        new Chart(document.getElementById('deviceDistributionChart'), {
            type: 'doughnut',
            data: {
                labels: chartData.device_distribution.map(d => d.device_type),
                datasets: [{
                    data: chartData.device_distribution.map(d => d.count),
                    backgroundColor: ['#2196F3', '#4CAF50', '#FF9800', '#9C27B0', '#607D8B']
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
    
    // 3. Browser-Verteilung
    if (chartData.browser_distribution) {
        new Chart(document.getElementById('browserDistributionChart'), {
            type: 'pie',
            data: {
                labels: chartData.browser_distribution.map(d => d.browser_name),
                datasets: [{
                    data: chartData.browser_distribution.map(d => d.count),
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

    // Suchmaschinen-Verteilung
    if (chartData.search_engine_distribution) {
        new Chart(document.getElementById('searchEngineDistributionChart'), {
            type: 'pie',
            data: {
                labels: chartData.search_engine_distribution.map(d => d.search_engine),
                datasets: [{
                    data: chartData.search_engine_distribution.map(d => d.count),
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
    
    // 4. Traffic-Quellen
    if (chartData.traffic_sources) {
        new Chart(document.getElementById('trafficSourcesChart'), {
            type: 'doughnut',
            data: {
                labels: chartData.traffic_sources.map(d => d.source_label),
                datasets: [{
                    data: chartData.traffic_sources.map(d => d.count),
                    backgroundColor: ['#4CAF50', '#2196F3', '#FF9800', '#9C27B0']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { 
                        position: 'bottom'
                    }
                }
            }
        });
    }
    
    // 5. Top Deutsche Städte
    if (chartData.german_cities) {
        new Chart(document.getElementById('germanCitiesChart'), {
            type: 'bar',
            data: {
                labels: chartData.german_cities.map(d => d.city),
                datasets: [{
                    label: 'Besucher',
                    data: chartData.german_cities.map(d => d.count),
                    backgroundColor: '#2196F3'
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
    
    // 6. Seiten Performance
    if (chartData.page_performance) {
        new Chart(document.getElementById('pagePerformanceChart'), {
            type: 'bar',
            data: {
                labels: chartData.page_performance.map(d => 
                    d.page_title.length > 30 ? d.page_title.substring(0, 30) + '...' : d.page_title
                ),
                datasets: [
                    {
                        label: 'Aufrufe',
                        data: chartData.page_performance.map(d => d.views),
                        backgroundColor: '#2196F3',
                        yAxisID: 'y'
                    },
                    {
                        label: 'Ø Verweildauer (s)',
                        data: chartData.page_performance.map(d => Math.round(d.avg_time_on_page)),
                        type: 'line',
                        borderColor: '#FF9800',
                        backgroundColor: 'rgba(255, 152, 0, 0.1)',
                        borderWidth: 2,
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
                        title: { display: true, text: 'Aufrufe' }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: { display: true, text: 'Verweildauer (s)' },
                        grid: { drawOnChartArea: false }
                    }
                }
            }
        });
    }

    // 7. Horizontales Balkendiagramm für Heatmap
    if (chartData.visit_heatmap && chartData.visit_heatmap.length > 0) {
        const daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        const germanDays = {
            'Monday': 'Montag',
            'Tuesday': 'Dienstag', 
            'Wednesday': 'Mittwoch',
            'Thursday': 'Donnerstag',
            'Friday': 'Freitag',
            'Saturday': 'Samstag',
            'Sunday': 'Sonntag'
        };
        
        // Gruppiere Daten nach Tagen
        const dayData = daysOfWeek.map(day => {
            const dayVisits = chartData.visit_heatmap
                .filter(item => item.day === day)
                .reduce((sum, item) => sum + parseInt(item.visits), 0);
            
            return {
                day: germanDays[day],
                totalVisits: dayVisits,
                hours: chartData.visit_heatmap
                    .filter(item => item.day === day)
                    .map(item => ({
                        hour: parseInt(item.hour),
                        visits: parseInt(item.visits)
                    }))
            };
        });

        // Sortiere nach Gesamtbesuchen (absteigend)
        dayData.sort((a, b) => b.totalVisits - a.totalVisits);

        new Chart(document.getElementById('visitHeatmapChart'), {
            type: 'bar',
            data: {
                labels: dayData.map(d => d.day),
                datasets: [{
                    label: 'Gesamt Besuche pro Tag',
                    data: dayData.map(d => d.totalVisits),
                    backgroundColor: '#2196F3',
                    borderColor: '#1976D2',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y', // Horizontale Balken
                scales: {
                    x: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Gesamt Besuche'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Wochentag'
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            afterBody: function(context) {
                                const dayIndex = context[0].dataIndex;
                                const day = dayData[dayIndex];
                                const topHours = day.hours
                                    .sort((a, b) => b.visits - a.visits)
                                    .slice(0, 3)
                                    .map(h => `${h.hour}:00 (${h.visits} Besuche)`)
                                    .join('\n');
                                
                                return ['\nTop Zeiten:', topHours];
                            }
                        }
                    }
                }
            }
        });
        
    } else {
        document.getElementById('visitHeatmapChart').innerHTML = 
            '<div style="display: flex; justify-content: center; align-items: center; height: 100%; color: #666;">Keine Heatmap-Daten verfügbar</div>';
    }
});
</script>