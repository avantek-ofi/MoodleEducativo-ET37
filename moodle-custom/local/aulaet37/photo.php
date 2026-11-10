<?php
// This file is part of Aula ET37 - https://moodle.org/
//
// Aula ET37 is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Profile photo step for self-registered accounts.
 *
 * @package    local_aulaet37
 * @copyright  2026 Aula ET37
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');

require_login(null, false);
if (isguestuser()) {
    throw new \moodle_exception('noguest');
}
if (!empty($CFG->disableuserimages)) {
    throw new \moodle_exception('photodisabled', 'local_aulaet37');
}

$systemcontext = context_system::instance();
$usercontext = context_user::instance($USER->id);
require_capability('moodle/user:editownprofile', $systemcontext);

$url = new moodle_url('/local/aulaet37/photo.php');
$PAGE->set_url($url);
$PAGE->set_context($usercontext);
$PAGE->set_pagelayout('login');
$PAGE->set_title(get_string('photostep', 'local_aulaet37'));
$PAGE->set_heading(get_string('photostep', 'local_aulaet37'));

$filemanageroptions = [
    'maxbytes' => $CFG->maxbytes,
    'subdirs' => 0,
    'maxfiles' => 1,
    'accepted_types' => 'optimised_image',
];
$draftitemid = file_get_submitted_draft_itemid('imagefile');
file_prepare_draft_area($draftitemid, $usercontext->id, 'user', 'newicon', 0, $filemanageroptions);

$form = new \local_aulaet37\form\photo_form($url, ['filemanageroptions' => $filemanageroptions]);
$form->set_data(['imagefile' => $draftitemid]);

if ($data = $form->get_data()) {
    $usernew = (object) ['id' => $USER->id, 'imagefile' => $data->imagefile];
    core_user::update_picture($usernew, $filemanageroptions);
    $picture = (int) $DB->get_field('user', 'picture', ['id' => $USER->id]);
    if ($picture) {
        $USER->picture = $picture;
        \core\event\user_updated::create_from_userid($USER->id)->trigger();
        $returnurl = !empty($SESSION->wantsurl) ? $SESSION->wantsurl : new moodle_url('/my/');
        unset($SESSION->wantsurl);
        redirect($returnurl, get_string('photosaved', 'local_aulaet37'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
    \core\notification::error(get_string('photonotprocessed', 'local_aulaet37'));
}

$logouturl = new moodle_url('/login/logout.php', ['sesskey' => sesskey()]);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_aulaet37/photo_step', [
    'fullname' => fullname($USER),
    'required' => \local_aulaet37\local\photo::is_required_for($USER),
    'form' => $form->render(),
    'logouturl' => $logouturl->out(false),
    'profileurl' => (new moodle_url('/user/profile.php'))->out(false),
]);
echo $OUTPUT->footer();
