<?php
// This file is part of Aula ET37 - https://moodle.org/
//
// Aula ET37 is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Personal data step (DNI and birth date) for self-registered and Google accounts.
 *
 * @package    local_aulaet37
 * @copyright  2026 Aula ET37
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_aulaet37\local\personal_data;
use local_aulaet37\local\steps;

require_once(__DIR__ . '/../../config.php');

require_login(null, false);
if (isguestuser()) {
    throw new \moodle_exception('noguest');
}
require_capability('moodle/user:editownprofile', context_system::instance());

$url = new moodle_url(steps::DATA_SCRIPT);
$PAGE->set_url($url);
$PAGE->set_context(context_user::instance($USER->id));
$PAGE->set_pagelayout('login');
$PAGE->set_title(get_string('datastep', 'local_aulaet37'));
$PAGE->set_heading(get_string('datastep', 'local_aulaet37'));

$values = personal_data::get_values((int) $USER->id);
if ($values === null) {
    throw new \moodle_exception('datafieldsmissing', 'local_aulaet37');
}

$form = new \local_aulaet37\form\personal_data_form($url, ['userid' => (int) $USER->id]);
$defaults = [personal_data::DNI_INPUT => $values['dni']];
if ($values['birthdate'] > 0) {
    $defaults[personal_data::BIRTHDATE_INPUT] = $values['birthdate'];
}
$form->set_data($defaults);

if ($data = $form->get_data()) {
    personal_data::save((int) $USER->id, (string) $data->{personal_data::DNI_INPUT},
        (int) $data->{personal_data::BIRTHDATE_INPUT});
    redirect(steps::return_url(), get_string('datasaved', 'local_aulaet37'), null,
        \core\output\notification::NOTIFY_SUCCESS);
}

$required = personal_data::is_required_for($USER);
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_aulaet37/data_step', [
    'fullname' => fullname($USER),
    'required' => $required,
    'form' => $form->render(),
    'logouturl' => (new moodle_url('/login/logout.php', ['sesskey' => sesskey()]))->out(false),
    'profileurl' => (new moodle_url('/user/profile.php'))->out(false),
]);
echo $OUTPUT->footer();
