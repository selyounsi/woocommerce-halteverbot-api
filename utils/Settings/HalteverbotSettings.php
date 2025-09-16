<?php

namespace Utils\Settings;

class HalteverbotSettings
{
    private static string $option_name = 'halteverbot_settings';

    private static array $defaults = [
        'modify_checkout' => false,
    ];

    public static function getSettings(): array
    {
        $saved = get_option(self::$option_name, []);
        return self::mergeOverride(self::$defaults, $saved);
    }

    public static function updateSettings(array $newSettings): bool
    {
        $merged = self::mergeOverride(self::$defaults, $newSettings);
        return update_option(self::$option_name, $merged);
    }

    private static function mergeOverride(array $defaults, array $saved): array
    {
        $merged = $defaults;

        foreach ($saved as $key => $value) {
            if (
                isset($defaults[$key]) &&
                is_array($defaults[$key]) &&
                is_array($value)
            ) {
                $merged[$key] = array_is_list($value)
                    ? $value
                    : self::mergeOverride($defaults[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }
}
