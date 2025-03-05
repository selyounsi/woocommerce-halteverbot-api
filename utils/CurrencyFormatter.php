<?php

namespace Utils;

class CurrencyFormatter
{
    public static function formatEuro($value): string
    {
        // Stelle sicher, dass der Wert numerisch ist
        $number = (float)str_replace(',', '.', preg_replace('/[^0-9,.-]/', '', $value));

        // Formatieren und als Euro zurückgeben
        return number_format($number, 2, ',', '.') . ' €';
    }
}
