<?php
    /*
    Plugin Name: Halteverbot App API
    Description: Plugin zur Verarbeitung von Halteverbot-Daten mit WooCommerce-Integration.
    Version: 1.0.0
    Author: Dein Name
    License: GPL-2.0+
    */

    // Verhindert direkten Zugriff
    if (!defined('ABSPATH')) {
        exit;
    }


    // Definiere eine Konstante für den Plugin-Root-Pfad
    if (!defined('HALTEVERBOT_APP_API_PATH')) {
        define('HALTEVERBOT_APP_API_PATH', plugin_dir_path(__FILE__)); // Root-Pfad des Plugins
    }

    // Funktion zum Laden der App-Daten
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

    // Dateien laden, wenn WordPress initialisiert wird
    add_action('init', 'load_app_data');
?>
