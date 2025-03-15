<?php

namespace Utils;

use DateTime;

class DateUtils {

    /**
     * Array of time type options.
     *
     * @return array[] Array of time type objects with 'value' and 'text' properties.
     */
    public static function getTimeTypes(): array
    {
        return [
            ["value" => "range", "text" => "Zeitraum"],
            ["value" => "day", "text" => "Tag/e"],
            ["value" => "week", "text" => "Woche/n"],
            ["value" => "month", "text" => "Monat/e"],
            ["value" => "year", "text" => "Jahr/e"]
        ];
    }

    /**
     * Function to calculate the number of days based on selected time type.
     *
     * @param string $type The type of time unit ('range', 'day', 'week', 'year', 'month').
     * @param string|null $start_date The start date in 'Y-m-d' format (only for 'range' type).
     * @param string|null $end_date The end date in 'Y-m-d' format (only for 'range' type).
     * @param int $time_duration The duration in the selected time type.
     * @return float The calculated number of days.
     */
    public static function calculateDays(string $type, ?string $start_date, ?string $end_date, int $time_duration): float
    {
        if ($type === 'range' && $start_date && $end_date) {
            $startDate = new DateTime($start_date);
            $endDate = new DateTime($end_date);
            $interval = $startDate->diff($endDate);
            return max($interval->days + 1, 0); // Ensure non-negative value
        }
        
        return match ($type) {
            'day' => $time_duration,
            'week' => $time_duration * 7,
            'month' => $time_duration * 30.42, // Average days in a month
            'year' => $time_duration * 365,
            default => 0,
        };
    }

    /**
     * Function to check the unit based on time type.
     *
     * @param string $type The time type value (e.g., 'day', 'week', 'year', etc.).
     * @return string The text representation of the time unit.
     */
    public static function checkTimeUnit(string $type): string
    {
        $timeTypes = self::getTimeTypes();
        foreach ($timeTypes as $option) {
            if ($option['value'] === $type) {
                return $option['text'];
            }
        }
        return $type;
    }

    /**
     * Konvertiert ein Datum von 'Y-m-d', 'Y-m-d H:i' oder 'Y-m-d H:i:s' in 'd.m.Y' oder 'd.m.Y H:i'.
     *
     * @param string $date Das ursprüngliche Datum im Format 'Y-m-d', 'Y-m-d H:i' oder 'Y-m-d H:i:s'.
     * @return string|null Das formatierte Datum im deutschen Format oder null, wenn die Konvertierung fehlschlägt.
     */
    public static function formatToGermanDate(string $date): ?string
    {
        // Mögliche Formate für die Eingabe
        $formats = ['Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d'];

        foreach ($formats as $format) {
            $dateTime = DateTime::createFromFormat($format, $date);

            if ($dateTime) {
                // Format entsprechend der Eingabe ausgeben
                return $dateTime->format(str_contains($date, ':') ? 'd.m.Y H:i' : 'd.m.Y');
            }
        }

        return $date;
    }
}
