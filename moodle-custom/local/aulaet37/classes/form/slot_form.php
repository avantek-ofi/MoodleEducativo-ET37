<?php
// This file is part of Aula ET37 - https://moodle.org/
//
// Aula ET37 is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_aulaet37\form;

use local_aulaet37\local\timetable;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * One class block of a classroom timetable.
 *
 * Custom data: groups (id => name) of the course.
 *
 * @package    local_aulaet37
 * @copyright  2026 Aula ET37
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class slot_form extends \moodleform {
    /**
     * Form definition.
     */
    protected function definition(): void {
        $mform = $this->_form;
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'slotid');
        $mform->setType('slotid', PARAM_INT);
        $mform->addElement('hidden', 'action', 'edit');
        $mform->setType('action', PARAM_ALPHA);

        $mform->addElement('select', 'weekday', get_string('slotweekday', 'local_aulaet37'), timetable::weekday_options());
        $mform->setType('weekday', PARAM_INT);

        foreach (['startinput' => 'slotstart', 'endinput' => 'slotend'] as $name => $label) {
            $mform->addElement('text', $name, get_string($label, 'local_aulaet37'),
                ['size' => 6, 'maxlength' => 5, 'placeholder' => '07:45', 'autocomplete' => 'off']);
            $mform->setType($name, PARAM_TEXT);
            $mform->addRule($name, get_string('required'), 'required', null, 'client');
        }
        $mform->addElement('static', 'timehelp', '', get_string('slottimehelp', 'local_aulaet37'));

        $groups = [0 => get_string('slotallgroups', 'local_aulaet37')] + ($this->_customdata['groups'] ?? []);
        $mform->addElement('select', 'groupid', get_string('slotgroup', 'local_aulaet37'), $groups);
        $mform->setType('groupid', PARAM_INT);
        $mform->addHelpButton('groupid', 'slotgroup', 'local_aulaet37');

        $mform->addElement('text', 'room', get_string('slotroom', 'local_aulaet37'), ['size' => 30, 'maxlength' => 100]);
        $mform->setType('room', PARAM_TEXT);
        $mform->addElement('text', 'notes', get_string('slotnotes', 'local_aulaet37'), ['size' => 50, 'maxlength' => 255]);
        $mform->setType('notes', PARAM_TEXT);

        $this->add_action_buttons(true, get_string('savechanges'));
    }

    /**
     * Validates times and the group.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        $start = timetable::parse_time((string) ($data['startinput'] ?? ''));
        $end = timetable::parse_time((string) ($data['endinput'] ?? ''));
        if ($start === null) {
            $errors['startinput'] = get_string('slotinvalidtime', 'local_aulaet37');
        }
        if ($end === null) {
            $errors['endinput'] = get_string('slotinvalidtime', 'local_aulaet37');
        }
        if ($start !== null && $end !== null) {
            if ($end <= $start) {
                $errors['endinput'] = get_string('slotendbeforestart', 'local_aulaet37');
            } else if ($start < timetable::DAY_START || $end > timetable::DAY_END) {
                $errors['startinput'] = get_string('slotoutofday', 'local_aulaet37', (object) [
                    'from' => timetable::format_time(timetable::DAY_START),
                    'to' => timetable::format_time(timetable::DAY_END),
                ]);
            }
        }
        if (!in_array((int) ($data['weekday'] ?? 0), timetable::WEEKDAYS, true)) {
            $errors['weekday'] = get_string('required');
        }
        $groupid = (int) ($data['groupid'] ?? 0);
        if ($groupid && !array_key_exists($groupid, $this->_customdata['groups'] ?? [])) {
            $errors['groupid'] = get_string('required');
        }
        return $errors;
    }
}
