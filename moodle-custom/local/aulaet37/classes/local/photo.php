<?php
// This file is part of Aula ET37 - https://moodle.org/
//
// Aula ET37 is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_aulaet37\local;

use stdClass;

/**
 * Profile photo requirement for self-registered accounts.
 *
 * Moodle's signup form runs before the user has a session, so the native file
 * picker cannot be used there. The photo is requested right after the first login
 * (see {@see steps}).
 *
 * @package    local_aulaet37
 * @copyright  2026 Aula ET37
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class photo {
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
        if (!self::is_enabled() || !steps::applies_to($user)) {
            return false;
        }
        return empty($user->picture);
    }
}
