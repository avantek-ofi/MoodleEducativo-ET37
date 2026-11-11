<?php
// This file is part of Aula ET37 - https://moodle.org/
//
// Aula ET37 is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Idempotent institutional setup: school roles and their permissions.
 *
 * Only native Moodle roles and capabilities are used. The script creates missing roles,
 * grants the capabilities listed here and never removes permissions an administrator
 * added later. Capabilities of plugins that are not installed (for example mod_attendance)
 * are skipped and granted on the next run.
 *
 * @package    local_aulaet37
 * @copyright  2026 Aula ET37
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/accesslib.php');

[$options, $unrecognized] = cli_get_params(['help' => false], ['h' => 'help']);
if ($options['help']) {
    cli_writeln("Crea o actualiza los roles institucionales (directivos, coordinación, preceptoría) y sus permisos.\n\n" .
        "php public/local/aulaet37/cli/institution.php");
    exit(0);
}

\core\session\manager::set_user(get_admin());
$systemcontext = context_system::instance();

// Read-only supervision of everything inside the context where the role is assigned
// (site for directors, an area category for coordinators). No editing, no grading.
$supervision = [
    'moodle/course:view',
    'moodle/course:viewhiddencourses',
    'moodle/course:viewhiddenactivities',
    'moodle/course:viewhiddensections',
    'moodle/course:viewparticipants',
    'moodle/course:viewsuspendedusers',
    'moodle/course:enrolreview',
    'moodle/category:viewcourselist',
    'moodle/category:viewhiddencategories',
    'moodle/site:accessallgroups',
    'moodle/site:viewfullnames',
    'moodle/site:viewreports',
    'moodle/user:viewdetails',
    'moodle/grade:viewall',
    'moodle/grade:viewhidden',
    'gradereport/grader:view',
    'gradereport/user:view',
    'gradereport/overview:view',
    'report/log:view',
    'report/outline:view',
    'report/participation:view',
    'report/progress:view',
    'report/completion:view',
    'moodle/badges:viewawarded',
    'moodle/notes:view',
    'mod/assign:view',
    'mod/assign:viewgrades',
    'mod/quiz:viewreports',
    'mod/forum:viewqandawithoutposting',
    'mod/feedback:viewreports',
    'mod/choice:readresponses',
    'mod/lesson:viewreports',
    'mod/h5pactivity:reviewattempts',
    'mod/scorm:viewreport',
    'mod/workshop:viewallsubmissions',
    'mod/attendance:view',
    'mod/attendance:viewreports',
    'mod/attendance:viewsummaryreports',
    'mod/attendance:export',
    'local/aulaet37:viewtimetables',
];

// Creating classrooms: Moodle enrols the creator as teacher of the new course
// ($CFG->creatornewroleid), so editing is limited to the classrooms they create.
$createcourses = [
    'moodle/course:create',
];

// Preceptors follow their divisions: participants, grades (read only), notes and attendance.
$preceptorship = [
    'moodle/course:view',
    'moodle/course:viewparticipants',
    'moodle/course:viewsuspendedusers',
    'moodle/category:viewcourselist',
    'moodle/site:accessallgroups',
    'moodle/site:viewfullnames',
    'moodle/user:viewdetails',
    'moodle/grade:viewall',
    'gradereport/grader:view',
    'gradereport/user:view',
    'report/progress:view',
    'moodle/notes:view',
    'moodle/notes:manage',
    'mod/assign:view',
    'mod/attendance:view',
    'mod/attendance:takeattendances',
    'mod/attendance:changeattendances',
    'mod/attendance:manageattendances',
    'mod/attendance:viewreports',
    'mod/attendance:viewsummaryreports',
    'mod/attendance:export',
    'mod/attendance:warningemails',
    // Class timetable: preceptors load the blocks of their divisions and see each student's timetable.
    'local/aulaet37:viewtimetables',
    'local/aulaet37:managetimetable',
];

// School-wide communication for directors (assigned at site level): posting in the site
// announcements forum (reaches every account) and creating site calendar events (shared dates).
$schoolwide = [
    'mod/forum:viewdiscussion',
    'mod/forum:addnews',
    'mod/forum:replynews',
    'mod/forum:createattachment',
    'mod/forum:pindiscussions',
    'moodle/calendar:manageentries',
];

$directivo = array_merge($supervision, $createcourses, $schoolwide);
$roles = [
    'rector' => ['Rector/a', 'Directivo. Ve todas las aulas, calificaciones, asistencia e informes sin editar. ' .
        'Puede crear aulas. Asignar en el contexto del sistema.', [CONTEXT_SYSTEM, CONTEXT_COURSECAT], $directivo],
    'vicerrector' => ['Vicerrector/a', 'Directivo. Mismos permisos que Rector/a. Asignar en el contexto del sistema.',
        [CONTEXT_SYSTEM, CONTEXT_COURSECAT], $directivo],
    'director' => ['Director/a', 'Directivo. Mismos permisos que Rector/a. Asignar en el contexto del sistema ' .
        'o en la categoría que dirige.', [CONTEXT_SYSTEM, CONTEXT_COURSECAT], $directivo],
    'regente' => ['Regente', 'Directivo del área técnica. Mismos permisos que Rector/a; asignar en el sistema ' .
        'o solo en las categorías técnicas.', [CONTEXT_SYSTEM, CONTEXT_COURSECAT], $directivo],
    'coordinador' => ['Coordinador/a de área', 'Ve las aulas de su área (la categoría donde se asigna) sin editar ' .
        'y puede crear aulas en ella. Para editar un aula concreta, matricularlo/a como Docente en esa aula.',
        [CONTEXT_COURSECAT], array_merge($supervision, $createcourses)],
    'preceptor' => ['Preceptor/a', 'Toma y consulta la asistencia, ve participantes y calificaciones (sin editar) ' .
        'y registra notas sobre los estudiantes. Asignar en el aula o en la categoría de la división.',
        [CONTEXT_COURSECAT, CONTEXT_COURSE], $preceptorship],
];

