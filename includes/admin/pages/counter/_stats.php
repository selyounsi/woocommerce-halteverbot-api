<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<?php
use Utils\Tracker\VisitorAnalytics;

$analyticsInstance = VisitorAnalytics::getAnalyticsInstance();

// Zeitraum-Logik
$timeframe = $_GET['timeframe'] ?? '7d';
$today = date('Y-m-d');

switch($timeframe) {
    case 'today':
        $start_date = $today;
        $end_date = $today;
        $period_label = 'Heute';
        break;
    case 'yesterday':
        $start_date = date('Y-m-d', strtotime('-1 day'));
        $end_date = $start_date;
        $period_label = 'Gestern';
        break;
    case '30d':
        $start_date = date('Y-m-d', strtotime('-29 days'));
        $end_date = $today;
        $period_label = 'Letzte 30 Tage';
        break;
    case '90d':
        $start_date = date('Y-m-d', strtotime('-89 days'));
        $end_date = $today;
        $period_label = 'Letzte 90 Tage';
        break;
    case 'month':
        $start_date = date('Y-m-01');
        $end_date = $today;
        $period_label = 'Dieser Monat';
        break;
    case 'last_month':
        $start_date = date('Y-m-01', strtotime('-1 month'));
        $end_date = date('Y-m-t', strtotime('-1 month'));
        $period_label = 'Letzter Monat';
        break;
    case 'year':
        $start_date = date('Y-01-01');
        $end_date = $today;
        $period_label = 'Dieses Jahr';
        break;
    case '7d':
    default:
        $start_date = date('Y-m-d', strtotime('-6 days'));
        $end_date = $today;
        $period_label = 'Letzte 7 Tage';
        break;
}

// Report mit dynamischem Zeitraum laden
$report = $analyticsInstance->get_report($start_date, $end_date);
?>

