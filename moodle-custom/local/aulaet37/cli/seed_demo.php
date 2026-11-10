<?php
// This file is part of Aula ET37 - https://moodle.org/
//
// Aula ET37 is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Creates fictitious courses, users and activities for local development only.
 *
 * Requires MOODLE_DEMO_PASSWORD in the environment and --confirm. It is idempotent:
 * existing demo courses and users are reused.
 *
 * @package    local_aulaet37
 * @copyright  2026 Aula ET37
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/completionlib.php');

[$options, $unrecognized] = cli_get_params(['help' => false, 'confirm' => false], ['h' => 'help']);
if ($options['help'] || !$options['confirm']) {
    cli_writeln("Crea datos FICTICIOS solo para desarrollo local.\n\n" .
        "MOODLE_DEMO_PASSWORD=... php public/local/aulaet37/cli/seed_demo.php --confirm");
    exit($options['help'] ? 0 : 1);
}

$password = getenv('MOODLE_DEMO_PASSWORD');
if ($password === false || trim($password) === '') {
    cli_error('Definir MOODLE_DEMO_PASSWORD en .env para crear usuarios de demostración.');
}

\core\session\manager::set_user(get_admin());
$generator = \core\test\phpunit\phpunit_util::get_data_generator();

/**
 * Returns an existing demo user or creates it.
 *
 * @param testing_data_generator $generator
 * @param array $record
 * @return stdClass
 */
function local_aulaet37_demo_user(testing_data_generator $generator, array $record): stdClass {
    global $DB;
    if ($user = $DB->get_record('user', ['username' => $record['username'], 'deleted' => 0])) {
        return $user;
    }
    $user = $generator->create_user($record + ['auth' => 'manual', 'lang' => 'es', 'country' => 'AR',
        'timezone' => '99', 'confirmed' => 1]);
    cli_writeln("Usuario creado: {$record['username']}");
    return $user;
}

$teacher = local_aulaet37_demo_user($generator, [
    'username' => 'docente.demo',
    'password' => $password,
    'firstname' => 'Valeria',
    'lastname' => 'Demo Docente',
    'email' => 'docente.demo@aula-et37.example',
]);
$student = local_aulaet37_demo_user($generator, [
    'username' => 'estudiante.demo',
    'password' => $password,
    'firstname' => 'Tomás',
    'lastname' => 'Demo Estudiante',
    'email' => 'estudiante.demo@aula-et37.example',
]);

// Fictitious values for the required registration fields (DNI range 99.xxx.xxx reserved here for demos).
require_once($CFG->dirroot . '/user/profile/lib.php');
\local_aulaet37\local\profile_fields::ensure();
foreach ([[$teacher, '99000001', '1985-03-14'], [$student, '99000002', '2009-08-21']] as [$demouser, $dni, $birth]) {
    profile_load_data($demouser);
    if (empty($demouser->{'profile_field_' . \local_aulaet37\local\profile_fields::DNI})) {
        profile_save_data((object) [
            'id' => $demouser->id,
            'profile_field_' . \local_aulaet37\local\profile_fields::DNI => $dni,
            'profile_field_' . \local_aulaet37\local\profile_fields::BIRTHDATE =>
                (new DateTimeImmutable($birth . ' 12:00:00', core_date::get_server_timezone_object()))->getTimestamp(),
        ]);
        cli_writeln("Datos de perfil ficticios completados: {$demouser->username}");
    }
}

$category = $DB->get_record('course_categories', ['idnumber' => 'AULAET37-DEMO']);
if (!$category) {
    $category = $generator->create_category([
        'name' => 'Cursos de demostración',
        'idnumber' => 'AULAET37-DEMO',
        'description' => 'Datos ficticios para desarrollo local.',
    ]);
}

$courses = [
    ['PROG-DEMO', 'Programación I', true, 1],
    ['MAT-DEMO', 'Matemática 4.º año', true, 2],
    ['ELEC-DEMO', 'Taller de Electrónica', true, 0],
    ['LENG-DEMO', 'Lengua y Literatura', true, 1],
    ['BD-DEMO', 'Bases de Datos', false, 0],
];

$now = time();
$offset = 1;
foreach ($courses as [$shortname, $fullname, $studentenrolled, $completed]) {
    if ($DB->record_exists('course', ['shortname' => $shortname])) {
        cli_writeln("Curso existente: {$shortname}");
        continue;
    }
    $course = $generator->create_course([
        'shortname' => $shortname,
        'fullname' => $fullname,
        'category' => $category->id,
        'summary' => 'Curso ficticio para probar Aula ET37. No contiene datos reales.',
        'format' => 'topics',
        'numsections' => 4,
        'enablecompletion' => 1,
        'startdate' => $now - 30 * DAYSECS,
        'lang' => 'es',
    ]);
    $generator->enrol_user($teacher->id, $course->id, 'editingteacher');
    if ($studentenrolled) {
        $generator->enrol_user($student->id, $course->id, 'student');
    }

    $cms = [];
    $cms[] = $generator->create_module('page', [
        'course' => $course->id,
        'section' => 1,
        'name' => 'Programa y criterios de evaluación',
        'content' => '<p>Contenido ficticio de demostración.</p>',
        'completion' => COMPLETION_TRACKING_MANUAL,
    ]);
    $cms[] = $generator->create_module('assign', [
        'course' => $course->id,
        'section' => 1,
        'name' => 'Trabajo práctico 1',
        'intro' => '<p>Consigna ficticia de demostración.</p>',
        'allowsubmissionsfromdate' => $now - DAYSECS,
        'duedate' => $now + $offset * DAYSECS,
        'assignsubmission_onlinetext_enabled' => 1,
        'completion' => COMPLETION_TRACKING_MANUAL,
    ]);
    $cms[] = $generator->create_module('assign', [
        'course' => $course->id,
        'section' => 2,
        'name' => 'Trabajo práctico 2',
        'intro' => '<p>Consigna ficticia de demostración.</p>',
        'allowsubmissionsfromdate' => $now,
        'duedate' => $now + ($offset + 6) * DAYSECS,
        'assignsubmission_onlinetext_enabled' => 1,
        'completion' => COMPLETION_TRACKING_MANUAL,
    ]);
    $offset += 2;

    if ($studentenrolled && $completed) {
        $completion = new completion_info($course);
        $modinfo = get_fast_modinfo($course);
        foreach (array_slice($cms, 0, $completed) as $instance) {
            $completion->update_state($modinfo->get_cm($instance->cmid), COMPLETION_COMPLETE, $student->id);
        }
    }
    cli_writeln("Curso creado: {$shortname}");
}

cli_writeln('Datos ficticios listos. Usuarios: docente.demo y estudiante.demo (contraseña: MOODLE_DEMO_PASSWORD).');
