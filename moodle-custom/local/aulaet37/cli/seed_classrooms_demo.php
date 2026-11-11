<?php
// This file is part of Aula ET37 - https://moodle.org/
//
// Aula ET37 is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * FICTITIOUS test classrooms for local development: two programming classrooms with
 * auto-graded code exercises (CodeRunner), a weekly timetable for every demo classroom,
 * school announcements and school dates. Run after seed_demo.php and seed_institution_demo.php.
 *
 * Idempotent: classrooms, users, posts, events and timetables that already exist are kept.
 *
 * @package    local_aulaet37
 * @copyright  2026 Aula ET37
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->dirroot . '/calendar/lib.php');
require_once($CFG->dirroot . '/mod/forum/lib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/question/format.php');
require_once($CFG->dirroot . '/question/format/xml/format.php');

use local_aulaet37\local\institution;
use local_aulaet37\local\profile_fields;
use local_aulaet37\local\timetable;

[$options, $unrecognized] = cli_get_params(['help' => false, 'confirm' => false], ['h' => 'help']);
if ($options['help'] || !$options['confirm']) {
    cli_writeln("Crea aulas de prueba FICTICIAS (programación con corrección automática), horarios, avisos y fechas.\n\n" .
        "MOODLE_DEMO_PASSWORD=... php public/local/aulaet37/cli/seed_classrooms_demo.php --confirm");
    exit($options['help'] ? 0 : 1);
}

$password = getenv('MOODLE_DEMO_PASSWORD');
if ($password === false || trim($password) === '') {
    cli_error('Definir MOODLE_DEMO_PASSWORD en .env para crear datos de demostración.');
}

\core\session\manager::set_user(get_admin());
$generator = \core\test\phpunit\phpunit_util::get_data_generator();

$category = $DB->get_record('course_categories', ['idnumber' => 'AULAET37-DEMO']);
if (!$category) {
    cli_error('Ejecutar antes seed_demo.php (falta la categoría AULAET37-DEMO).');
}
$users = [];
foreach (['docente.demo', 'estudiante.demo', 'preceptor.demo', 'directivo.demo'] as $username) {
    $users[$username] = $DB->get_record('user', ['username' => $username, 'deleted' => 0]);
    if (!$users[$username]) {
        cli_error("Ejecutar antes seed_demo.php y seed_institution_demo.php (falta {$username}).");
    }
}

// Two more fictitious students so groups and the preceptor's list have more than one person.
foreach ([
    ['lucia.demo', 'Lucía', 'Demo Estudiante', '99000006', '2009-02-11'],
    ['joaquin.demo', 'Joaquín', 'Demo Estudiante', '99000007', '2008-11-30'],
] as [$username, $firstname, $lastname, $dni, $birth]) {
    $user = $DB->get_record('user', ['username' => $username, 'deleted' => 0]);
    if (!$user) {
        $user = $generator->create_user(['username' => $username, 'password' => $password, 'firstname' => $firstname,
            'lastname' => $lastname, 'email' => $username . '@aula-et37.example', 'auth' => 'manual', 'lang' => 'es',
            'country' => 'AR', 'timezone' => '99', 'confirmed' => 1]);
        profile_save_data((object) [
            'id' => $user->id,
            'profile_field_' . profile_fields::DNI => $dni,
            'profile_field_' . profile_fields::BIRTHDATE =>
                (new DateTimeImmutable($birth . ' 12:00:00', core_date::get_server_timezone_object()))->getTimestamp(),
        ]);
        cli_writeln("Usuario creado: {$username}");
    }
    $users[$username] = $user;
}

/**
 * Moodle XML for one CodeRunner question that uses a built-in question type (no custom template).
 *
 * @param array $q name, text, type (python3, c_program, nodejs...), answer, preload, tests
 *                 (each: code, stdin, expected, example).
 * @return string
 */
