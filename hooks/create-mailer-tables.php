<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Legt die Mailer-Tabellen an bzw. aktualisiert sie (analog zu den anderen
 * Tabellen-Hooks des Plugins).
 */
function wha_create_mailer_tables()
{
    if (!class_exists('\Utils\Mailer\MailerLog')) {
        return;
    }

    $log = new \Utils\Mailer\MailerLog();
    $log->createTables();
    $log->updateTables();
}

add_action('admin_init', 'wha_create_mailer_tables');
