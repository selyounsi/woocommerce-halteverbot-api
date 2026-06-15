<?php

namespace Utils\Settings;

class HalteverbotOptions
{
    public static function isCheckoutModified(): bool
    {
        $settings = HalteverbotSettings::getSettings();
        return !empty($settings['modify_checkout']);
    }

    /**
     * Ist der Checkout-Hinweis aktiv? (Aktiviert + nicht leerer Text)
     */
    public static function isCheckoutNoticeEnabled(): bool
    {
        $settings = HalteverbotSettings::getSettings();
        return !empty($settings['checkout_notice_enabled'])
            && trim((string) ($settings['checkout_notice_text'] ?? '')) !== '';
    }

    public static function getCheckoutNoticeTitle(): string
    {
        $settings = HalteverbotSettings::getSettings();
        return (string) ($settings['checkout_notice_title'] ?? '');
    }

    public static function getCheckoutNoticeText(): string
    {
        $settings = HalteverbotSettings::getSettings();
        return (string) ($settings['checkout_notice_text'] ?? '');
    }
}
