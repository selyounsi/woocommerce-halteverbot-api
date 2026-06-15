<?php

use Utils\Districts\BerlinDistricts;

if (!defined('ABSPATH')) {
    exit;
}

// BezirkDateManager nur auf relevanten Seiten laden
add_action('wp_enqueue_scripts', function () {
    // Nur auf Produkt-Seiten laden wo das Formular existiert
    if (is_product() && 1 === 2) {
        
        // BezirkDateManager JS einbinden
        wp_enqueue_script(
            'bezirk-date-manager',
            WHA_PLUGIN_ASSETS_URL . '/js/BezirkDateManager.js',
            ['jquery'],
            '1.0.6',
            true
        );

        // Bezirks-Daten von PHP nach JS übertragen
        $districts_data = [];
        
        // Hole alle Bezirke aus der BerlinDistricts Klasse
        $all_districts = BerlinDistricts::getAllDistricts();
        
        foreach ($all_districts as $district_name) {
            $districts_data[$district_name] = BerlinDistricts::getZipsByDistrict($district_name);
        }

        // Daten an JS übergeben
        wp_localize_script('bezirk-date-manager', 'BezirkDateManagerData', [
            'districts' => $districts_data,
            'districtsConfig' => [
                'Charlottenburg-Wilmersdorf' => [
                    [
                        'fieldId' => "field_date-7667899242", // Startdatum
                        'config' => [ 
                            'minDate' => "fp_incr(14)", // 14 Tage Vorlauf
                        ]
                    ],
                    [
                        'fieldId' => "field_date-6267767576", // Enddatum  
                        'config' => [
                            'minDate' => "fp_incr(15)", // 15 Tage Vorlauf
                        ]
                    ]
                ]
            ],
            'filterFieldId' => 'field_text_0600722025' // PLZ Feld
        ]);

        // Initialisierungscode für BezirkDateManager
        wp_add_inline_script('bezirk-date-manager', "
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof BezirkDateManager !== 'undefined' && window.BezirkDateManagerData) {
                    const bezirkManager = new BezirkDateManager(
                        window.BezirkDateManagerData.districts,
                        window.BezirkDateManagerData.districtsConfig, 
                        window.BezirkDateManagerData.filterFieldId
                    );
                    // Optional: Für Debugging global verfügbar machen
                    window.bezirkManager = bezirkManager;
                }
            });
        ");
    }
}, 20);