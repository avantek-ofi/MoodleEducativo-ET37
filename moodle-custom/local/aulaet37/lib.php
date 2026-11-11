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
    \local_aulaet37\local\personal_data::require_on_signup($mform);
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
    return \local_aulaet37\local\profile_rules::validate((array) $data, null, true);
}

/**
 * Sends self-registered and Google users through the pending steps (personal data, photo).
 *
 * @param mixed $courseorid
 * @param bool $autologinguest
 * @param mixed $cm
 * @param bool $setwantsurltome
 * @param bool $preventredirect
 */
function local_aulaet37_after_require_login($courseorid = null, $autologinguest = null, $cm = null,
        $setwantsurltome = null, $preventredirect = null): void {
    \local_aulaet37\local\steps::enforce((bool) $preventredirect, (bool) $setwantsurltome);
}

/**
 * Adds "Class timetable" to profile pages when the viewer may see that person's timetable.
 *
 * @param \core_user\output\myprofile\tree $tree
 * @param stdClass $user
 * @param bool $iscurrentuser
 * @param stdClass|null $course
 */
function local_aulaet37_myprofile_navigation(\core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course): void {
    if (isguestuser($user) || !\local_aulaet37\local\timetable::can_view_user((int) $user->id)) {
        return;
    }
    $url = new moodle_url('/local/aulaet37/horario.php', $iscurrentuser ? [] : ['userid' => $user->id]);
    $tree->add_node(new \core_user\output\myprofile\node('miscellaneous', 'aulaet37timetable',
        get_string('timetable', 'local_aulaet37'), null, $url));
}

/**
 * Adds "Classroom timetable" to the course navigation ("More" menu of the classroom).
 *
 * @param navigation_node $parentnode
 * @param stdClass $course
 * @param context_course $context
 */
function local_aulaet37_extend_navigation_course(navigation_node $parentnode, stdClass $course, context_course $context): void {
    if ((int) $course->id === (int) SITEID || !isloggedin() || isguestuser()) {
        return;
    }
    $parentnode->add(get_string('coursetimetable', 'local_aulaet37'),
        new moodle_url('/local/aulaet37/horario_aula.php', ['id' => $course->id]),
        navigation_node::TYPE_CUSTOM, null, 'aulaet37timetable', new pix_icon('i/scheduled', ''));
}
