<?php
// This file is part of Aula ET37 - https://moodle.org/
//
// Aula ET37 is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_aulaet37;

use local_aulaet37\local\timetable;

/**
 * Keeps the timetable consistent when classrooms or groups disappear.
 *
 * @package    local_aulaet37
 * @copyright  2026 Aula ET37
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {
    /**
     * Removes the blocks of a deleted classroom.
     *
     * @param \core\event\course_deleted $event
     */
    public static function course_deleted(\core\event\course_deleted $event): void {
        global $DB;
        $DB->delete_records(timetable::TABLE, ['courseid' => $event->courseid]);
    }

    /**
     * Removes the blocks that were only for a deleted group.
     *
     * @param \core\event\group_deleted $event
     */
    public static function group_deleted(\core\event\group_deleted $event): void {
        global $DB;
        $DB->delete_records(timetable::TABLE, ['groupid' => $event->objectid]);
    }
}
