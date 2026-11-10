<?php
// This file is part of Aula ET37 - https://moodle.org/
//
// Aula ET37 is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_aulaet37\local;

/**
 * Creates the native custom profile fields used at registration.
 *
 * Existing fields are never overwritten, so administrators can adjust them from
 * Site administration > Users > User profile fields.
 *
 * @package    local_aulaet37
 * @copyright  2026 Aula ET37
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class profile_fields {
    /** Short name of the DNI field. */
    public const DNI = 'dni';

    /** Short name of the birth date field. */
    public const BIRTHDATE = 'fechanacimiento';

    /** First selectable year for the birth date. */
    public const START_YEAR = 1930;

    /**
     * Ensures the category and fields exist.
     *
     * @return string[] Short names of the fields created in this call.
     */
    public static function ensure(): array {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/user/profile/lib.php');
        require_once($CFG->dirroot . '/user/profile/definelib.php');

        $created = [];
        $categoryid = self::ensure_category();

        if (!$DB->record_exists('user_info_field', ['shortname' => self::DNI])) {
            profile_save_field((object) [
                'shortname' => self::DNI,
                'name' => get_string('field_dni', 'local_aulaet37'),
                'datatype' => 'text',
                'description' => ['text' => get_string('field_dni_desc', 'local_aulaet37'), 'format' => FORMAT_HTML],
                'categoryid' => $categoryid,
                'required' => 1,
                'locked' => 0,
                'visible' => PROFILE_VISIBLE_PRIVATE,
                'forceunique' => 1,
                'signup' => 1,
                'defaultdata' => '',
                'defaultdataformat' => FORMAT_MOODLE,
                'param1' => 12,
                'param2' => 8,
                'param3' => 0,
                'param4' => '',
                'param5' => '',
            ], []);
            $created[] = self::DNI;
        }

        if (!$DB->record_exists('user_info_field', ['shortname' => self::BIRTHDATE])) {
            $year = (int) date('Y');
            profile_save_field((object) [
                'shortname' => self::BIRTHDATE,
                'name' => get_string('field_birthdate', 'local_aulaet37'),
                'datatype' => 'datetime',
                'description' => ['text' => '', 'format' => FORMAT_HTML],
                'categoryid' => $categoryid,
                'required' => 1,
                'locked' => 0,
                'visible' => PROFILE_VISIBLE_PRIVATE,
                'forceunique' => 0,
                'signup' => 1,
                'defaultdata' => 0,
                'defaultdataformat' => FORMAT_MOODLE,
                // The datetime definition expects the year selectors of its admin form.
                'param1' => self::START_YEAR,
                'startyear' => self::START_YEAR,
                'startmonth' => 1,
                'startday' => 1,
                'param2' => $year,
                'endyear' => $year,
                'endmonth' => 1,
                'endday' => 1,
                'param3' => 0,
            ], []);
            $created[] = self::BIRTHDATE;
        }

        return $created;
    }

    /**
     * Returns the id of the "Datos personales" category, creating it if needed.
     *
     * @return int
     */
    protected static function ensure_category(): int {
        global $DB;
        $categoryid = (int) get_config('local_aulaet37', 'profilecategoryid');
        if ($categoryid && $DB->record_exists('user_info_category', ['id' => $categoryid])) {
            return $categoryid;
        }
        $category = (object) ['name' => get_string('profilecategory', 'local_aulaet37')];
        profile_save_category($category);
        set_config('profilecategoryid', $category->id, 'local_aulaet37');
        return (int) $category->id;
    }
}