function local_aulaet37_coderunner_xml(array $q): string {
    $cdata = fn(string $text) => '<![CDATA[' . str_replace(']]>', ']]]]><![CDATA[>', $text) . ']]>';
    $tests = '';
    foreach ($q['tests'] as $test) {
        $tests .= '<testcase testtype="0" useasexample="' . (int) !empty($test['example']) . '" hiderestiffail="0" ' .
            'mark="1.0000000"><testcode><text>' . $cdata($test['code'] ?? '') . '</text></testcode>' .
            '<stdin><text>' . $cdata($test['stdin'] ?? '') . '</text></stdin>' .
            '<expected><text>' . $cdata($test['expected']) . '</text></expected>' .
            '<extra><text></text></extra><display><text>SHOW</text></display></testcase>';
    }
    return '<question type="coderunner"><name><text>' . $cdata($q['name']) . '</text></name>' .
        '<questiontext format="html"><text>' . $cdata($q['text']) . '</text></questiontext>' .
        '<generalfeedback format="html"><text></text></generalfeedback>' .
        '<defaultgrade>1.0000000</defaultgrade><penalty>0.0000000</penalty><hidden>0</hidden>' .
        '<coderunnertype>' . $q['type'] . '</coderunnertype><prototypetype>0</prototypetype>' .
        '<allornothing>0</allornothing><penaltyregime>0, 10, 20, ...</penaltyregime><precheck>1</precheck>' .
        '<hidecheck>0</hidecheck><showsource>0</showsource><answerboxlines>12</answerboxlines>' .
        '<answerboxcolumns>90</answerboxcolumns><answerpreload>' . $cdata($q['preload'] ?? '') . '</answerpreload>' .
        '<globalextra></globalextra><useace>1</useace><resultcolumns></resultcolumns><template></template>' .
        '<iscombinatortemplate></iscombinatortemplate><allowmultiplestdins></allowmultiplestdins>' .
        '<answer>' . $cdata($q['answer']) . '</answer><validateonsave>0</validateonsave>' .
        '<testsplitterre></testsplitterre><language></language><acelang></acelang><sandbox></sandbox><grader></grader>' .
        '<cputimelimitsecs></cputimelimitsecs><memlimitmb></memlimitmb><sandboxparams></sandboxparams>' .
        '<templateparams></templateparams><hoisttemplateparams>1</hoisttemplateparams>' .
        '<extractcodefromjson>1</extractcodefromjson><templateparamslang>None</templateparamslang>' .
        '<templateparamsevalpertry>0</templateparamsevalpertry><templateparamsevald>{}</templateparamsevald>' .
        '<twigall>0</twigall><uiplugin></uiplugin><uiparameters></uiparameters><attachments>0</attachments>' .
        '<attachmentsrequired>0</attachmentsrequired><maxfilesize>10240</maxfilesize><filenamesregex></filenamesregex>' .
        '<filenamesexplain></filenamesexplain><displayfeedback>1</displayfeedback><giveupallowed>0</giveupallowed>' .
        '<prototypeextra></prototypeextra><testcases>' . $tests . '</testcases></question>';
}

/**
 * Imports CodeRunner questions into the classroom's question bank and adds them to a new quiz.
 *
 * @param testing_data_generator $generator
 * @param stdClass $course
 * @param int $section
 * @param string $name
 * @param string $intro
 * @param array[] $questions
 */
