<?php

namespace Utils\Districts;

/**
 * Utility class for Berlin districts and their postal codes
 * including CC email lookup for WooCommerce email templates.
 */
class BerlinDistricts
{
    /**
     * Array of all districts and their postal codes.
     */
    private static array $districts = [
        'Berlin-Mitte' => [
            10115, 10117, 10119, 10178, 10179, 10551, 10553,
            10555, 10557, 10559, 10785, 10787, 13347, 13349,
            13351, 13353, 13355, 13357, 13359
        ],
        'Charlottenburg-Wilmersdorf' => [
            10585, 10587, 10589, 10623, 10625, 10627, 10629,
            10707, 10709, 10711, 10713, 10715, 10717, 10719,
            13627, 14050, 14052, 14053, 14055, 14057, 14059,
            14193, 14197, 14195, 14199
        ],
        'Friedrichshain-Kreuzberg' => [
            10243, 10245, 10247, 10249, 10961, 10963,
            10965, 10967, 10969, 10997, 10999
        ],
        'Lichtenberg' => [
            10315, 10317, 10318, 10319, 10365, 10367,
            10369, 13051, 13053, 13055, 13057, 13059
        ],
        'Marzahn-Hellersdorf' => [
            12619, 12621, 12623, 12627, 12629, 12679,
            12681, 12683, 12685, 12687, 12689
        ],
        'Neukölln' => [
            12043, 12045, 12047, 12049, 12051, 12053,
            12055, 12057, 12059, 12347, 12349, 12351,
            12353, 12355, 12357, 12359
        ],
        'Pankow' => [
            10405, 10407, 10409, 10435, 10437, 10439, 
            13086, 13088, 13089, 13125, 13127, 13129, 
            13156, 13158, 13159, 13187, 13189
        ],
        'Reinickendorf' => [
            13403, 13405, 13407, 13409, 13435, 13437, 13439,
            13465, 13467, 13469, 13503, 13505, 13507, 13509
        ],
        'Spandau' => [
            13581, 13583, 13585, 13587, 13589, 13591,
            13593, 13595, 13597, 13599, 13629, 14089
        ],
        'Steglitz-Zehlendorf' => [
            12157, 12161, 12163, 12165, 12167, 12169, 12203,
            12205, 12207, 12209, 12247, 12249, 14109, 14129,
            14163, 14165, 14167, 14169, 14193, 14195
        ],
        'Tempelhof-Schöneberg' => [
            10777, 10779, 10781, 10783, 10787, 10789,
            10823, 10825, 10827, 10829, 12099, 12101,
            12103, 12105, 12107, 12109, 12157, 12159,
            12161, 12277, 12279, 12305, 12307, 12309
        ],
        'Treptow-Köpenick' => [
            12435, 12437, 12439, 12459, 12487, 12489, 12524,
            12526, 12527, 12555, 12557, 12559, 12587, 12589
        ]
    ];

    /**
     * Option key used to store district → email mappings.
     */
    private const OPTION_KEY = 'wha_district_email_map';

    /**
     * Option key used to store district → email-type → email mappings (future-proof).
     */
    private const OPTION_KEY_TYPES = 'wha_district_email_map_types';

    /**
     * Get the stored CC email for a district.
     *
     * @param string $district
     * @return string|null
     */
    public static function getDistrictEmail(string $district): ?string
    {
        $map = get_option(self::OPTION_KEY, []);
        return $map[$district] ?? null;
    }

    /**
     * Get CC email based on district and WooCommerce email type.
     *
     * Fallback order:
     * 1. specific email-type (if stored)
     * 2. general district email (default CC)
     *
     * @param string $district
     * @param string|null $emailType
     * @return string|null
     */
    public static function getDistrictEmailByType(string $district, ?string $emailType = null): ?string
    {
        if ($emailType) {
            // Hole Option direkt für diesen email_type
            $map = get_option("wha_district_email_map_{$emailType}", []);
            if (isset($map[$district])) {
                return $map[$district];
            }
        }

        // fallback: default per-district CC
        return self::getDistrictEmail($district);
    }

    /**
     * Get the district name for a given postal code.
     *
     * @param int|string $zipCode
     * @return string|null
     */
    public static function getDistrictByZip(int|string $zipCode): ?string
    {
        $zipCode = (int) $zipCode;

        foreach (self::$districts as $district => $zips) {
            if (in_array($zipCode, $zips, true)) {
                return $district;
            }
        }
        return null;
    }

    /**
     * Get CC email by ZIP code.
     *
     * @param int|string $zip
     * @param string|null $emailType
     * @return string|null
     */
    public static function getCcEmailByZip(int|string $zip, ?string $emailType = null): ?string
    {
        $district = self::getDistrictByZip($zip);
        if (!$district) {
            return null;
        }

        return self::getDistrictEmailByType($district, $emailType);
    }

    /**
     * Check if a postal code belongs to a specific district.
     *
     * @param int|string $zipCode
     * @param string $districtName
     * @return bool
     */
    public static function isInDistrict(int|string $zipCode, string $districtName): bool
    {
        $zipCode = (int)$zipCode;
        return in_array($zipCode, self::$districts[$districtName] ?? [], true);
    }

    public static function filterByDistrict(array $zipCodes, string $districtName): array
    {
        return array_filter($zipCodes, fn($zip) => self::isInDistrict($zip, $districtName));
    }

    public static function getZipsByDistrict(string $districtName): array
    {
        return self::$districts[$districtName] ?? [];
    }

    public static function getAllDistricts(): array
    {
        return array_keys(self::$districts);
    }

    public static function isKnownZip(int|string $zipCode): bool
    {
        return self::getDistrictByZip($zipCode) !== null;
    }
}
