<?php
// This file is part of Aula ET37 - https://moodle.org/
//
// Aula ET37 is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_aulaet37\local;

use moodle_url;
use stdClass;

/**
 * Mandatory steps after login for self-registered and Google accounts.
 *
 * Order: personal data (DNI and birth date), then profile photo. Site
 * administrators, manual accounts and "log in as" sessions are exempt; that is
 * why the profile fields are not marked as required in Moodle itself.
 *
 * @package    local_aulaet37
 * @copyright  2026 Aula ET37
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class steps {
    /** Authentication methods whose accounts must complete the steps. */
    public const AUTHS = ['email', 'oauth2'];

    /** Page of the personal data step. */
    public const DATA_SCRIPT = '/local/aulaet37/datos.php';

    /** Page of the photo step. */
    public const PHOTO_SCRIPT = '/local/aulaet37/photo.php';

    /** Scripts that must stay reachable while a step is pending. */
    protected const EXEMPT_SCRIPTS = [
        '/login/logout.php',
        '/user/edit.php',
        '/user/policy.php',
        '/admin/tool/policy/index.php',
        '/admin/tool/policy/view.php',
        '/draftfile.php',
        '/pluginfile.php',
    ];

    /**
     * Whether the steps apply to this account at all.
     *
     * @param stdClass $user
     * @return bool
     */
    public static function applies_to(stdClass $user): bool {
        if (empty($user->id) || isguestuser($user) || is_siteadmin($user)) {
            return false;
        }
        if (\core\session\manager::is_loggedinas()) {
            return false;
        }
        if (empty($user->auth) || !in_array($user->auth, self::AUTHS, true)) {
            return false;
        }
        return has_capability('moodle/user:editownprofile', \context_system::instance(), $user);
    }

    /**
     * Returns the script of the first pending step for the user, if any.
     *
     * @param stdClass $user
     * @return string|null
     */
    public static function pending_step(stdClass $user): ?string {
        if (personal_data::is_required_for($user)) {
            return self::DATA_SCRIPT;
        }
        if (photo::is_required_for($user)) {
            return self::PHOTO_SCRIPT;
        }
        return null;
    }

    /**
     * Redirects the current user to the first pending step.
     *
     * @param bool $preventredirect
     * @param bool $setwantsurltome
     */
    public static function enforce(bool $preventredirect, bool $setwantsurltome): void {
        global $USER, $SESSION, $SCRIPT;

        if ($preventredirect || CLI_SCRIPT || AJAX_SCRIPT || (defined('WS_SERVER') && WS_SERVER)) {
            return;
        }
        $script = (string) $SCRIPT;
        if (in_array($script, self::EXEMPT_SCRIPTS, true)) {
            return;
        }
        $step = self::pending_step($USER);
        if ($step === null || $step === $script) {
            return;
        }
        if ($setwantsurltome && !in_array($script, [self::DATA_SCRIPT, self::PHOTO_SCRIPT], true)) {
            $SESSION->wantsurl = qualified_me();
        }
        redirect(new moodle_url($step));
    }

    /**
     * Where to go after completing a step.
     *
     * @return moodle_url|string
     */
    public static function return_url() {
        global $SESSION;
        $url = !empty($SESSION->wantsurl) ? $SESSION->wantsurl : new moodle_url('/my/');
        unset($SESSION->wantsurl);
        return $url;
    }
}
