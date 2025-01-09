<?php

namespace Utils;

use DateTime;

class DateUtils {

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
