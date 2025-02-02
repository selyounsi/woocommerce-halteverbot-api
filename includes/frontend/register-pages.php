<?php
    // Füge eine Rewrite-Regel für die Order-Details-Seite hinzu
    function register_order_details_page() {
        add_rewrite_rule(
            '^order-details/?$', // URL-Struktur für die Seite
            'index.php?order_details=1', // Query-Variable
            'top'
        );
    }
    add_action('init', 'register_order_details_page');

    // Definiere benutzerdefinierte Query-Variable
    function add_order_details_query_var($vars) {
        $vars[] = 'order_details'; // Die benutzerdefinierte Query-Variable
        return $vars;
    }
    add_filter('query_vars', 'add_order_details_query_var');

    // Template für die Order-Details-Seite zuweisen
    function order_details_template($template) {
        if (get_query_var('order_details')) {
            $template_path = __DIR__ . '/page-order-details.php'; // Dein Template-Ordner
            if (file_exists($template_path)) {
                $template = $template_path; // Template zuweisen
            }
        }
        return $template;
    }
    add_filter('template_include', 'order_details_template');
