<?php
    /*
        Plugin Name: Halteverbot App API
        Description: Plugin zur Verarbeitung von Halteverbot-Daten mit WooCommerce-Integration.
        Version: 1.0.0
        Author: Halteverbotsservice Berlin
        Requires Plugins: woocommerce, bp-custom-order-status-for-woocommerce, woocommerce-pdf-invoices-packing-slips
        License: GPL-2.0+
    */

    /**
     * Verhindert direkten Zugriff
     */
    if (!defined('ABSPATH')) {
        exit;
    }

    // Composer-Autoloader einbinden
    $vendor_autoload = plugin_dir_path(__FILE__) . 'vendor/autoload.php';
    if (file_exists($vendor_autoload)) {
        require_once $vendor_autoload;
    }

    /**
     * Define a constant for the plugin root path
     */
    if (!defined('WHA_PLUGIN_PATH')) {
        define('WHA_PLUGIN_PATH', plugin_dir_path(__FILE__));
    }

    /**
     * Define a constant for the plugin url
     */
    if (!defined('WHA_PLUGIN_ASSETS_URL')) {
        define('WHA_PLUGIN_ASSETS_URL', plugin_dir_url(__FILE__) . 'assets');
    }

    /**
     * Define the translation key for the plugin
     */
    if (!defined('WHA_TRANSLATION_KEY')) {
        define('WHA_TRANSLATION_KEY', 'woocommerce-halteverbot-api');
    }

    /**
     * Define route path
     */
    if (!defined('WHA_ROUTE_PATH')) {
        define('WHA_ROUTE_PATH', 'wc/v3');
    }

    /**
     * Define upload path
     */
    if (!defined('WHA_UPLOAD_PATH')) {
        define('WHA_UPLOAD_PATH', 'WHA/');
    }

    /**
     * Funktion zur Überprüfung der Abhängigkeiten
     */
    function halteverbot_check_dependencies() 
    {
        $required_plugins = [
            'woocommerce/woocommerce.php' => 'WooCommerce',
            'bp-custom-order-status-for-woocommerce/main.php' => 'Custom Order Status Manager for WooCommerce'
        ];

        $missing_plugins = [];
        foreach ($required_plugins as $plugin_file => $plugin_name) {
            if (!is_plugin_active($plugin_file)) {
                $missing_plugins[] = $plugin_name;
            }
        }

        // Wenn ein benötigtes Plugin fehlt, Fehlermeldung anzeigen und Plugin deaktivieren
        if (!empty($missing_plugins)) {
            add_action('admin_notices', function() use ($missing_plugins) {
                $plugin_names = implode(', ', $missing_plugins);
                echo '<div class="notice notice-error"><p>';
                echo sprintf(
                    __('Das Halteverbot App API Plugin benötigt die folgenden aktiven Plugins: %s.', 'halteverbot-app-api'),
                    esc_html($plugin_names)
                );
                echo '</p></div>';
            });

            // Plugin deaktivieren
            deactivate_plugins(plugin_basename(__FILE__));
        }
    }
    add_action('admin_init', 'halteverbot_check_dependencies');

    /**
     * Funktion zum Laden der App-Daten
     */
    function load_app_data() {
        // Pfad zum data-Verzeichnis
        $data_directory = plugin_dir_path(__FILE__) . 'data/';

        // Prefix festlegen (z.B. "exclude_" oder ein anderer Wert)
        $prefix_to_exclude = 'exclude_';

        // Unterordner im data-Verzeichnis durchsuchen und PHP-Dateien einbinden
        $directories = array_filter(glob($data_directory . '*'), 'is_dir');
        foreach ($directories as $dir) {
            $files = glob($dir . '/*.php');
            foreach ($files as $file) {
                // Überprüfen, ob der Dateiname mit dem angegebenen Prefix beginnt
                $filename = basename($file);
                if (strpos($filename, $prefix_to_exclude) !== 0) {
                    // Datei einbinden, wenn der Prefix nicht vorhanden ist
                    require_once $file;
                }
            }
        }
    }

    /**
     * Funktion zum Laden aller Hook-Dateien aus dem hooks/-Ordner
     */
    function wha_load_hooks() {
        $hooks_dir = plugin_dir_path(__FILE__) . 'hooks/';

        if (!is_dir($hooks_dir)) {
            return; // Ordner existiert nicht
        }

        foreach (glob($hooks_dir . '*.php') as $hook_file) {
            require_once $hook_file;
        }
    }
    wha_load_hooks();

    // Include die Dateien für das Admin-Menü
    require_once plugin_dir_path(__FILE__) . 'includes/admin/register.php';

    // Include die Dateien für das Admin-Menü
    require_once plugin_dir_path(__FILE__) . 'includes/frontend/register.php';

    // Dateien laden, wenn WordPress initialisiert wird
    add_action('init', 'load_app_data');


    /**
     * 🕒 Verbesserte automatische Token-Wartung
     */
    add_action('plugins_loaded', function() {
        if (!class_exists('\Utils\Tracker\Google\GoogleSearchConsole')) {
            return;
        }

        // 🔁 WP-Cron-Event stündlich ausführen für bessere Wartung
        if (!wp_next_scheduled('wha_gsc_token_maintenance')) {
            wp_schedule_event(time(), 'hourly', 'wha_gsc_token_maintenance');
        }

        add_action('wha_gsc_token_maintenance', function() {
            try {
                $gsc = \Utils\Tracker\Google\GoogleSearchConsole::getInstance();
                
                if (!$gsc->isAuthenticated()) {
                    return; // Nicht authentifiziert, nichts zu tun
                }
                
                $tokenStatus = $gsc->getTokenStatus();
                
                if (!$tokenStatus['valid'] && $tokenStatus['has_refresh_token']) {
                    error_log('[Halteverbot GSC] Token-Wartung: Token erneuern notwendig');
                    $result = $gsc->getValidToken(); // Erzwingt Refresh
                    
                    if ($result['success']) {
                        error_log('[Halteverbot GSC] Token-Wartung: Erfolgreich erneuert');
                    } else {
                        error_log('[Halteverbot GSC] Token-Wartung: Fehler - ' . $result['error']);
                    }
                } else {
                    error_log('[Halteverbot GSC] Token-Wartung: Token noch gültig für ' . $tokenStatus['time_to_expiry']);
                }
                
            } catch (Exception $e) {
                error_log('[Halteverbot GSC] Token-Wartung Fehler: ' . $e->getMessage());
            }
        });
    });

?>
