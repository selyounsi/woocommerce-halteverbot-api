<?php

namespace Utils;

class ReviewsSettings
{
    private static string $option_name = 'halteverbot_reviews_settings';

    private static array $defaults = [
        'enabled' => false,
        'status' => [

        ],
        'card_rating' => [
            'headline' => 'Wie hat Ihnen unser Service gefallen?',
            'show_textarea' => true,
            'textarea_placeholder' => 'Ihr Feedback …',
            'submit_button_text' => 'Absenden',
            'referral_source' => [
                "Google-Suche",
                "Social Media (Facebook, Instagram, etc.)",
                "Empfehlung von Freunden/Bekannten",
                "Vorbeigelaufen / Vor Ort gesehen",
                "Flyer / Plakat",
                "Online-Werbung",
                "Bewertungsplattform (z. B. Tripadvisor)",
                "Unsere Website",
                "Sonstiges"
            ]
        ],
        'card_google' => [
            'headline' => 'Vielen Dank für Ihre Bewertung!',
            'text' => 'Wenn Sie mit unserem Service zufrieden waren, würden wir uns über eine Bewertung auf Google freuen.',
            'button_link' => 'https://g.page/r/CUSTOMER_REVIEW_LINK',
            'button_text' => 'Auf Google bewerten',
        ],
        'card_end' => [
            'headline' => 'Vielen Dank!',
            'text' => 'Ihre Bewertung wurde erfolgreich übermittelt.',
        ],
    ];

    /**
     * Holt die gemergten Einstellungen
     */
    public static function getSettings(): array
    {
        $saved = get_option(self::$option_name, []);
        return self::mergeOverride(self::$defaults, $saved);
    }

    /**
     * Aktualisiert die Einstellungen
     */
    public static function updateSettings(array $newSettings): bool
    {
        $merged = self::mergeOverride(self::$defaults, $newSettings);
        return update_option(self::$option_name, $merged);
    }

    /**
     * Prüft, ob Bewertungen aktiviert sind
     */
    public static function isEnabled(): bool
    {
        $settings = self::getSettings();
        return !empty($settings['enabled']);
    }

    /**
     * Gibt alle in den Plugin-Einstellungen gespeicherten Bestellstatus zurück
     */
    public static function getAllOrderStatuses(): array
    {
        $settings = self::getSettings();

        return !empty($settings['status']) && is_array($settings['status'])
            ? $settings['status']
            : [];
    }

    /**
     * Rekursives Merging: Werte aus $saved überschreiben $defaults vollständig
     */
    private static function mergeOverride(array $defaults, array $saved): array {
        $merged = $defaults;

        foreach ($saved as $key => $value) {
            if (
                isset($defaults[$key]) &&
                is_array($defaults[$key]) &&
                is_array($value)
            ) {
                // Wenn es ein numerischer Array ist → komplett ersetzen
                if (array_is_list($value)) {
                    $merged[$key] = $value;
                } else {
                    // Assoziativer Array → rekursiv mergen
                    $merged[$key] = self::mergeOverride($defaults[$key], $value);
                }
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }
}