function local_aulaet37_code_quiz(testing_data_generator $generator, stdClass $course, int $section, string $name,
        string $intro, array $questions): void {
    global $CFG;
    $qbank = \core_question\local\bank\question_bank_helper::get_default_open_instance_system_type($course, true);
    $bankcontext = context_module::instance($qbank->id);
    $qcategory = question_get_default_category($bankcontext->id, true);

    $file = make_request_directory() . '/preguntas.xml';
    file_put_contents($file, '<?xml version="1.0" encoding="UTF-8"?><quiz>' .
        implode('', array_map('local_aulaet37_coderunner_xml', $questions)) . '</quiz>');
    $format = new qformat_xml();
    $format->setCategory($qcategory);
    $format->setContexts([$bankcontext]);
    $format->setCourse($course);
    $format->setFilename($file);
    $format->setRealfilename('preguntas.xml');
    $format->setMatchgrades('error');
    $format->setCatfromfile(false);
    $format->setContextfromfile(false);
    $format->setStoponerror(true);
    ob_start();
    $ok = $format->importpreprocess() && $format->importprocess() && $format->importpostprocess();
    $log = ob_get_clean();
    if (!$ok || count($format->questionids) !== count($questions)) {
        cli_error("No se pudieron importar las preguntas de \"{$name}\":\n" . trim(strip_tags($log)));
    }

    $quiz = $generator->create_module('quiz', [
        'course' => $course->id,
        'section' => $section,
        'name' => $name,
        'intro' => $intro,
        'preferredbehaviour' => 'adaptive',
        'questionsperpage' => 1,
        'grade' => 10,
        'attempts' => 0,
        'completion' => COMPLETION_TRACKING_AUTOMATIC,
        'completionview' => 1,
    ]);
    foreach ($format->questionids as $questionid) {
        quiz_add_quiz_question($questionid, $quiz, 0);
    }
    \mod_quiz\quiz_settings::create($quiz->id)->get_grade_calculator()->recompute_quiz_sumgrades();
    cli_writeln("  Cuestionario autocorregido: {$name} (" . count($format->questionids) . ' ejercicios)');
}

$now = time();
$codeblock = fn(string $code) => '<pre><code>' . s($code) . '</code></pre>';

$classrooms = [
    'PROG2-PRUEBA' => [
        'fullname' => 'Programación II · 5.º 1.ª (aula de prueba)',
        'summary' => '<p>Aula FICTICIA para probar ejercicios de código con corrección automática, entregas con ' .
            'GitHub o Google Drive y el horario por división. No contiene datos reales.</p>',
        'sections' => ['Bienvenida y herramientas', 'Python: funciones', 'C: entrada y salida', 'Trabajo práctico'],
        'groups' => ['Grupo A (laboratorio martes)', 'Grupo B (laboratorio jueves)'],
    ],
    'WEB-PRUEBA' => [
        'fullname' => 'Laboratorio de Programación Web · 6.º 2.ª (aula de prueba)',
        'summary' => '<p>Aula FICTICIA de desarrollo web: JavaScript autocorregido, entrega de proyectos y ' .
            'glosario técnico. No contiene datos reales.</p>',
        'sections' => ['Entorno de trabajo', 'JavaScript autocorregido', 'Proyecto', 'Glosario'],
        'groups' => [],
    ],
];

