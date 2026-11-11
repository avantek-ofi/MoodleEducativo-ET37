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

// Google Drive in the file picker (native repository_googledocs), on top of the Google service:
// students and teachers attach or link Drive files in submissions, resources and forums.
require_once($CFG->dirroot . '/repository/lib.php');
$googleissuer = null;
foreach (\core\oauth2\api::get_all_issuers(true) as $candidate) {
    if ($candidate->get('servicetype') === 'google' && $candidate->get('enabled')) {
        $googleissuer = $candidate;
    }
}
if ($googleissuer) {
    $driveoptions = ['issuerid' => $googleissuer->get('id'), 'pluginname' => 'Google Drive', 'documentformat' => 'docx',
        'drawingformat' => 'pdf', 'presentationformat' => 'pptx', 'spreadsheetformat' => 'xlsx',
        'defaultreturntype' => FILE_INTERNAL, 'supportedreturntypes' => 'both'];
    $drive = repository::get_type_by_typename('googledocs');
    if (!$drive) {
        $drive = new repository_type('googledocs', $driveoptions, true);
        $drive->create(true);
        cli_writeln('Google Drive: repositorio activado en el selector de archivos.');
    } else {
        $drive->update_options(['issuerid' => $googleissuer->get('id')]);
        $drive->update_visibility(true);
        cli_writeln('Google Drive: repositorio actualizado.');
    }
} else {
    cli_writeln('Google Drive: omitido (necesita Google OAuth 2 activo).');
}

// GitHub OAuth 2 (custom service): "Log in with GitHub" and linking the GitHub account from
// Preferences > Linked logins. GitHub only sends the e-mail when it is public on the account.
$githubid = local_aulaet37_env('GITHUB_CLIENT_ID');
$githubsecret = local_aulaet37_env('GITHUB_CLIENT_SECRET');
$githubissuer = null;
foreach (\core\oauth2\api::get_all_issuers(true) as $candidate) {
    if ($candidate->get('servicetype') === 'custom' && $candidate->get('name') === 'GitHub') {
        $githubissuer = $candidate;
    }
}
if ($githubid !== '' && $githubsecret !== '') {
    if (!$githubissuer) {
        // No base URL: with one Moodle would try OpenID Connect discovery, which GitHub does not offer.
        $githubissuer = \core\oauth2\api::create_issuer((object) [
            'name' => 'GitHub',
            'image' => 'https://github.githubassets.com/favicons/favicon.svg',
            'baseurl' => '',
            'loginscopes' => 'read:user user:email',
            'loginscopesoffline' => 'read:user user:email',
            'loginparams' => '',
            'loginparamsoffline' => '',
            'basicauth' => 0,
            'servicetype' => 'custom',
            'clientid' => $githubid,
            'clientsecret' => $githubsecret,
            'showonloginpage' => \core\oauth2\issuer::EVERYWHERE,
            'requireconfirmation' => 1,
            'enabled' => 1,
        ]);
        cli_writeln('GitHub OAuth 2: servicio creado.');
    } else {
        $githubissuer->set('clientid', $githubid);
        $githubissuer->set('clientsecret', $githubsecret);
        $githubissuer->set('enabled', 1);
        $githubissuer->update();
    }
    // Endpoints and field mappings, created when missing (also repairs a half-created service).
    $issuerid = $githubissuer->get('id');
    foreach ([
        'authorization_endpoint' => 'https://github.com/login/oauth/authorize',
        'token_endpoint' => 'https://github.com/login/oauth/access_token',
        'userinfo_endpoint' => 'https://api.github.com/user',
    ] as $name => $endpointurl) {
        if (!$DB->record_exists('oauth2_endpoint', ['issuerid' => $issuerid, 'name' => $name])) {
            \core\oauth2\api::create_endpoint((object) ['issuerid' => $issuerid, 'name' => $name, 'url' => $endpointurl]);
        }
    }
    // The numeric GitHub id identifies the account (logins can be renamed); the login fills the
    // "GitHub username" profile field.
    foreach (['id' => 'username', 'email' => 'email', 'name' => 'alternatename', 'avatar_url' => 'picture',
            'login' => 'profile_field_' . \local_aulaet37\local\profile_fields::GITHUB] as $external => $internal) {
        if (!$DB->record_exists('oauth2_user_field_mapping', ['issuerid' => $issuerid, 'externalfield' => $external])) {
            \core\oauth2\api::create_user_field_mapping((object) ['issuerid' => $issuerid, 'externalfield' => $external,
                'internalfield' => $internal]);
        }
    }
    local_aulaet37_enable_auth('oauth2');
    cli_writeln('GitHub OAuth 2: activado. Callback autorizado: ' . (new moodle_url('/admin/oauth2callback.php'))->out(false));
} else if ($githubissuer && $githubissuer->get('enabled')) {
    cli_writeln('GitHub OAuth 2: sin GITHUB_CLIENT_ID/SECRET; se conserva el servicio existente.');
} else {
    cli_writeln('GitHub OAuth 2: omitido (definir GITHUB_CLIENT_ID y GITHUB_CLIENT_SECRET).');
}

// The emoticon filter turns code such as "es_par(n)" or "x = (y)" into pictures, which breaks
// statements and examples in a programming school; it is disabled site-wide.
require_once($CFG->libdir . '/filterlib.php');
if (array_key_exists('emoticon', filter_get_global_states()) &&
        (int) filter_get_global_states()['emoticon']->active !== TEXTFILTER_DISABLED) {
    filter_set_global_state('emoticon', TEXTFILTER_DISABLED);
    cli_writeln('Filtro de emoticones: desactivado (alteraba el código de los enunciados).');
}

// CodeRunner: student code runs only in the internal Jobe container. Without this setting
// CodeRunner would send it to the public test server of the University of Canterbury.
if (\core_component::get_component_directory('qtype_coderunner')) {
    $jobehost = local_aulaet37_env('JOBE_HOST') ?: 'jobe';
    set_config('jobe_host', $jobehost, 'qtype_coderunner');
    set_config('jobesandbox_enabled', 1, 'qtype_coderunner');
    set_config('wsenabled', 0, 'qtype_coderunner');
    cli_writeln("CodeRunner: servidor Jobe {$jobehost} (red interna, sin Internet).");
} else {
    cli_writeln('CodeRunner: no instalado (reconstruir la imagen).');
}

purge_all_caches();
cli_writeln('Listo.');