<div class="wp-list-table widefat fixed striped"> 
    <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 20px;">
        <h3 style="margin: 0;">Analytics Dashboard</h3>
        <div style="font-size: 14px; color: #666; background: #f8f9fa; padding: 8px 15px; border-radius: 20px;">
            📅 Zeigt Daten von <strong><?php echo date('d.m.Y', strtotime($start_date)); ?></strong> 
            bis <strong><?php echo date('d.m.Y', strtotime($end_date)); ?></strong> 
            (<?php echo $period_label; ?>)
        </div>
    </div>

    <div style="display: flex; gap: 2rem; min-height: 800px;">
        
        <!-- SIDEBAR NAVIGATION -->
        <div class="analytics-sidebar" style="width: 250px; background: #f8f9fa; border-radius: 8px; padding: 20px; height: fit-content;">
            <h3 style="margin-top: 0; color: #2271b1;">📊 Berichte</h3>
            
            <nav class="analytics-nav" style="display: flex; flex-direction: column; gap: 5px;">
                <button class="nav-tab active" data-tab="overview">
                    <span class="nav-icon">📈</span>
                    Übersicht
                </button>
                
                <button class="nav-tab" data-tab="visitors">
                    <span class="nav-icon">👥</span>
                    Besucher Analytics
                </button>
                
                <button class="nav-tab" data-tab="ecommerce">
                    <span class="nav-icon">🛒</span>
                    E-Commerce
                </button>
                
                <button class="nav-tab" data-tab="orders">
                    <span class="nav-icon">📦</span>
                    Bestellungen
                </button>
                
                <button class="nav-tab" data-tab="reviews">
                    <span class="nav-icon">⭐</span>
                    Bewertungen
                </button>
                
                <button class="nav-tab" data-tab="pages">
                    <span class="nav-icon">📄</span>
                    Seiten Analytics
                </button>
                
                <button class="nav-tab" data-tab="devices">
                    <span class="nav-icon">📱</span>
                    Geräte & Browser
                </button>
                
                <button class="nav-tab" data-tab="geo">
                    <span class="nav-icon">🌍</span>
                    Geodaten
                </button>
                
                <button class="nav-tab" data-tab="traffic">
                    <span class="nav-icon">🚦</span>
                    Traffic Quellen
                </button>
                
                <button class="nav-tab" data-tab="technical">
                    <span class="nav-icon">⚙️</span>
                    Technische Daten
                </button>
            </nav>

            <!-- Zeitraum Filter -->
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
                <h4 style="margin-bottom: 10px; color: #666;">⏰ Zeitraum</h4>
                <form method="GET" id="timeframe-form" style="margin-bottom: 15px;">
                    <input type="hidden" name="page" value="<?php echo $_GET['page'] ?? ''; ?>">
                    <select name="timeframe" id="timeframe-filter" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ccc; margin-bottom: 10px;" onchange="this.form.submit()">
                        <option value="today" <?php echo $timeframe === 'today' ? 'selected' : ''; ?>>Heute</option>
                        <option value="yesterday" <?php echo $timeframe === 'yesterday' ? 'selected' : ''; ?>>Gestern</option>
                        <option value="7d" <?php echo $timeframe === '7d' ? 'selected' : ''; ?>>Letzte 7 Tage</option>
                        <option value="30d" <?php echo $timeframe === '30d' ? 'selected' : ''; ?>>Letzte 30 Tage</option>
                        <option value="90d" <?php echo $timeframe === '90d' ? 'selected' : ''; ?>>Letzte 90 Tage</option>
                        <option value="month" <?php echo $timeframe === 'month' ? 'selected' : ''; ?>>Dieser Monat</option>
                        <option value="last_month" <?php echo $timeframe === 'last_month' ? 'selected' : ''; ?>>Letzter Monat</option>
                        <option value="year" <?php echo $timeframe === 'year' ? 'selected' : ''; ?>>Dieses Jahr</option>
                    </select>
                </form>
                
                <!-- Custom Date Range (erweiterbar) -->
                <details style="margin-top: 10px;">
                    <summary style="font-size: 12px; cursor: pointer; color: #666;">Benutzerdefiniert</summary>
                    <form method="GET" style="margin-top: 10px;">
                        <input type="hidden" name="page" value="<?php echo $_GET['page'] ?? ''; ?>">
                        <div style="margin-bottom: 8px;">
                            <label style="font-size: 11px; display: block;">Von:</label>
                            <input type="date" name="custom_start" style="width: 100%; padding: 4px; font-size: 11px;" 
                                   value="<?php echo $start_date; ?>">
                        </div>
                        <div style="margin-bottom: 8px;">
                            <label style="font-size: 11px; display: block;">Bis:</label>
                            <input type="date" name="custom_end" style="width: 100%; padding: 4px; font-size: 11px;"
                                   value="<?php echo $end_date; ?>">
                        </div>
                        <button type="submit" style="width: 100%; padding: 6px; background: #2271b1; color: white; border: none; border-radius: 3px; font-size: 11px;">
                            Anwenden
                        </button>
                    </form>
                </details>
            </div>

            <!-- Quick Stats -->
            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
                <h4 style="margin-bottom: 10px; color: #666;">📊 Quick Stats</h4>
                <div style="font-size: 12px; line-height: 1.4;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                        <span>Besucher:</span>
                        <strong><?php echo $report['total_visitors'] ?? 0; ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                        <span>Pageviews:</span>
                        <strong><?php echo $report['total_pageviews'] ?? 0; ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                        <span>Bestellungen:</span>
                        <strong><?php echo $report['wc_metrics']['last_7_days']['total_orders'] ?? 0; ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                        <span>Conversion:</span>
                        <strong><?php echo $report['wc_metrics']['last_7_days']['conversion_rate'] ?? 0; ?>%</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                        <span>Umsatz:</span>
                        <strong><?php echo number_format($report['wc_metrics']['last_7_days']['revenue'] ?? 0, 0, ',', '.'); ?> €</strong>
                    </div>
                </div>
            </div>

            <!-- Export Options -->
            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
                <h4 style="margin-bottom: 10px; color: #666;">📤 Export</h4>
                <div style="display: flex; flex-direction: column; gap: 5px;">
                    <button type="button" onclick="exportData('pdf')" style="padding: 8px; background: #dc3545; color: white; border: none; border-radius: 4px; font-size: 12px; cursor: pointer;">
                        📄 PDF Export
                    </button>
                    <button type="button" onclick="exportData('csv')" style="padding: 8px; background: #28a745; color: white; border: none; border-radius: 4px; font-size: 12px; cursor: pointer;">
                        📊 CSV Export
                    </button>
                </div>
            </div>
        </div>

        <!-- MAIN CONTENT AREA -->
        <div class="analytics-content" style="flex: 1;">
            
            <!-- Loading Indicator -->
            <div id="loading-indicator" style="display: none; text-align: center; padding: 20px;">
                <div style="font-size: 16px; color: #2271b1;">🔄 Lade Daten...</div>
            </div>
            
            <!-- OVERVIEW TAB -->
            <div class="tab-content active" id="overview-tab">
                <div class="dashboard-widgets-wrap" style="display: flex; gap: 1rem; flex-wrap:wrap">
                    <?php require __DIR__ . "/stats/globals-analytics.php"; ?>
                </div>
            </div>

            <!-- VISITORS TAB -->
            <div class="tab-content" id="visitors-tab">
                <div class="dashboard-widgets-wrap" style="display: flex; gap: 1rem; flex-wrap:wrap">
                    <?php require __DIR__ . "/stats/visitor-analytics.php"; ?>
                </div>
            </div>

            <!-- E-COMMERCE TAB -->
            <div class="tab-content" id="ecommerce-tab">
                <div class="dashboard-widgets-wrap" style="display: flex; gap: 1rem; flex-wrap:wrap">
                    <?php require __DIR__ . "/stats/woocommerce-analytics.php"; ?>
                </div>
            </div>

            <!-- ORDERS TAB -->
            <div class="tab-content" id="orders-tab">
                <div class="dashboard-widgets-wrap" style="display: flex; gap: 1rem; flex-wrap:wrap">
                    <?php require __DIR__ . "/stats/order-analytics.php"; ?>
                </div>
            </div>

            <!-- REVIEWS TAB -->
            <div class="tab-content" id="reviews-tab">
                <div class="dashboard-widgets-wrap" style="display: flex; gap: 1rem; flex-wrap:wrap">
                    <?php require __DIR__ . "/stats/reviews-analytics.php"; ?>
                </div>
            </div>

            <!-- PAGES TAB -->
            <div class="tab-content" id="pages-tab">
                <div class="dashboard-widgets-wrap" style="display: flex; gap: 1rem; flex-wrap:wrap">
                    <?php require __DIR__ . "/stats/pages-analytics.php"; ?>
                </div>
            </div>

            <!-- DEVICES TAB -->
            <div class="tab-content" id="devices-tab">
                <div class="dashboard-widgets-wrap" style="display: flex; gap: 1rem; flex-wrap:wrap">
                    <?php require __DIR__ . "/stats/devices-analytics.php"; ?>
                </div>
            </div>

            <!-- GEO TAB -->
            <div class="tab-content" id="geo-tab">
                <div class="dashboard-widgets-wrap" style="display: flex; gap: 1rem; flex-wrap:wrap">
                    <?php require __DIR__ . "/stats/geo-analytics.php"; ?>
                </div>
            </div>

            <!-- TRAFFIC TAB -->
            <div class="tab-content" id="traffic-tab">
                <div class="dashboard-widgets-wrap" style="display: flex; gap: 1rem; flex-wrap:wrap">
                    <?php require __DIR__ . "/stats/traffic-analytics.php"; ?>
                </div>
            </div>

            <!-- TECHNICAL TAB -->
            <div class="tab-content" id="technical-tab">
                <div class="dashboard-widgets-wrap" style="display: flex; gap: 1rem; flex-wrap:wrap">
                    <?php require __DIR__ . "/stats/misc-analytics.php"; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