foreach ($classrooms as $shortname => $info) {
    if ($DB->record_exists('course', ['shortname' => $shortname])) {
        cli_writeln("Aula de prueba existente: {$shortname}");
        continue;
    }
    $course = $generator->create_course([
        'shortname' => $shortname,
        'fullname' => $info['fullname'],
        'category' => $category->id,
        'summary' => $info['summary'],
        'format' => 'topics',
        'numsections' => count($info['sections']),
        'enablecompletion' => 1,
        'startdate' => $now - 30 * DAYSECS,
        'lang' => 'es',
        'groupmode' => count($info['groups']) ? SEPARATEGROUPS : NOGROUPS,
    ]);
    foreach ($info['sections'] as $index => $sectionname) {
        $DB->set_field('course_sections', 'name', $sectionname, ['course' => $course->id, 'section' => $index + 1]);
    }
    rebuild_course_cache($course->id, true);

    $generator->enrol_user($users['docente.demo']->id, $course->id, 'editingteacher');
    $generator->enrol_user($users['preceptor.demo']->id, $course->id, 'preceptor');
    foreach (['estudiante.demo', 'lucia.demo', 'joaquin.demo'] as $student) {
        $generator->enrol_user($users[$student]->id, $course->id, 'student');
    }
    foreach ($info['groups'] as $index => $groupname) {
        $group = $generator->create_group(['courseid' => $course->id, 'name' => $groupname]);
        $members = $index === 0 ? ['estudiante.demo', 'joaquin.demo'] : ['lucia.demo'];
        foreach ($members as $member) {
            $generator->create_group_member(['groupid' => $group->id, 'userid' => $users[$member]->id]);
        }
    }
    cli_writeln("Aula de prueba creada: {$shortname}");

    if ($shortname === 'PROG2-PRUEBA') {
        $generator->create_module('page', [
            'course' => $course->id,
            'section' => 1,
            'name' => 'Cómo trabajamos: GitHub, Drive y entregas',
            'contentformat' => FORMAT_HTML,
            'intro' => '<p>Herramientas del aula.</p>',
            'content' => '<h3>Ejercicios autocorregidos</h3><p>Los cuestionarios de código se corrigen solos: ' .
                'escribís tu solución, tocás <strong>Comprobar</strong> y ves qué pruebas pasan. Podés corregir y ' .
                'volver a intentar; cada intento extra descuenta un 10 %.</p>' .
                '<h3>Repositorio en GitHub</h3><p>Cargá tu usuario de GitHub en tu perfil (Datos personales). ' .
                'Para los trabajos prácticos, creá un repositorio y pegá su dirección en la entrega:</p>' .
                $codeblock("git init\ngit add .\ngit commit -m \"TP1: calculadora\"\ngit remote add origin " .
                    "https://github.com/tu-usuario/tp1-calculadora.git\ngit push -u origin main") .
                '<h3>Google Drive</h3><p>Si la escuela activó Google, en el selector de archivos aparece ' .
                '<strong>Google Drive</strong> para adjuntar documentos sin descargarlos.</p>',
            'completion' => COMPLETION_TRACKING_MANUAL,
        ]);
        $generator->create_module('forum', [
            'course' => $course->id,
            'section' => 1,
            'name' => 'Consultas de código',
            'intro' => '<p>Pegá tu código entre bloques y contá qué esperabas que pase. Nunca compartas contraseñas.</p>',
            'type' => 'qanda',
        ]);
        local_aulaet37_code_quiz($generator, $course, 2, 'Práctica autocorregida: funciones en Python',
            '<p>Tres funciones cortas. Se pueden reintentar; la nota sale sola al terminar.</p>', [
            [
                'name' => 'Python: es_par',
                'type' => 'python3',
                'text' => '<p>Escribí la función <code>es_par(n)</code> que devuelva <code>True</code> si el ' .
                    'entero <code>n</code> es par y <code>False</code> si no.</p>',
                'preload' => "def es_par(n):\n    ",
                'answer' => "def es_par(n):\n    return n % 2 == 0",
                'tests' => [
                    ['code' => 'print(es_par(4))', 'expected' => 'True', 'example' => true],
                    ['code' => 'print(es_par(7))', 'expected' => 'False', 'example' => true],
                    ['code' => 'print(es_par(0))', 'expected' => 'True'],
                    ['code' => 'print(es_par(-3))', 'expected' => 'False'],
                ],
            ],
            [
                'name' => 'Python: contar_vocales',
                'type' => 'python3',
                'text' => '<p>Escribí <code>contar_vocales(texto)</code>, que devuelva cuántas vocales ' .
                    '(a, e, i, o, u, con o sin tilde y en mayúscula o minúscula) tiene <code>texto</code>.</p>',
                'answer' => "def contar_vocales(texto):\n    return sum(1 for c in texto.lower() if c in 'aeiouáéíóú')",
                'tests' => [
                    ['code' => "print(contar_vocales('Escuela'))", 'expected' => '4', 'example' => true],
                    ['code' => "print(contar_vocales('Programación'))", 'expected' => '5'],
                    ['code' => "print(contar_vocales('xyz'))", 'expected' => '0'],
                ],
            ],
            [
                'name' => 'Python: factorial',
                'type' => 'python3',
                'text' => '<p>Escribí <code>factorial(n)</code> para enteros <code>n &gt;= 0</code>, sin usar ' .
                    '<code>math</code>.</p>',
                'answer' => "def factorial(n):\n    resultado = 1\n    for i in range(2, n + 1):\n" .
                    "        resultado *= i\n    return resultado",
                'tests' => [
                    ['code' => 'print(factorial(5))', 'expected' => '120', 'example' => true],
                    ['code' => 'print(factorial(0))', 'expected' => '1'],
                    ['code' => 'print(factorial(10))', 'expected' => '3628800'],
                ],
            ],
        ]);
        local_aulaet37_code_quiz($generator, $course, 3, 'Práctica autocorregida: programas en C',
            '<p>Programas completos que leen de la entrada estándar.</p>', [
            [
                'name' => 'C: suma de dos números',
                'type' => 'c_program',
                'text' => '<p>Escribí un programa en C que lea dos enteros y muestre su suma.</p>',
                'preload' => "#include <stdio.h>\n\nint main(void) {\n    \n    return 0;\n}",
                'answer' => "#include <stdio.h>\n\nint main(void) {\n    int a, b;\n    scanf(\"%d %d\", &a, &b);\n" .
                    "    printf(\"%d\\n\", a + b);\n    return 0;\n}",
                'tests' => [
                    ['stdin' => "3 4\n", 'expected' => '7', 'example' => true],
                    ['stdin' => "-10 25\n", 'expected' => '15'],
                    ['stdin' => "0 0\n", 'expected' => '0'],
                ],
            ],
            [
                'name' => 'C: triángulo de asteriscos',
                'type' => 'c_program',
                'text' => '<p>Leé un entero <code>n</code> y dibujá un triángulo de <code>n</code> filas: la fila ' .
                    '<code>i</code> tiene <code>i</code> asteriscos.</p>',
                'answer' => "#include <stdio.h>\n\nint main(void) {\n    int n;\n    scanf(\"%d\", &n);\n" .
                    "    for (int i = 1; i <= n; i++) {\n        for (int j = 0; j < i; j++) putchar('*');\n" .
                    "        putchar('\\n');\n    }\n    return 0;\n}",
                'tests' => [
                    ['stdin' => "3\n", 'expected' => "*\n**\n***", 'example' => true],
                    ['stdin' => "1\n", 'expected' => '*'],
                    ['stdin' => "5\n", 'expected' => "*\n**\n***\n****\n*****"],
                ],
            ],
        ]);
        $generator->create_module('assign', [
            'course' => $course->id,
            'section' => 4,
            'name' => 'TP1: calculadora en Python (repositorio en GitHub)',
            'intro' => '<p>Programá una calculadora de consola con suma, resta, multiplicación y división ' .
                '(con manejo de división por cero). Subí el código a un repositorio <strong>público</strong> de ' .
                'GitHub y pegá su dirección en el texto de la entrega. Opcional: adjuntá un informe desde ' .
                'Google Drive.</p>',
            'allowsubmissionsfromdate' => $now - DAYSECS,
            'duedate' => $now + 10 * DAYSECS,
            'assignsubmission_onlinetext_enabled' => 1,
            'assignsubmission_file_enabled' => 1,
            'assignsubmission_file_maxfiles' => 3,
            'assignsubmission_file_maxsizebytes' => 10 * 1024 * 1024,
            'assignsubmission_file_filetypes' => '.py,.zip,.pdf',
            'assignfeedback_comments_enabled' => 1,
            'grade' => 10,
            'completion' => COMPLETION_TRACKING_MANUAL,
        ]);
    } else {
        $generator->create_module('page', [
            'course' => $course->id,
            'section' => 1,
            'name' => 'Entorno de trabajo',
            'contentformat' => FORMAT_HTML,
            'intro' => '<p>Editor, navegador y control de versiones.</p>',
            'content' => '<p>Usamos Visual Studio Code, un navegador actualizado y Git. Estructura sugerida del ' .
                'proyecto:</p>' . $codeblock("mi-landing/\n  index.html\n  css/estilos.css\n  js/app.js\n  README.md") .
                '<p>Probá tu JavaScript en los ejercicios autocorregidos antes de integrarlo al proyecto.</p>',
            'completion' => COMPLETION_TRACKING_MANUAL,
        ]);
        local_aulaet37_code_quiz($generator, $course, 2, 'JavaScript autocorregido: funciones',
            '<p>Funciones de JavaScript ejecutadas con Node.js.</p>', [
            [
                'name' => 'JavaScript: doble',
                'type' => 'nodejs',
                'text' => '<p>Escribí la función <code>doble(x)</code> que devuelva el doble de <code>x</code>.</p>',
                'preload' => "function doble(x) {\n  \n}",
                'answer' => "function doble(x) {\n  return x * 2;\n}",
                'tests' => [
                    ['code' => 'console.log(doble(4));', 'expected' => '8', 'example' => true],
                    ['code' => 'console.log(doble(-1.5));', 'expected' => '-3'],
                ],
            ],
            [
                'name' => 'JavaScript: invertir',
                'type' => 'nodejs',
                'text' => '<p>Escribí <code>invertir(texto)</code>, que devuelva el texto al revés.</p>',
                'answer' => "function invertir(texto) {\n  return texto.split('').reverse().join('');\n}",
                'tests' => [
                    ['code' => "console.log(invertir('hola'));", 'expected' => 'aloh', 'example' => true],
                    ['code' => "console.log(invertir('ET37'));", 'expected' => '73TE'],
                    ['code' => "console.log(invertir(''));", 'expected' => ''],
                ],
            ],
        ]);
        $generator->create_module('assign', [
            'course' => $course->id,
            'section' => 3,
            'name' => 'Proyecto: landing page con HTML y CSS',
            'intro' => '<p>Armá una página de presentación accesible y adaptable a celulares. Entregá un .zip con ' .
                'el proyecto o la dirección del repositorio en GitHub.</p>',
            'allowsubmissionsfromdate' => $now - DAYSECS,
            'duedate' => $now + 14 * DAYSECS,
            'assignsubmission_onlinetext_enabled' => 1,
            'assignsubmission_file_enabled' => 1,
            'assignsubmission_file_maxfiles' => 5,
            'assignsubmission_file_maxsizebytes' => 20 * 1024 * 1024,
            'assignsubmission_file_filetypes' => '.zip,.html,.css,.js',
            'assignfeedback_comments_enabled' => 1,
            'grade' => 10,
            'completion' => COMPLETION_TRACKING_MANUAL,
        ]);
        $glossary = $generator->create_module('glossary', [
            'course' => $course->id,
            'section' => 4,
            'name' => 'Glosario web',
            'intro' => '<p>Términos del laboratorio. Cualquiera puede proponer entradas.</p>',
        ]);
        $glossarygenerator = $generator->get_plugin_generator('mod_glossary');
        foreach ([
            'DOM' => 'Representación en árbol del documento HTML que JavaScript puede leer y modificar.',
            'Responsive' => 'Diseño que se adapta al ancho de la pantalla, del celular al monitor.',
            'Commit' => 'Registro de un conjunto de cambios en Git, con un mensaje que explica qué se hizo.',
        ] as $concept => $definition) {
            $glossarygenerator->create_content($glossary, ['concept' => $concept, 'definition' => $definition,
                'userid' => $users['docente.demo']->id]);
        }
    }
}

