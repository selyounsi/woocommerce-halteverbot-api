<?php

namespace Utils\Settings;

class HalteverbotOptions
{
    public static function isCheckoutModified(): bool
    {
        $settings = HalteverbotSettings::getSettings();
        return !empty($settings['modify_checkout']);
    }
}
