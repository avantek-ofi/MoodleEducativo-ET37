<?php
// This file is part of Aula ET37 - https://moodle.org/
//
// Aula ET37 is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Callbacks used by Moodle core.
 *
 * @package    local_aulaet37
 * @copyright  2026 Aula ET37
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Adapts the native self-registration form to the school's requested fields.
 *
 * Name, surname and email are native fields; DNI and birth date are native custom
 * profile fields shown on signup. Optional location fields are removed.
 *
 * @param MoodleQuickForm $mform
 */
function local_aulaet37_extend_signup_form($mform): void {
    foreach (['city', 'country'] as $name) {
        if ($mform->elementExists($name)) {
            $mform->removeElement($name);
        }
    }
    if ($mform->elementExists('username')) {
        $mform->addHelpButton('username', 'signupusername', 'local_aulaet37');
    }
    if (\local_aulaet37\local\photo::is_enabled()) {
        $mform->addElement('static', 'aulaet37photonotice', get_string('photo', 'local_aulaet37'),
            get_string('signupphotonotice', 'local_aulaet37'));
    }
}

/**
 * Validates DNI and birth date on the signup form.
 *
 * @param array $data Submitted data.
 * @return array Errors keyed by element name.
 */
function local_aulaet37_validate_extend_signup_form($data): array {
    return \local_aulaet37\local\profile_rules::validate((array) $data);
}

/**
 * Requires self-registered users to upload a profile photo before using the site.
 *
 * @param mixed $courseorid
 * @param bool $autologinguest
 * @param mixed $cm
 * @param bool $setwantsurltome
 * @param bool $preventredirect
 */
function local_aulaet37_after_require_login($courseorid = null, $autologinguest = null, $cm = null,
        $setwantsurltome = null, $preventredirect = null): void {
    \local_aulaet37\local\photo::enforce((bool) $preventredirect, (bool) $setwantsurltome);
}
