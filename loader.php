<?php
    /*
        Plugin Name: Halteverbot App API
        Description: Plugin zur Verarbeitung von Halteverbot-Daten mit WooCommerce-Integration.
        Version: 1.0.0
        Author: Halteverbotsservice Berlin
        Requires Plugins: woocommerce, bp-custom-order-status-for-woocommerce
        License: GPL-2.0+
    */

    /**
     * Verhindert direkten Zugriff
     */
    if (!defined('ABSPATH')) {
        exit;
    }

    /**
     * Define a constant for the plugin root path
     */
    if (!defined('HALTEVERBOT_APP_API_PATH')) {
        define('HALTEVERBOT_APP_API_PATH', plugin_dir_path(__FILE__));
    }

    /**
     * Define the translation key for the plugin
     */
    if (!defined('WHA_TRANSLATION_KEY')) {
        define('WHA_TRANSLATION_KEY', 'woocommerce-halteverbot-api');
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
        
        // Composer-Autoloader einbinden
        $vendor_autoload = plugin_dir_path(__FILE__) . 'vendor/autoload.php';
        if (file_exists($vendor_autoload)) {
            require_once $vendor_autoload;
        }

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

    // Include die Dateien für das Admin-Menü
    require_once plugin_dir_path(__FILE__) . 'includes/admin/admin-menu.php';

    // Include die Dateien für das Admin-Menü
    require_once plugin_dir_path(__FILE__) . 'includes/frontend/register-pages.php';

    // Dateien laden, wenn WordPress initialisiert wird
    add_action('init', 'load_app_data');
?>
