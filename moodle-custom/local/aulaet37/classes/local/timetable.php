<?php
// This file is part of Aula ET37 - https://moodle.org/
//
// Aula ET37 is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_aulaet37\local;

use context_course;
use context_system;
use core_date;
use DateTimeImmutable;
use moodle_url;
use stdClass;

/**
 * Weekly class timetable.
 *
 * Each classroom (course) defines its class blocks (day, start, end, room, optionally one group
 * as the division). A person's timetable is built from the classrooms where they are actively
 * enrolled and the groups they belong to, so it follows Moodle's own enrolments and groups and
 * never needs a separate list of students.
 *
 * @package    local_aulaet37
 * @copyright  2026 Aula ET37
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class timetable {
    /** Table name. */
    public const TABLE = 'local_aulaet37_slots';

    /** School days that can hold class blocks (ISO-8601: 1 = Monday ... 6 = Saturday). */
    public const WEEKDAYS = [1, 2, 3, 4, 5, 6];

    /** Earliest and latest minute allowed for a block. */
    public const DAY_START = 6 * 60;

    /** Latest minute allowed for a block. */
    public const DAY_END = 23 * 60;

    /**
     * Localised name of an ISO weekday.
     *
     * @param int $weekday
     * @return string
     */
    public static function weekday_name(int $weekday): string {
        $keys = [1 => 'monday', 2 => 'tuesday', 3 => 'wednesday', 4 => 'thursday', 5 => 'friday', 6 => 'saturday',
            7 => 'sunday'];
        return get_string($keys[$weekday], 'calendar');
    }

    /**
     * Weekday options for forms.
     *
     * @return array
     */
    public static function weekday_options(): array {
        $options = [];
        foreach (self::WEEKDAYS as $day) {
            $options[$day] = self::weekday_name($day);
        }
        return $options;
    }

    /**
     * Minutes after midnight as HH:MM.
     *
     * @param int $minutes
     * @return string
     */
    public static function format_time(int $minutes): string {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    /**
     * Parses HH:MM (or H:MM) into minutes after midnight.
     *
     * @param string $value
     * @return int|null Null when the value is not a valid time.
     */
    public static function parse_time(string $value): ?int {
        if (!preg_match('/^([01]?\d|2[0-3]):([0-5]\d)$/', trim($value), $matches)) {
            return null;
        }
        return (int) $matches[1] * 60 + (int) $matches[2];
    }

    /**
     * Current ISO weekday and minute in the current user's timezone.
     *
     * @return int[] [weekday, minute]
     */
    public static function now(): array {
        $now = new DateTimeImmutable('now', core_date::get_user_timezone_object());
        return [(int) $now->format('N'), (int) $now->format('G') * 60 + (int) $now->format('i')];
    }

    /**
     * Class blocks of one classroom.
     *
     * @param int $courseid
     * @return stdClass[]
     */
    public static function get_course_slots(int $courseid): array {
        global $DB;
        return $DB->get_records(self::TABLE, ['courseid' => $courseid], 'weekday ASC, starttime ASC, id ASC');
    }

    /**
     * Creates or updates a class block.
     *
     * @param stdClass $data Fields: id (optional), weekday, starttime, endtime (minutes), groupid, room, notes.
     * @param int $courseid
     * @return int Block id.
     */
    public static function save_slot(stdClass $data, int $courseid): int {
        global $DB;
        $record = (object) [
            'courseid' => $courseid,
            'groupid' => (int) ($data->groupid ?? 0),
            'weekday' => (int) $data->weekday,
            'starttime' => (int) $data->starttime,
            'endtime' => (int) $data->endtime,
            'room' => \core_text::substr(trim((string) ($data->room ?? '')), 0, 100),
            'notes' => \core_text::substr(trim((string) ($data->notes ?? '')), 0, 255),
            'timemodified' => time(),
        ];
        if (!empty($data->id)) {
            $existing = $DB->get_record(self::TABLE, ['id' => $data->id, 'courseid' => $courseid], 'id', MUST_EXIST);
            $record->id = $existing->id;
            $DB->update_record(self::TABLE, $record);
            return (int) $record->id;
        }
        $record->timecreated = $record->timemodified;
        return (int) $DB->insert_record(self::TABLE, $record);
    }

    /**
     * Deletes a block of the given classroom.
     *
     * @param int $slotid
     * @param int $courseid
     */
    public static function delete_slot(int $slotid, int $courseid): void {
        global $DB;
        $DB->delete_records(self::TABLE, ['id' => $slotid, 'courseid' => $courseid]);
    }

    /**
     * Courses where the user is actively enrolled and that the current viewer may see.
     *
     * @param int $userid
     * @return stdClass[]
     */
    protected static function visible_courses_of(int $userid): array {
        $courses = enrol_get_all_users_courses($userid, true, 'id, fullname, shortname, visible');
        return array_filter($courses, function($course) {
            return $course->visible || has_capability('moodle/course:viewhiddencourses', context_course::instance($course->id));
        });
    }

    /**
     * Whether the current user may see the timetable of another person.
     *
     * Allowed for the person themself, for whoever holds local/aulaet37:viewtimetables at site level
     * (directors) and for whoever holds it in one of the classrooms where the person is enrolled
     * (preceptors, teachers and coordinators of those classrooms).
     *
     * @param int $userid
     * @return bool
     */
    public static function can_view_user(int $userid): bool {
        global $USER;
        if (!isloggedin() || isguestuser()) {
            return false;
        }
        if ((int) $userid === (int) $USER->id) {
            return true;
        }
        if (has_capability('local/aulaet37:viewtimetables', context_system::instance())) {
            return true;
        }
        foreach (enrol_get_all_users_courses($userid, true, 'id') as $course) {
            if (has_capability('local/aulaet37:viewtimetables', context_course::instance($course->id))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Class blocks of a person, with display data, ordered by day and time.
     *
     * @param int $userid
     * @return array[] Each item: weekday, starttime, endtime, start, end, coursename, courseurl, room, notes,
     *                 teachers, groupname.
     */
    public static function get_user_items(int $userid): array {
        global $DB;
        $courses = self::visible_courses_of($userid);
        if (!$courses) {
            return [];
        }
        [$insql, $params] = $DB->get_in_or_equal(array_keys($courses), SQL_PARAMS_NAMED);
        $slots = $DB->get_records_select(self::TABLE, "courseid {$insql}", $params, 'weekday ASC, starttime ASC, id ASC');
        $usergroups = [];
        $items = [];
        foreach ($slots as $slot) {
            if ($slot->groupid) {
                if (!isset($usergroups[$slot->courseid])) {
                    $usergroups[$slot->courseid] = groups_get_user_groups($slot->courseid, $userid)[0] ?? [];
                }
                if (!in_array($slot->groupid, $usergroups[$slot->courseid])) {
                    continue;
                }
            }
            $items[] = self::export_slot($slot, $courses[$slot->courseid]);
        }
        return $items;
    }

    /**
     * Display data of one block.
     *
     * @param stdClass $slot
     * @param stdClass $course
     * @return array
     */
    public static function export_slot(stdClass $slot, stdClass $course): array {
        static $teachers = [];
        static $groupnames = [];
        $context = context_course::instance($course->id);
        if (!isset($teachers[$course->id])) {
            $names = [];
            $element = new \core_course_list_element(get_course($course->id));
            foreach ($element->get_course_contacts() as $contact) {
                $names[] = $contact['username'];
            }
            $teachers[$course->id] = implode(', ', array_unique($names));
        }
        $groupname = '';
        if ($slot->groupid) {
            if (!isset($groupnames[$slot->groupid])) {
                $group = groups_get_group($slot->groupid, 'id, name');
                $groupnames[$slot->groupid] = $group ? format_string($group->name, true, ['context' => $context]) : '';
            }
            $groupname = $groupnames[$slot->groupid];
        }
        return [
            'id' => (int) $slot->id,
            'weekday' => (int) $slot->weekday,
            'starttime' => (int) $slot->starttime,
            'endtime' => (int) $slot->endtime,
            'start' => self::format_time((int) $slot->starttime),
            'end' => self::format_time((int) $slot->endtime),
            'coursename' => format_string($course->fullname, true, ['context' => $context]),
            'courseurl' => (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
            'room' => (string) $slot->room,
            'notes' => (string) $slot->notes,
            'teachers' => $teachers[$course->id],
            'groupname' => $groupname,
            'tone' => 'tone' . ((int) $course->id % 6),
        ];
    }

    /**
     * Groups blocks by weekday for the weekly template; days without blocks are kept (Monday to
     * Friday) so the week reads the same for everybody, Saturday only when it has blocks.
     *
     * @param array[] $items Output of get_user_items() or export_slot().
     * @return array
     */
    public static function export_week(array $items): array {
        [$today, $minute] = self::now();
        $days = [];
        foreach (self::WEEKDAYS as $weekday) {
            $dayitems = array_values(array_filter($items, fn($item) => $item['weekday'] === $weekday));
            if ($weekday === 6 && !$dayitems) {
                continue;
            }
            foreach ($dayitems as &$item) {
                $item['isnow'] = $weekday === $today && $minute >= $item['starttime'] && $minute < $item['endtime'];
            }
            $days[] = [
                'weekday' => $weekday,
                'name' => self::weekday_name($weekday),
                'istoday' => $weekday === $today,
                'items' => $dayitems,
                'hasitems' => !empty($dayitems),
                'count' => count($dayitems),
            ];
        }
        return $days;
    }

    /**
     * Today's blocks of a person (empty on days without classes).
     *
     * @param int $userid
     * @return array[]
     */
    public static function today_items(int $userid): array {
        [$today, $minute] = self::now();
        $items = array_values(array_filter(self::get_user_items($userid), fn($item) => $item['weekday'] === $today));
        foreach ($items as &$item) {
            $item['isnow'] = $minute >= $item['starttime'] && $minute < $item['endtime'];
            $item['isdone'] = $minute >= $item['endtime'];
        }
        return $items;
    }
}