.analytics-sidebar {
    position: sticky;
    top: 20px;
}

.analytics-nav {
    margin-bottom: 20px;
}

.analytics-nav .nav-tab {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 15px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: left;
    width: 100%;
    font-size: 14px;
    margin-bottom: 5px;
}

.analytics-nav .nav-tab:hover {
    background: #f1f1f1;
    border-color: #2271b1;
}

.analytics-nav .nav-tab.active {
    background: #2271b1;
    color: white;
    border-color: #2271b1;
}

.analytics-nav .nav-icon {
    font-size: 16px;
    width: 20px;
    text-align: center;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block !important;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Responsive Design */
@media (max-width: 1200px) {
    .analytics-sidebar {
        width: 220px;
    }
}

@media (max-width: 768px) {
    .analytics-container {
        flex-direction: column;
    }
    
    .analytics-sidebar {
        width: 100%;
        position: static;
    }
    
    .analytics-nav {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab Navigation with persistence
    const navTabs = document.querySelectorAll('.nav-tab');
    const tabContents = document.querySelectorAll('.tab-content');
    
    // Function to activate a specific tab
    function activateTab(tabName) {
        // Remove active class from all tabs and contents
        navTabs.forEach(t => t.classList.remove('active'));
        tabContents.forEach(c => c.classList.remove('active'));
        
        // Add active class to current tab and content
        const targetTab = document.querySelector(`[data-tab="${tabName}"]`);
        const targetContent = document.getElementById(`${tabName}-tab`);
        
        if (targetTab && targetContent) {
            targetTab.classList.add('active');
            targetContent.classList.add('active');
            
            // Save to localStorage and URL
            localStorage.setItem('analytics-active-tab', tabName);
            updateUrlParameter('tab', tabName);
        }
    }
    
    // Function to update URL parameter without page reload
    function updateUrlParameter(key, value) {
        const url = new URL(window.location);
        url.searchParams.set(key, value);
        window.history.replaceState({}, '', url);
    }
    
    // Function to get URL parameter
    function getUrlParameter(name) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(name);
    }
    
    // Tab click event
    navTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            activateTab(targetTab);
            
            // Refresh charts when switching tabs
            setTimeout(() => {
                if (typeof Chart !== 'undefined') {
                    window.dispatchEvent(new Event('resize'));
                }
            }, 100);
        });
    });
    
    // Determine which tab to show on page load
    function initializeActiveTab() {
        // Priority 1: URL parameter
        const urlTab = getUrlParameter('tab');
        
        // Priority 2: localStorage
        const savedTab = localStorage.getItem('analytics-active-tab');
        
        // Priority 3: Default tab
        const defaultTab = 'overview';
        
        const tabToActivate = urlTab || savedTab || defaultTab;
        activateTab(tabToActivate);
    }
    
    // Initialize
    initializeActiveTab();
    
    // Show loading indicator on form submit
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            document.getElementById('loading-indicator').style.display = 'block';
        });
    });
});

// Export functions
function exportData(format) {
    const timeframe = document.getElementById('timeframe-filter')?.value || '7d';
    
    let url = `<?php echo admin_url('admin-ajax.php'); ?>?action=export_analytics&format=${format}&timeframe=${timeframe}&nonce=<?php echo wp_create_nonce('analytics_export'); ?>`;
    
    if (format === 'pdf') {
        // PDF Export
        window.open(url, '_blank');
    } else {
        // CSV Download
        const link = document.createElement('a');
        link.href = url;
        link.download = `analytics-export-${timeframe}-${new Date().toISOString().split('T')[0]}.csv`;
        link.click();
    }
}

// Auto-refresh functionality
let autoRefreshInterval;
function toggleAutoRefresh(enable) {
    if (enable) {
        autoRefreshInterval = setInterval(() => {
            console.log('Auto-refreshing analytics...');
            // Page reload for simple implementation
            window.location.reload();
        }, 300000); // 5 minutes
    } else {
        clearInterval(autoRefreshInterval);
    }
}

// Initialize auto-refresh based on URL parameter
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('autorefresh') === '1') {
    toggleAutoRefresh(true);
}
</script>