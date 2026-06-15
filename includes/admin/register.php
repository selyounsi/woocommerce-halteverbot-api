<?php
// Sicherheit: Direktes Aufrufen von PHP-Dateien verhindern
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Hauptmenü und Submenüs hinzufügen
function halteverbot_app_add_menu() {
    // Hauptmenü
    add_menu_page(
        'Halteverbot App',
        'Halteverbot App',
        'manage_options',
        'halteverbot-app',
        function () {
            include('pages/main.php');
        },
        'dashicons-car',
        2                        
    );

    // Submenü: Direkt auf die interne Seite 'order_status' verweisen
    add_submenu_page(
        'halteverbot-app',
        'Order Status',
        'Order Status',
        'manage_options',
        'halteverbot-app-order-status',
        function () {
            $order_status_url = admin_url( 'edit.php?post_type=order_status' );
            wp_redirect( $order_status_url );
            exit;
        }
    );

    // Submenü: E-Mail Vorlagen (nur benutzerdefinierte Status)
    add_submenu_page(
        'halteverbot-app',
        'E-Mail Vorlagen',
        'E-Mail Vorlagen',
        'manage_options',
        'halteverbot-app-email-templates',
        function () {
            include('pages/email-templates.php');
        }
    );

    // Submenü 1: Halteverbotszonen verwalten
    add_submenu_page(
        'halteverbot-app',
        'Einstellungen',
        'Einstellungen',
        'manage_options',
        'halteverbot-app-settings',
        function () {
            include('pages/settings.php');
        }
    );

    // Submenü 1: Halteverbotszonen verwalten
    add_submenu_page(
        'halteverbot-app',
        'WebCounter',
        'WebCounter',
        'manage_options',
        'halteverbot-app-counter',
        function () {
            include('pages/counter/index.php');
        }
    );

    // Submenü: E-Mail Versand (Newsletter / Sammelmails)
    // Hinweis: Die Seite bleibt registriert und per URL erreichbar
    // (admin.php?page=halteverbot-app-mailer) – sie wird weiter unten nur aus
    // dem Menü ausgeblendet, solange das Feature noch nicht freigegeben ist.
    add_submenu_page(
        'halteverbot-app',
        'E-Mail Versand',
        'E-Mail Versand',
        'manage_woocommerce',
        'halteverbot-app-mailer',
        function () {
            include('pages/mailer.php');
        }
    );

    // 🔒 E-Mail Versand vorübergehend aus dem Menü ausblenden
    // (noch nicht für Kunden sichtbar). Zum Anzeigen diese Zeile entfernen:
    remove_submenu_page('halteverbot-app', 'halteverbot-app-mailer');

    // Submenü 1: Halteverbotszonen verwalten
    add_submenu_page(
        'halteverbot-app',
        'Bewertungen',
        'Bewertungen',
        'manage_options',
        'halteverbot-app-reviews',
        function () {
            include('pages/reviews.php');
        }
    );

    // Submenü 1: Halteverbotszonen verwalten
    add_submenu_page(
        'halteverbot-app',
        'Bewertungs-Setup',
        'Bewertungs-Setup',
        'manage_options',
        'halteverbot-app-reviews-settings',
        function () {
            include('pages/reviews-settings.php');
        }
    );
}
add_action('admin_menu', 'halteverbot_app_add_menu');