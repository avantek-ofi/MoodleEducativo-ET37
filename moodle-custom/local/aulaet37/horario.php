<?php
// This file is part of Aula ET37 - https://moodle.org/
//
// Aula ET37 is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Personal weekly class timetable: own, or another person's for preceptors, teachers and directors.
 *
 * @package    local_aulaet37
 * @copyright  2026 Aula ET37
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_aulaet37\local\timetable;

require_once(__DIR__ . '/../../config.php');

$userid = optional_param('userid', 0, PARAM_INT);

require_login(null, false);
if (isguestuser()) {
    throw new \moodle_exception('noguest');
}
$userid = $userid ?: (int) $USER->id;
$user = core_user::get_user($userid, '*', MUST_EXIST);
if ($user->deleted) {
    throw new \moodle_exception('userdeleted');
}
$isown = (int) $user->id === (int) $USER->id;
if (!timetable::can_view_user((int) $user->id)) {
    throw new required_capability_exception(context_system::instance(), 'local/aulaet37:viewtimetables', 'nopermissions', '');
}

$url = new moodle_url('/local/aulaet37/horario.php', $isown ? [] : ['userid' => $user->id]);
$PAGE->set_url($url);
// Own timetable: system context, so Moodle does not add the profile header with a "Message" button.
$PAGE->set_context($isown ? context_system::instance() : context_user::instance($user->id));
$PAGE->set_pagelayout('standard');
$title = $isown ? get_string('mytimetable', 'local_aulaet37') :
    get_string('timetableof', 'local_aulaet37', fullname($user));
$PAGE->set_title($title);
$PAGE->set_heading($title);
if (!$isown) {
    $PAGE->navbar->add(fullname($user), new moodle_url('/user/profile.php', ['id' => $user->id]));
}
$PAGE->navbar->add(get_string('timetable', 'local_aulaet37'), $url);

$items = timetable::get_user_items((int) $user->id);
[$today] = timetable::now();
$todayitems = timetable::today_items((int) $user->id);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_aulaet37/timetable', [
    'intro' => $isown ? get_string('mytimetableintro', 'local_aulaet37') :
        get_string('timetableofintro', 'local_aulaet37', fullname($user)),
    'todayname' => timetable::weekday_name($today),
    'hastoday' => !empty($todayitems),
    'today' => $todayitems,
    'days' => timetable::export_week($items),
    'hasitems' => !empty($items),
]);
echo $OUTPUT->footer();
