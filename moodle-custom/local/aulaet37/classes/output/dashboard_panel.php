<?php
// This file is part of Aula ET37 - https://moodle.org/
//
// Aula ET37 is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_aulaet37\output;

use context_module;
use context_system;
use local_aulaet37\local\institution;
use local_aulaet37\local\timetable;
use moodle_url;
use renderer_base;

/**
 * Dashboard panel: school announcements, school dates and today's classes.
 *
 * Everything comes from native Moodle data: announcements are the discussions of the site
 * "Announcements" forum (forced subscription, so they also arrive as notifications and email),
 * school dates are site calendar events, and classes come from the classroom timetables.
 *
 * @package    local_aulaet37
 * @copyright  2026 Aula ET37
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dashboard_panel implements \core\output\renderable, \core\output\templatable {
    /** Announcements shown. */
    const ANNOUNCEMENTS = 3;

    /** School dates shown. */
    const DATES = 4;

    /**
     * Template context.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        global $USER;
        $announcements = $this->announcements();
        $dates = $this->dates();
        $today = timetable::today_items((int) $USER->id);
        [$weekday] = timetable::now();
        return [
            'announcements' => $announcements['items'],
            'hasannouncements' => !empty($announcements['items']),
            'announcementsurl' => $announcements['url'],
            'postannouncementurl' => $announcements['posturl'],
            'dates' => $dates['items'],
            'hasdates' => !empty($dates['items']),
            'datesurl' => (new moodle_url('/calendar/view.php', ['view' => 'upcoming', 'course' => SITEID]))->out(false),
            'adddateurl' => $dates['addurl'],
            'today' => $today,
            'hastoday' => !empty($today),
            'todayname' => timetable::weekday_name($weekday),
            'timetableurl' => (new moodle_url('/local/aulaet37/horario.php'))->out(false),
        ];
    }

    /**
     * Latest discussions of the site announcements forum the user can read.
     *
     * @return array
     */
    protected function announcements(): array {
        global $CFG;
        $result = ['items' => [], 'url' => '', 'posturl' => ''];
        $forum = institution::get_news_forum();
        if (!$forum) {
            return $result;
        }
        require_once($CFG->dirroot . '/mod/forum/lib.php');
        $cm = get_coursemodule_from_instance('forum', $forum->id, SITEID);
        if (!$cm || !$cm->visible) {
            return $result;
        }
        $context = context_module::instance($cm->id);
        if (!has_capability('mod/forum:viewdiscussion', $context)) {
            return $result;
        }
        $result['url'] = (new moodle_url('/mod/forum/view.php', ['f' => $forum->id]))->out(false);
        if (has_capability('mod/forum:addnews', $context)) {
            $result['posturl'] = (new moodle_url('/mod/forum/post.php', ['forum' => $forum->id]))->out(false);
        }
        $discussions = forum_get_discussions($cm, 'd.pinned DESC, d.timemodified DESC', true, -1, self::ANNOUNCEMENTS);
        foreach ($discussions as $discussion) {
            $message = file_rewrite_pluginfile_urls($discussion->message, 'pluginfile.php', $context->id, 'mod_forum',
                'post', $discussion->id);
            $text = html_to_text(format_text($message, $discussion->messageformat, ['context' => $context]), 0, false);
            $result['items'][] = [
                'subject' => format_string($discussion->name, true, ['context' => $context]),
                'url' => (new moodle_url('/mod/forum/discuss.php', ['d' => $discussion->discussion]))->out(false),
                'excerpt' => shorten_text(trim($text), 160),
                'date' => userdate($discussion->timemodified, get_string('strftimedatefullshort', 'langconfig')),
                'datetime' => date('c', $discussion->timemodified),
                'pinned' => !empty($discussion->pinned),
            ];
        }
        return $result;
    }

    /**
     * Upcoming site events (school dates visible to everyone).
     *
     * @return array
     */
    protected function dates(): array {
        global $DB;
        $items = [];
        $events = $DB->get_records_select('event', "eventtype = :type AND visible = 1 AND timestart + timeduration >= :now",
            ['type' => 'site', 'now' => usergetmidnight(time())], 'timestart ASC', 'id, name, timestart, timeduration', 0,
            self::DATES);
        foreach ($events as $event) {
            $items[] = [
                'name' => format_string($event->name, true, ['context' => context_system::instance()]),
                'day' => userdate($event->timestart, '%d'),
                'month' => userdate($event->timestart, '%b'),
                'when' => userdate($event->timestart, get_string('strftimedaydate', 'langconfig')),
                'datetime' => date('c', $event->timestart),
                'url' => (new moodle_url('/calendar/view.php', ['view' => 'day', 'time' => $event->timestart],
                    'event_' . $event->id))->out(false),
            ];
        }
        $addurl = '';
        if (has_capability('moodle/calendar:manageentries', context_system::instance())) {
            $addurl = (new moodle_url('/calendar/view.php', ['view' => 'month', 'course' => SITEID]))->out(false);
        }
        return ['items' => $items, 'addurl' => $addurl];
    }
}
