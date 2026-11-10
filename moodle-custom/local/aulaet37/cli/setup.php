<?php
// This file is part of Aula ET37 - https://moodle.org/
//
// Aula ET37 is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Idempotent site configuration for Aula ET37.
 *
 * Reads optional values from the environment:
 * - MOODLE_SMTP_HOST, MOODLE_NOREPLY_ADDRESS: outgoing mail (Mailpit in development).
 * - GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, GOOGLE_ALLOWED_DOMAINS: Google OAuth 2.
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
    cli_writeln("Configura registro, campos de perfil, correo y Google OAuth 2.\n\n" .
        "php public/local/aulaet37/cli/setup.php");
    exit(0);
}

\core\session\manager::set_user(get_admin());

/**
 * Returns a trimmed environment variable.
 *
 * @param string $name
 * @return string
 */
function local_aulaet37_env(string $name): string {
    $value = getenv($name);
    return $value === false ? '' : trim($value);
}

/**
 * Adds an authentication plugin to the enabled list.
 *
 * @param string $auth
 */
function local_aulaet37_enable_auth(string $auth): void {
    global $CFG;
    $enabled = array_filter(explode(',', (string) ($CFG->auth ?? '')));
    if (!in_array($auth, $enabled, true)) {
        $enabled[] = $auth;
        set_config('auth', implode(',', $enabled));
        \core\session\manager::gc();
    }
}

// Registration profile fields.
$created = \local_aulaet37\local\profile_fields::ensure();
cli_writeln('Campos de perfil: ' . ($created ? 'creados ' . implode(', ', $created) : 'ya existentes'));

// Native email-based self-registration.
local_aulaet37_enable_auth('email');
set_config('registerauth', 'email');
set_config('authloginviaemail', 1);
set_config('allowaccountssameemail', 0);
set_config('country', 'AR');
if (get_config('local_aulaet37', 'requirephoto') === false) {
    set_config('requirephoto', 1, 'local_aulaet37');
}
cli_writeln('Registro por correo: activado; inicio con usuario o correo permitido.');

// Spanish wording fixes through Moodle's local language pack (es_local), same mechanism as
// the Language customisation tool. Existing customisations are never replaced.
$overrides = [
    'loginstartsignup' => 'Crear una cuenta',
    'loginwelcomeback' => '¡Qué bueno verte de nuevo!',
];
$langdir = $CFG->dataroot . '/lang/es_local';
$langfile = $langdir . '/moodle.php';
check_dir_exists($langdir);
$content = is_readable($langfile) ? file_get_contents($langfile) : "<?php\n\ndefined('MOODLE_INTERNAL') || die();\n\n";
foreach ($overrides as $key => $text) {
    if (strpos($content, "\$string['{$key}']") === false) {
        $content .= "\$string['{$key}'] = " . var_export($text, true) . ";\n";
    }
}
file_put_contents($langfile, $content);
cli_writeln('Textos locales en español: ' . implode(', ', array_keys($overrides)));

// Course colours used by Moodle to generate course card patterns.
$palette = ['#5b3fd1', '#0f8f84', '#d6337a', '#d9650b', '#7c4ddb', '#127fa5', '#c2410c', '#9b2fae', '#2f855a', '#4f5bd5'];
foreach ($palette as $index => $colour) {
    set_config('coursecolor' . ($index + 1), $colour, 'core_admin');
}

// Outgoing mail.
$smtphost = local_aulaet37_env('MOODLE_SMTP_HOST');
if ($smtphost !== '') {
    set_config('smtphosts', $smtphost);
    set_config('smtpsecure', '');
    set_config('smtpauthtype', 'LOGIN');
    $noreply = local_aulaet37_env('MOODLE_NOREPLY_ADDRESS') ?: 'noreply@aula-et37.example';
    set_config('noreplyaddress', $noreply);
    cli_writeln("Correo saliente: {$smtphost}");
} else {
    cli_writeln('Correo saliente: sin MOODLE_SMTP_HOST, se conserva la configuración actual.');
}

// Google OAuth 2.
$clientid = local_aulaet37_env('GOOGLE_CLIENT_ID');
$clientsecret = local_aulaet37_env('GOOGLE_CLIENT_SECRET');
if ($clientid !== '' && $clientsecret !== '') {
    $issuer = null;
    foreach (\core\oauth2\api::get_all_issuers(true) as $candidate) {
        if ($candidate->get('servicetype') === 'google') {
            $issuer = $candidate;
            break;
        }
    }
    if (!$issuer) {
        $issuer = \core\oauth2\api::create_standard_issuer('google');
        cli_writeln('Google OAuth 2: servicio creado.');
    }
    $issuer->set('clientid', $clientid);
    $issuer->set('clientsecret', $clientsecret);
    $issuer->set('enabled', 1);
    $issuer->set('showonloginpage', \core\oauth2\issuer::EVERYWHERE);
    // Google only returns verified addresses; a second confirmation email is not required.
    $issuer->set('requireconfirmation', 0);
    $issuer->set('alloweddomains', local_aulaet37_env('GOOGLE_ALLOWED_DOMAINS'));
    $issuer->update();
    local_aulaet37_enable_auth('oauth2');
    cli_writeln('Google OAuth 2: activado. URI de redirección autorizada: ' .
        (new moodle_url('/admin/oauth2callback.php'))->out(false));
} else {
    cli_writeln('Google OAuth 2: omitido (definir GOOGLE_CLIENT_ID y GOOGLE_CLIENT_SECRET).');
}

purge_all_caches();
cli_writeln('Listo.');