// Weekly timetable of the demo classrooms (07:45 shift, 80-minute blocks), only for classrooms
// that do not have one yet. Group-only blocks show how a classroom splits its lab by division halves.
$blocks = [
    'MAT-DEMO' => [[1, '07:45', '09:05', 'Aula 12'], [3, '10:45', '12:05', 'Aula 12']],
    'LENG-DEMO' => [[1, '09:15', '10:35', 'Aula 12'], [4, '10:45', '12:05', 'Aula 12']],
    'PROG2-PRUEBA' => [[1, '10:45', '12:05', 'Laboratorio 2'], [2, '07:45', '10:35', 'Laboratorio 2', 'Grupo A'],
        [4, '07:45', '10:35', 'Laboratorio 2', 'Grupo B']],
    'ELEC-DEMO' => [[2, '13:00', '16:20', 'Taller de Electrónica', '', 'Traer guardapolvo']],
    'WEB-PRUEBA' => [[3, '07:45', '10:35', 'Laboratorio 3'], [5, '09:15', '10:35', 'Laboratorio 3']],
    'PROG-DEMO' => [[4, '13:00', '14:20', 'Laboratorio 1'], [5, '07:45', '09:05', 'Laboratorio 1']],
    'BD-DEMO' => [[5, '10:45', '12:05', 'Laboratorio 1']],
];
foreach ($blocks as $shortname => $courseblocks) {
    $course = $DB->get_record('course', ['shortname' => $shortname]);
    if (!$course || timetable::get_course_slots((int) $course->id)) {
        continue;
    }
    foreach ($courseblocks as $block) {
        [$weekday, $start, $end, $room] = $block;
        $groupid = 0;
        if (!empty($block[4])) {
            foreach (groups_get_all_groups($course->id) as $group) {
                if (str_starts_with($group->name, $block[4])) {
                    $groupid = (int) $group->id;
                }
            }
        }
        timetable::save_slot((object) ['weekday' => $weekday, 'starttime' => timetable::parse_time($start),
            'endtime' => timetable::parse_time($end), 'groupid' => $groupid, 'room' => $room,
            'notes' => $block[5] ?? ''], (int) $course->id);
    }
    cli_writeln("Horario cargado: {$shortname} (" . count($courseblocks) . ' bloque(s))');
}

