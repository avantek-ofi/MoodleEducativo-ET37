<?php
// This file is part of Aula ET37 - https://moodle.org/
//
// Aula ET37 is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * FICTITIOUS institutional demo data: one user per school role and an attendance
 * activity with sessions in the demo courses. Run after seed_demo.php and institution.php.
 *
 * @package    local_aulaet37
 * @copyright  2026 Aula ET37
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

[$options, $unrecognized] = cli_get_params(['help' => false, 'confirm' => false], ['h' => 'help']);
if ($options['help'] || !$options['confirm']) {
    cli_writeln("Crea datos FICTICIOS de roles institucionales y asistencia, solo para desarrollo local.\n\n" .
        "MOODLE_DEMO_PASSWORD=... php public/local/aulaet37/cli/seed_institution_demo.php --confirm");
    exit($options['help'] ? 0 : 1);
}

$password = getenv('MOODLE_DEMO_PASSWORD');
if ($password === false || trim($password) === '') {
    cli_error('Definir MOODLE_DEMO_PASSWORD en .env para crear usuarios de demostración.');
}

\core\session\manager::set_user(get_admin());
$generator = \core\test\phpunit\phpunit_util::get_data_generator();

$category = $DB->get_record('course_categories', ['idnumber' => 'AULAET37-DEMO']);
if (!$category) {
    cli_error('Ejecutar antes seed_demo.php (falta la categoría AULAET37-DEMO).');
}
$roleids = $DB->get_records_menu('role', null, '', 'shortname, id');
foreach (['rector', 'coordinador', 'preceptor'] as $required) {
    if (empty($roleids[$required])) {
        cli_error('Ejecutar antes institution.php (falta el rol ' . $required . ').');
    }
}
$democourses = $DB->get_records('course', ['category' => $category->id], 'sortorder');

// Users: [username, first name, last name, DNI (99.xxx.xxx range reserved for demos), birth date].
$people = [
    'directivo' => ['directivo.demo', 'Laura', 'Demo Rectora', '99000003', '1972-05-02'],
    'coordinador' => ['coordinador.demo', 'Martín', 'Demo Coordinador', '99000004', '1980-09-30'],
    'preceptor' => ['preceptor.demo', 'Sofía', 'Demo Preceptora', '99000005', '1990-01-17'],
];
$users = [];
foreach ($people as $key => [$username, $firstname, $lastname, $dni, $birth]) {
    $user = $DB->get_record('user', ['username' => $username, 'deleted' => 0]);
    if (!$user) {
        $user = $generator->create_user(['username' => $username, 'password' => $password, 'firstname' => $firstname,
            'lastname' => $lastname, 'email' => $username . '@aula-et37.example', 'auth' => 'manual', 'lang' => 'es',
            'country' => 'AR', 'timezone' => '99', 'confirmed' => 1]);
        profile_save_data((object) [
            'id' => $user->id,
            'profile_field_' . \local_aulaet37\local\profile_fields::DNI => $dni,
            'profile_field_' . \local_aulaet37\local\profile_fields::BIRTHDATE =>
                (new DateTimeImmutable($birth . ' 12:00:00', core_date::get_server_timezone_object()))->getTimestamp(),
        ]);
        cli_writeln("Usuario creado: {$username}");
    }
    $users[$key] = $user;
}

// Director: whole site. Coordinator: the demo category acts as their area. Preceptor: two divisions.
role_assign($roleids['rector'], $users['directivo']->id, context_system::instance()->id);
role_assign($roleids['coordinador'], $users['coordinador']->id, context_coursecat::instance($category->id)->id);
foreach ($democourses as $course) {
    if (in_array($course->shortname, ['PROG-DEMO', 'MAT-DEMO'], true)) {
        $generator->enrol_user($users['preceptor']->id, $course->id, 'preceptor');
    }
}
cli_writeln('Roles asignados: directivo.demo (Rector/a, sitio), coordinador.demo (Coordinador/a, categoría demo), ' .
    'preceptor.demo (Preceptor/a en PROG-DEMO y MAT-DEMO).');

// Attendance activity with past and upcoming school-day sessions (07:45, 4 h 40 min).
if (!\core_component::get_component_directory('mod_attendance')) {
    cli_writeln('Asistencia: mod_attendance no está instalado; se omite.');
    exit(0);
}
require_once($CFG->dirroot . '/mod/attendance/locallib.php');
$timezone = core_date::get_server_timezone_object();
$days = [];
$day = (new DateTimeImmutable('today 07:45', $timezone))->modify('-7 days');
while (count($days) < 15) {
    if ((int) $day->format('N') <= 5) {
        $days[] = $day->getTimestamp();
    }
    $day = $day->modify('+1 day');
}
foreach ($democourses as $course) {
    if ($DB->record_exists('attendance', ['course' => $course->id])) {
        continue;
    }
    $instance = $generator->create_module('attendance', [
        'course' => $course->id,
        'section' => 0,
        'name' => 'Asistencia',
        'intro' => '<p>Registro de asistencia ficticio de la división.</p>',
        'grade' => 0,
    ]);
    $cm = get_coursemodule_from_instance('attendance', $instance->id, $course->id, false, MUST_EXIST);
    $structure = new mod_attendance_structure($DB->get_record('attendance', ['id' => $instance->id]), $cm, $course,
        context_module::instance($cm->id));
    foreach ($days as $sessdate) {
        $structure->add_session((object) [
            'sessdate' => $sessdate,
            'duration' => 280 * MINSECS,
            'groupid' => 0,
            'description' => 'Jornada escolar',
            'descriptionformat' => FORMAT_HTML,
            'descriptionitemid' => 0,
            'calendarevent' => 0,
            'timemodified' => time(),
            'statusset' => 0,
        ]);
    }
    cli_writeln("Asistencia creada con " . count($days) . " sesiones: {$course->shortname}");
}

cli_writeln('Datos institucionales ficticios listos (contraseña: MOODLE_DEMO_PASSWORD).');
