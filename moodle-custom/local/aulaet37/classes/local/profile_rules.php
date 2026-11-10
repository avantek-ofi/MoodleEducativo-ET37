<?php
// This file is part of Aula ET37 - https://moodle.org/
//
// Aula ET37 is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_aulaet37\local;

/**
 * Validation rules for the school's custom profile fields.
 *
 * @package    local_aulaet37
 * @copyright  2026 Aula ET37
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class profile_rules {
    /** Minimum age accepted at registration. */
    public const MIN_AGE = 10;

    /** Maximum age accepted at registration. */
    public const MAX_AGE = 100;

    /**
     * Validates submitted DNI and birth date values.
     *
     * Uniqueness of the DNI is enforced by Moodle's native "unique" profile field option.
     *
     * @param array $data Submitted form data.
     * @param int|null $now Current timestamp, for tests.
     * @return array Errors keyed by element name.
     */
    public static function validate(array $data, ?int $now = null): array {
        $errors = [];
        $now = $now ?? time();

        $dnikey = 'profile_field_' . profile_fields::DNI;
        if (array_key_exists($dnikey, $data)) {
            $dni = trim((string) $data[$dnikey]);
            if ($dni !== '' && !self::is_valid_dni($dni)) {
                $errors[$dnikey] = get_string('invaliddni', 'local_aulaet37');
            }
        }

        $birthkey = 'profile_field_' . profile_fields::BIRTHDATE;
        if (array_key_exists($birthkey, $data)) {
            $birth = (int) $data[$birthkey];
            if (!self::is_valid_birthdate($birth, $now)) {
                $errors[$birthkey] = get_string('invalidbirthdate', 'local_aulaet37', [
                    'min' => self::MIN_AGE,
                    'max' => self::MAX_AGE,
                ]);
            }
        }

        return $errors;
    }

    /**
     * Checks a DNI written with digits only.
     *
     * @param string $dni
     * @return bool
     */
    public static function is_valid_dni(string $dni): bool {
        return (bool) preg_match('/^[1-9]\d{6,7}$/', $dni);
    }

    /**
     * Checks that a birth date is in the past and within the accepted age range.
     *
     * @param int $birth Timestamp.
     * @param int $now Timestamp.
     * @return bool
     */
    public static function is_valid_birthdate(int $birth, int $now): bool {
        if ($birth <= 0 || $birth >= $now) {
            return false;
        }
        $birthdate = (new \DateTimeImmutable('@' . $birth))->setTimezone(\core_date::get_server_timezone_object());
        $today = (new \DateTimeImmutable('@' . $now))->setTimezone(\core_date::get_server_timezone_object());
        $age = $birthdate->diff($today)->y;
        return $age >= self::MIN_AGE && $age <= self::MAX_AGE;
    }
}
