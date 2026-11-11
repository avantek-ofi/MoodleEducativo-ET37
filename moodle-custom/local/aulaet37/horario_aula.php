<?php
// This file is part of Aula ET37 - https://moodle.org/
//
// Aula ET37 is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Timetable of a classroom: everyone enrolled sees it; teachers and preceptors edit it and open
 * the personal timetable of each student.
 *
 * @package    local_aulaet37
 * @copyright  2026 Aula ET37
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_aulaet37\local\timetable;

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$slotid = optional_param('slotid', 0, PARAM_INT);

$course = get_course($id);
require_login($course);
$context = context_course::instance($course->id);
$canmanage = has_capability('local/aulaet37:managetimetable', $context);
$canviewothers = has_capability('local/aulaet37:viewtimetables', $context);

$url = new moodle_url('/local/aulaet37/horario_aula.php', ['id' => $course->id]);
$PAGE->set_url($url);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('coursetimetable', 'local_aulaet37') . ' | ' . format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->navbar->add(get_string('coursetimetable', 'local_aulaet37'), $url);

$groups = [];
foreach (groups_get_all_groups($course->id, 0, 0, 'g.id, g.name') as $group) {
    $groups[$group->id] = format_string($group->name, true, ['context' => $context]);
}

if ($action === 'delete' && $slotid) {
    require_capability('local/aulaet37:managetimetable', $context);
    $slot = $DB->get_record(timetable::TABLE, ['id' => $slotid, 'courseid' => $course->id], '*', MUST_EXIST);
    if (optional_param('confirm', 0, PARAM_BOOL) && confirm_sesskey()) {
        timetable::delete_slot((int) $slot->id, (int) $course->id);
        redirect($url, get_string('slotdeleted', 'local_aulaet37'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
    $summary = timetable::weekday_name((int) $slot->weekday) . ' ' . timetable::format_time((int) $slot->starttime) .
        ' a ' . timetable::format_time((int) $slot->endtime);
    echo $OUTPUT->header();
    echo $OUTPUT->confirm(get_string('slotdeleteconfirm', 'local_aulaet37', $summary),
        new moodle_url($url, ['action' => 'delete', 'slotid' => $slot->id, 'confirm' => 1, 'sesskey' => sesskey()]), $url);
    echo $OUTPUT->footer();
    exit;
}

if ($action === 'edit') {
    require_capability('local/aulaet37:managetimetable', $context);
    $form = new \local_aulaet37\form\slot_form(new moodle_url($url), ['groups' => $groups]);
    if ($form->is_cancelled()) {
        redirect($url);
    }
    if ($data = $form->get_data()) {
        $data->id = $data->slotid;
        $data->starttime = timetable::parse_time($data->startinput);
        $data->endtime = timetable::parse_time($data->endinput);
        timetable::save_slot($data, (int) $course->id);
        redirect($url, get_string('slotsaved', 'local_aulaet37'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
    $defaults = ['id' => $course->id, 'action' => 'edit', 'weekday' => 1, 'groupid' => 0];
    if ($slotid) {
        $slot = $DB->get_record(timetable::TABLE, ['id' => $slotid, 'courseid' => $course->id], '*', MUST_EXIST);
        $defaults = array_merge($defaults, [
            'slotid' => $slot->id,
            'weekday' => $slot->weekday,
            'groupid' => $slot->groupid,
            'startinput' => timetable::format_time((int) $slot->starttime),
            'endinput' => timetable::format_time((int) $slot->endtime),
            'room' => $slot->room,
            'notes' => $slot->notes,
        ]);
    }
    $form->set_data($defaults);
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string($slotid ? 'slotedit' : 'slotadd', 'local_aulaet37'), 2);
    $form->display();
    echo $OUTPUT->footer();
    exit;
}

$items = [];
foreach (timetable::get_course_slots((int) $course->id) as $slot) {
    $item = timetable::export_slot($slot, $course);
    if ($canmanage) {
        $item['editurl'] = (new moodle_url($url, ['action' => 'edit', 'slotid' => $slot->id]))->out(false);
        $item['deleteurl'] = (new moodle_url($url, ['action' => 'delete', 'slotid' => $slot->id]))->out(false);
    }
    $items[] = $item;
}

$students = [];
if ($canviewothers) {
    $studentroleid = $DB->get_field('role', 'id', ['shortname' => 'student']);
    if ($studentroleid) {
        $fields = 'u.id, ' . implode(', ', array_map(fn($field) => 'u.' . $field, \core_user\fields::get_name_fields()));
        foreach (get_role_users($studentroleid, $context, false, $fields, 'u.lastname, u.firstname') as $student) {
            $students[] = [
                'fullname' => fullname($student),
                'url' => (new moodle_url('/local/aulaet37/horario.php', ['userid' => $student->id]))->out(false),
            ];
        }
    }
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_aulaet37/course_timetable', [
    'days' => timetable::export_week($items),
    'hasitems' => !empty($items),
    'canmanage' => $canmanage,
    'addurl' => (new moodle_url($url, ['action' => 'edit']))->out(false),
    'myurl' => (new moodle_url('/local/aulaet37/horario.php'))->out(false),
    'canviewothers' => $canviewothers,
    'students' => $students,
    'hasstudents' => !empty($students),
]);
echo $OUTPUT->footer();
