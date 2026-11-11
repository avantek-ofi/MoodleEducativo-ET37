<?php
// This file is part of Aula ET37 - https://moodle.org/
//
// Aula ET37 is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_aulaet37\form;

use local_aulaet37\local\personal_data;
use local_aulaet37\local\profile_rules;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

/**
 * DNI and birth date form for accounts that have not provided them yet.
 *
 * Element names match the native profile fields so the same validation rules
 * (format, age range and Moodle's "unique" check) apply as on signup.
 *
 * @package    local_aulaet37
 * @copyright  2026 Aula ET37
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class personal_data_form extends \moodleform {
    /**
     * Form definition.
     */
    protected function definition(): void {
        $mform = $this->_form;
        $mform->addElement('text', personal_data::DNI_INPUT, get_string('field_dni', 'local_aulaet37'),
            ['maxlength' => 8, 'size' => 12, 'inputmode' => 'numeric', 'autocomplete' => 'off']);
        $mform->setType(personal_data::DNI_INPUT, PARAM_TEXT);
        $mform->addRule(personal_data::DNI_INPUT, get_string('dnirequired', 'local_aulaet37'), 'required', null, 'client');
        $mform->addElement('static', 'dnihelp', '', get_string('field_dni_desc', 'local_aulaet37'));
        personal_data::add_birthdate_selector($mform);
        $this->add_action_buttons(false, get_string('savedata', 'local_aulaet37'));
    }

    /**
     * Applies the school's rules and Moodle's uniqueness check.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        $errors += profile_rules::validate($data, null, true);
        if (empty($errors)) {
            $usernew = (object) $data;
            $usernew->id = (int) $this->_customdata['userid'];
            $errors += profile_validation($usernew, $files);
        }
        return $errors;
    }
}
