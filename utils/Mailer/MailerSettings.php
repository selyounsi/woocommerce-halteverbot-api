<?php

namespace Utils\Mailer;

/**
 * Kleine Persistenz für Mailer-Einstellungen (aktuell nur die Test-Adresse
 * für den Sandbox-Modus). Fällt auf die Admin-E-Mail zurück.
 */
class MailerSettings
{
    private static string $option = 'wha_mailer_settings';

    public static function getTestAddress(): string
    {
        $settings = get_option(self::$option, []);
        $address  = is_array($settings) ? (string) ($settings['test_address'] ?? '') : '';

        return $address !== '' ? $address : (string) get_option('admin_email');
    }

    public static function setTestAddress(string $address): void
    {
        $settings = get_option(self::$option, []);
        if (!is_array($settings)) {
            $settings = [];
        }

        $settings['test_address'] = sanitize_email($address);
        update_option(self::$option, $settings);
    }
}
