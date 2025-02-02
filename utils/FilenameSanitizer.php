<?php

namespace Utils;

/**
 * Class FilenameSanitizer
 *
 * A helper class for sanitizing strings into valid filenames.
 * It replaces special characters, umlauts, and spaces with safe equivalents.
 */
class FilenameSanitizer 
{
    /**
     * Sanitize a string into a valid filename.
     *
     * @param string $startdate The start date (e.g. '2025-01-29')
     * @param string $enddate The end date (e.g. '2025-01-29')
     * @param string $address The address (e.g. 'Schottmüllerstr. 92')
     * @return string The sanitized filename string
     */
    public static function sanitize($startdate, $enddate, $address) {
        // Map of characters to replace (umlauts and space)
        $replace = [
            'ä' => 'ae',
            'ö' => 'oe',
            'ü' => 'ue',
            'ß' => 'ss',
            'Ä' => 'Ae',
            'Ö' => 'Oe',
            'Ü' => 'Ue',
            ' ' => '_', // Replace space with underscore
        ];

        // Combine the values into one string
        $filename = "{$startdate} {$enddate} {$address}";

        // Replace characters based on the defined replacements
        $filename = strtr($filename, $replace);

        // Remove any characters that are not alphanumeric, underscore, hyphen, or period
        $filename = preg_replace('/[^a-zA-Z0-9-_\.]/', '', $filename);

        return $filename;
    }
}