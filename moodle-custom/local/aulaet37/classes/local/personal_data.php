<?php
// This file is part of Aula ET37 - https://moodle.org/
//
// Aula ET37 is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_aulaet37\local;

use MoodleQuickForm;
use stdClass;

/**
 * DNI and birth date requirement, stored in native custom profile fields.
 *
 * @package    local_aulaet37
 * @copyright  2026 Aula ET37
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class personal_data {
    /** Form element name of the DNI field. */
    public const DNI_INPUT = 'profile_field_' . profile_fields::DNI;

    /** Form element name of the birth date field. */
    public const BIRTHDATE_INPUT = 'profile_field_' . profile_fields::BIRTHDATE;

    /**
     * Current values of the user, or null when the fields do not exist.
     *
     * @param int $userid
     * @return array|null ['dni' => string, 'birthdate' => int]
     */
    public static function get_values(int $userid): ?array {
        global $DB;
        $sql = "SELECT f.shortname, d.data
                  FROM {user_info_field} f
             LEFT JOIN {user_info_data} d ON d.fieldid = f.id AND d.userid = :userid
                 WHERE f.shortname IN (:dni, :birthdate)";
        $rows = $DB->get_records_sql_menu($sql, [
            'userid' => $userid,
            'dni' => profile_fields::DNI,
            'birthdate' => profile_fields::BIRTHDATE,
        ]);
        if (!array_key_exists(profile_fields::DNI, $rows) || !array_key_exists(profile_fields::BIRTHDATE, $rows)) {
            return null;
        }
        return [
            'dni' => trim((string) $rows[profile_fields::DNI]),
            'birthdate' => (int) $rows[profile_fields::BIRTHDATE],
        ];
    }

    /**
     * Whether the user must complete DNI or birth date now.
     *
     * @param stdClass $user
     * @return bool
     */
    public static function is_required_for(stdClass $user): bool {
        global $SESSION;
        if (!steps::applies_to($user)) {
            return false;
        }
        if (isset($SESSION->local_aulaet37_datadone) && (int) $SESSION->local_aulaet37_datadone === (int) $user->id) {
            return false;
        }
        $values = self::get_values((int) $user->id);
        if ($values === null || ($values['dni'] !== '' && $values['birthdate'] > 0)) {
            $SESSION->local_aulaet37_datadone = (int) $user->id;
            return false;
        }
        return true;
    }

    /**
     * Saves both values through the native profile field API.
     *
     * @param int $userid
     * @param string $dni
     * @param int $birthdate
     */
    public static function save(int $userid, string $dni, int $birthdate): void {
        global $CFG, $SESSION;
        require_once($CFG->dirroot . '/user/profile/lib.php');
        profile_save_data((object) [
            'id' => $userid,
            self::DNI_INPUT => trim($dni),
            self::BIRTHDATE_INPUT => $birthdate,
        ]);
        $SESSION->local_aulaet37_datadone = $userid;
        \core\event\user_updated::create_from_userid($userid)->trigger();
    }

    /**
     * Adds a birth date selector without the "Enable" checkbox Moodle shows for
     * optional date fields, plus the DNI required rule.
     *
     * @param MoodleQuickForm $mform
     * @param string $before Element to insert the selector before, or '' to append.
     */
    public static function add_birthdate_selector(MoodleQuickForm $mform, string $before = ''): void {
        global $DB;
        $field = $DB->get_record('user_info_field', ['shortname' => profile_fields::BIRTHDATE], 'name, param1, param2');
        $options = [
            'startyear' => $field ? (int) $field->param1 : profile_fields::START_YEAR,
            'stopyear' => $field && (int) $field->param2 ? (int) $field->param2 : (int) date('Y'),
            'optional' => false,
        ];
        $label = $field ? format_string($field->name) : get_string('field_birthdate', 'local_aulaet37');
        $element = $mform->createElement('date_selector', self::BIRTHDATE_INPUT, $label, $options);
        if ($before !== '' && $mform->elementExists($before)) {
            $mform->insertElementBefore($element, $before);
        } else {
            $mform->addElement($element);
        }
    }

    /**
     * Makes both fields mandatory on the signup form.
     *
     * @param MoodleQuickForm $mform
     */
    public static function require_on_signup(MoodleQuickForm $mform): void {
        if ($mform->elementExists(self::DNI_INPUT)) {
            $mform->addRule(self::DNI_INPUT, get_string('dnirequired', 'local_aulaet37'), 'required', null, 'client');
        }
        if (!$mform->elementExists(self::BIRTHDATE_INPUT)) {
            return;
        }
        // Find the element that follows the optional selector, replace it in place.
        $index = $mform->_elementIndex[self::BIRTHDATE_INPUT];
        $next = '';
        $total = count($mform->_elements);
        for ($i = $index + 1; $i < $total; $i++) {
            if (isset($mform->_elements[$i]) && ($name = $mform->_elements[$i]->getName())) {
                $next = $name;
                break;
            }
        }
        $mform->removeElement(self::BIRTHDATE_INPUT);
        // Drop the disabledIf rule the optional selector registered on its checkbox.
        unset($mform->_dependencies[self::BIRTHDATE_INPUT . '[enabled]']);
        self::add_birthdate_selector($mform, $next);
    }
}