// Display names for the standard roles, only when the administrator has not renamed them.
$standardnames = [
    'manager' => 'Gestor/a de aulas',
    'editingteacher' => 'Docente',
    'teacher' => 'Docente sin permiso de edición',
    'student' => 'Estudiante',
];
foreach ($standardnames as $shortname => $name) {
    $role = $DB->get_record('role', ['shortname' => $shortname], '*', MUST_EXIST);
    if (trim((string) $role->name) === '') {
        $DB->set_field('role', 'name', $name, ['id' => $role->id]);
        cli_writeln("Rol {$shortname}: nombre visible \"{$name}\".");
    }
}

$managerid = $DB->get_field('role', 'id', ['shortname' => 'manager'], MUST_EXIST);
$viewers = $DB->get_records_list('role', 'shortname', ['manager', 'editingteacher', 'teacher', 'student'], '', 'id, shortname');
$created = [];
$skipped = [];
foreach ($roles as $shortname => [$name, $description, $levels, $capabilities]) {
    $roleid = $DB->get_field('role', 'id', ['shortname' => $shortname]);
    if (!$roleid) {
        $roleid = create_role($name, $shortname, $description, '');
        set_role_contextlevels($roleid, $levels);
        $created[] = $shortname;
    }
    foreach (array_unique($capabilities) as $capability) {
        if (!get_capability_info($capability)) {
            $skipped[$capability] = true;
            continue;
        }
        // Never overwrite a permission the administrator changed (for example to prohibit it).
        if (!$DB->record_exists('role_capabilities', ['roleid' => $roleid, 'contextid' => $systemcontext->id,
                'capability' => $capability])) {
            assign_capability($capability, CAP_ALLOW, $roleid, $systemcontext->id);
        }
    }
    // Managers assign these roles; standard roles can see them in participant lists.
    if (!$DB->record_exists('role_allow_assign', ['roleid' => $managerid, 'allowassign' => $roleid])) {
        core_role_set_assign_allowed($managerid, $roleid);
    }
    foreach ($viewers as $viewer) {
        if (!$DB->record_exists('role_allow_view', ['roleid' => $viewer->id, 'allowview' => $roleid])) {
            core_role_set_view_allowed($viewer->id, $roleid);
        }
    }
    if (!$DB->record_exists('role_allow_view', ['roleid' => $roleid, 'allowview' => $roleid])) {
        core_role_set_view_allowed($roleid, $roleid);
    }
}
foreach ($roles as $shortname => $unused) {
    $roleid = $DB->get_field('role', 'id', ['shortname' => $shortname]);
    foreach ($roles as $other => $unused2) {
        $otherid = $DB->get_field('role', 'id', ['shortname' => $other]);
        if (!$DB->record_exists('role_allow_view', ['roleid' => $roleid, 'allowview' => $otherid])) {
            core_role_set_view_allowed($roleid, $otherid);
        }
    }
}
cli_writeln('Roles institucionales: ' . ($created ? 'creados ' . implode(', ', $created) : 'ya existentes') . '.');
if ($skipped) {
    cli_writeln('Permisos omitidos (complemento no instalado): ' . implode(', ', array_keys($skipped)));
}

// Only administrators, managers, directors and coordinators create classrooms: no course requests.
set_config('enablecourserequests', 0);
// Teachers who create a classroom keep editing it.
set_config('creatornewroleid', $DB->get_field('role', 'id', ['shortname' => 'editingteacher'], MUST_EXIST));

// Navigation and Administration blocks that Moodle forced on every page while the theme lacked
// Boost's "requiredblocks = ''". The left drawer and the secondary navigation replace them.
[$insql, $params] = $DB->get_in_or_equal(['navigation', 'settings'], SQL_PARAMS_NAMED);
$legacyblocks = $DB->get_records_select('block_instances', "blockname {$insql} AND parentcontextid = :ctx " .
    "AND pagetypepattern = :pattern", $params + ['ctx' => $systemcontext->id, 'pattern' => '*']);
foreach ($legacyblocks as $block) {
    blocks_delete_instance($block);
}
if ($legacyblocks) {
    cli_writeln('Bloques globales "Navegación" y "Administración" quitados (los reemplaza el menú lateral).');
}

// School announcements board: the site news forum with forced subscription, readable by every
// account through the "Authenticated user on site home" role.
$newsforum = \local_aulaet37\local\institution::ensure_news_forum();
if (empty($CFG->defaultfrontpageroleid)) {
    $frontpageroleid = $DB->get_field('role', 'id', ['archetype' => 'frontpage'], IGNORE_MULTIPLE);
    if ($frontpageroleid) {
        set_config('defaultfrontpageroleid', $frontpageroleid);
    }
}
cli_writeln('Avisos de la escuela: foro del sitio "' . format_string($newsforum->name) . '" con suscripción forzosa.');

// Attendance (mod_attendance): taken by teachers and preceptors, visible to directors and coordinators.
if (\core_component::get_component_directory('mod_attendance')) {
    cli_writeln('Asistencia: complemento instalado; permisos de preceptoría y supervisión aplicados.');
} else {
    cli_writeln('Asistencia: mod_attendance no está instalado (reconstruir la imagen).');
}

accesslib_clear_all_caches(true);
cli_writeln('Listo.');
