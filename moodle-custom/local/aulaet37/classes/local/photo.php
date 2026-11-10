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
 * Profile photo requirement for self-registered accounts.
 *
 * Moodle's signup form runs before the user has a session, so the native file
 * picker cannot be used there. The photo is requested right after the first login.
 *
 * @package    local_aulaet37
 * @copyright  2026 Aula ET37
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class photo {
    /** Authentication methods whose accounts must provide a photo. */
    public const AUTHS = ['email', 'oauth2'];

    /** Scripts that must stay reachable while the photo is missing. */
    protected const EXEMPT_SCRIPTS = [
        '/local/aulaet37/photo.php',
        '/login/logout.php',
        '/user/edit.php',
        '/user/policy.php',
        '/admin/tool/policy/index.php',
        '/admin/tool/policy/view.php',
        '/draftfile.php',
        '/pluginfile.php',
    ];

    /**
     * Whether the requirement is enabled site-wide.
     *
     * @return bool
     */
    public static function is_enabled(): bool {
        global $CFG;
        return !empty(get_config('local_aulaet37', 'requirephoto')) && empty($CFG->disableuserimages);
    }

    /**
     * Whether the given user must upload a photo now.
     *
     * @param stdClass $user
     * @return bool
     */
    public static function is_required_for(stdClass $user): bool {
        if (!self::is_enabled() || empty($user->id) || isguestuser($user) || is_siteadmin($user)) {
            return false;
        }
        if (\core\session\manager::is_loggedinas()) {
            return false;
        }
        if (empty($user->auth) || !in_array($user->auth, self::AUTHS, true)) {
            return false;
        }
        if (!has_capability('moodle/user:editownprofile', \context_system::instance(), $user)) {
            return false;
        }
        return empty($user->picture);
    }

    /**
     * Redirects the current user to the photo step when required.
     *
     * @param bool $preventredirect
     * @param bool $setwantsurltome
     */
    public static function enforce(bool $preventredirect, bool $setwantsurltome): void {
        global $USER, $SESSION, $SCRIPT;

        if ($preventredirect || CLI_SCRIPT || AJAX_SCRIPT || (defined('WS_SERVER') && WS_SERVER)) {
            return;
        }
        if (!self::is_required_for($USER)) {
            return;
        }
        if (in_array((string) $SCRIPT, self::EXEMPT_SCRIPTS, true)) {
            return;
        }
        if ($setwantsurltome) {
            $SESSION->wantsurl = qualified_me();
        }
        redirect(new moodle_url('/local/aulaet37/photo.php'));
    }
}