// School announcements (site news forum, forced subscription) posted by the fictitious director.
$forum = institution::ensure_news_forum();
$forumgenerator = $generator->get_plugin_generator('mod_forum');
foreach ([
    ['Bienvenida a Aula ET37 (aviso de prueba)', '<p>Este es un aviso FICTICIO del tablero de la escuela. Los avisos ' .
        'llegan a todas las cuentas como notificación y por correo, y se ven en el área personal.</p>', 1],
    ['Semana de proyectos de computación (aviso de prueba)', '<p>FICTICIO: durante la semana de proyectos los ' .
        'laboratorios abren también por la tarde. Consultá el horario de tu división.</p>', 0],
] as [$subject, $message, $pinned]) {
    if ($DB->record_exists('forum_discussions', ['forum' => $forum->id, 'name' => $subject])) {
        continue;
    }
    $forumgenerator->create_discussion(['course' => SITEID, 'forum' => $forum->id, 'userid' => $users['directivo.demo']->id,
        'name' => $subject, 'subject' => $subject, 'message' => $message, 'pinned' => $pinned]);
    cli_writeln("Aviso publicado: {$subject}");
}

// School dates: site calendar events visible to every account.
$today = new DateTimeImmutable('today 10:00', core_date::get_server_timezone_object());
foreach ([
    ['Entrega de boletines (fecha de prueba)', 7, 2 * HOURSECS],
    ['Reunión con familias (fecha de prueba)', 10, 90 * MINSECS],
    ['Muestra de proyectos de computación (fecha de prueba)', 14, 4 * HOURSECS],
    ['Mesas de examen (fecha de prueba)', 21, 5 * HOURSECS],
] as [$name, $days, $duration]) {
    if ($DB->record_exists('event', ['eventtype' => 'site', 'name' => $name])) {
        continue;
    }
    // School days only: a date that falls on a weekend moves to the next Monday.
    $date = $today->modify("+{$days} days");
    if ((int) $date->format('N') >= 6) {
        $date = $date->modify('next monday 10:00');
    }
    calendar_event::create((object) [
        'name' => $name,
        'description' => '<p>Fecha FICTICIA para probar el calendario compartido de la escuela.</p>',
        'format' => FORMAT_HTML,
        'courseid' => SITEID,
        'groupid' => 0,
        'userid' => $users['directivo.demo']->id,
        'modulename' => '',
        'instance' => 0,
        'eventtype' => 'site',
        'timestart' => $date->getTimestamp(),
        'timeduration' => $duration,
        'visible' => 1,
    ], false);
    cli_writeln("Fecha de la escuela creada: {$name}");
}

cli_writeln('Aulas de prueba listas. Usuarios: docente.demo, estudiante.demo, lucia.demo, joaquin.demo, ' .
    'preceptor.demo, directivo.demo (contraseña: MOODLE_DEMO_PASSWORD).');
