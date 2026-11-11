<?php
// This file is part of Aula ET37 - https://moodle.org/
//
// Aula ET37 is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Idempotent security baseline with native Moodle settings only.
 *
 * Environment:
 * - MOODLE_URL: cookies are marked "secure" when it starts with https://.
 * - MOODLE_MFA=1: second factor (TOTP app) required for administrators and directors.
 *
 * @package    local_aulaet37
 * @copyright  2026 Aula ET37
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognized] = cli_get_params(['help' => false], ['h' => 'help']);
if ($options['help']) {
    cli_writeln("Aplica la configuración de seguridad de Aula ET37 (contraseñas, bloqueo, sesiones, contenido, MFA).\n\n" .
        "php public/local/aulaet37/cli/security.php");
    exit(0);
}

\core\session\manager::set_user(get_admin());

$https = str_starts_with((string) $CFG->wwwroot, 'https://');

// Subnet of the internal "sandbox" network in compose.yaml, where only the Jobe server lives.
// Moodle blocks outgoing requests to private ranges (SSRF protection); this keeps every private
// range blocked except that subnet, so CodeRunner can reach Jobe.
$sandboxsubnet = '172.30.37.0/24';
$blockedhosts = [
    '127.0.0.0/8', '192.168.0.0/16', '10.0.0.0/8',
    // 172.16.0.0/12 except 172.30.37.0/24.
    '172.16.0.0/13', '172.24.0.0/14', '172.28.0.0/15', '172.30.0.0/19', '172.30.32.0/22', '172.30.36.0/24',
    '172.30.38.0/23', '172.30.40.0/21', '172.30.48.0/20', '172.30.64.0/18', '172.30.128.0/17', '172.31.0.0/16',
    '0.0.0.0', 'localhost', '169.254.169.254', '0000::1',
];

$settings = [
    // Passwords: 10+ characters with upper and lower case and a digit; no reuse of the last 5.
    'passwordpolicy' => 1,
    'minpasswordlength' => 10,
    'minpassworddigits' => 1,
    'minpasswordlower' => 1,
    'minpasswordupper' => 1,
    'minpasswordnonalphanum' => 0,
    'maxconsecutiveidentchars' => 3,
    'passwordreuselimit' => 5,
    'passwordchangelogout' => 1,
    'passwordchangetokendeletion' => 1,
    // Lock the account for 30 minutes after 10 failed attempts within 30 minutes.
    'lockoutthreshold' => 10,
    'lockoutwindow' => 30 * MINSECS,
    'lockoutduration' => 30 * MINSECS,
    // Nothing is visible without an account; no guest access; usernames are not revealed.
    'forcelogin' => 1,
    'forceloginforprofiles' => 1,
    'forceloginforprofileimage' => 1,
    'guestloginbutton' => 0,
    'protectusernames' => 1,
    'profilesforenrolledusersonly' => 1,
    'extendedusernamechars' => 0,
    // Lab computers are shared: never prefill the previous person's username.
    'rememberusername' => 0,
    // Sessions and cookies.
    'sessiontimeout' => 4 * HOURSECS,
    'cookiesecure' => $https ? 1 : 0,
    'allowframembedding' => 0,
    'referrerpolicy' => 'strict-origin-when-cross-origin',
    // Content: clean HTML everywhere, no embedded objects, no trusted text.
    'forceclean' => 1,
    'allowobjectembed' => 0,
    'enabletrusttext' => 0,
    'allowuserthemes' => 0,
    'allowcoursethemes' => 0,
    'allowcategorythemes' => 0,
    // E-mail.
    'verifychangedemail' => 1,
    'emailchangeconfirmation' => 1,
    'emailonlyfromnoreplyaddress' => 1,
    // Cron only from the command line (the cron container).
    'cronclionly' => 1,
    // Upload limit for the whole site: 50 MB.
    'maxbytes' => 50 * 1024 * 1024,
    // Outgoing requests.
    'curlsecurityblockedhosts' => implode("\n", $blockedhosts),
    'curlsecurityallowedport' => "443\n80",
];
$changed = [];
foreach ($settings as $name => $value) {
    if ((string) get_config('core', $name) !== (string) $value) {
        set_config($name, $value);
        $changed[] = $name;
    }
}
cli_writeln('Seguridad: ' . ($changed ? 'actualizado ' . implode(', ', $changed) : 'sin cambios') . '.');
cli_writeln("Seguridad: solo se permite acceso interno a la red {$sandboxsubnet} (Jobe).");

// Multi-factor authentication with Moodle's tool_mfa: TOTP app for administrators and directors.
// "Role" gives the points to everybody who is NOT in the listed roles, so only they need a second factor;
// "Grace period" lets them in for 7 days while they set up the app.
if (getenv('MOODLE_MFA') === '1') {
    $roleids = $DB->get_records_list('role', 'shortname', ['manager', 'rector', 'vicerrector', 'director', 'regente'],
        '', 'id');
    $mfa = [
        'tool_mfa' => ['enabled' => 1],
        'factor_totp' => ['enabled' => 1, 'weight' => 100, 'window' => 1],
        'factor_role' => ['enabled' => 1, 'weight' => 100, 'roles' => implode(',', array_merge(['admin'],
            array_keys($roleids)))],
        'factor_grace' => ['enabled' => 1, 'weight' => 100, 'graceperiod' => 7 * DAYSECS, 'forcesetup' => 1],
    ];
    foreach ($mfa as $plugin => $values) {
        foreach ($values as $name => $value) {
            set_config($name, $value, $plugin);
        }
    }
    cli_writeln('MFA: activado (app TOTP) para administradores, gestores y directivos; 7 días de gracia.');
} else if (get_config('tool_mfa', 'enabled')) {
    cli_writeln('MFA: ya estaba activo; se conserva (MOODLE_MFA no es 1).');
} else {
    cli_writeln('MFA: desactivado (definir MOODLE_MFA=1 en producción).');
}

purge_all_caches();
cli_writeln('Listo.');
