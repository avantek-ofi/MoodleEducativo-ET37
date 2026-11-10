<?php
// This file is part of Aula ET37 - https://moodle.org/
//
// Aula ET37 is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace theme_et37\output;

use moodle_url;

/**
 * Core renderer: adds date and shortcuts to the native dashboard header.
 *
 * @package    theme_et37
 * @copyright  2026 Aula ET37
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_renderer extends \theme_boost\output\core_renderer {
    /**
     * Header with the dashboard shortcuts appended inside it.
     *
     * @return string
     */
    public function full_header() {
        $html = parent::full_header();
        if ($this->page->pagetype !== 'my-index' || !isloggedin() || isguestuser()) {
            return $html;
        }
        $extra = $this->render_from_template('theme_et37/dashboard_quicklinks', $this->dashboard_quicklinks());
        $count = 0;
        $result = preg_replace('~</header>\s*$~', $extra . '</header>', $html, 1, $count);
        return $count ? $result : $html . $extra;
    }

    /**
     * Context for the dashboard shortcuts. Every link goes to a native Moodle page.
     *
     * @return array
     */
    protected function dashboard_quicklinks(): array {
        global $CFG;
        $links = [];
        // "My courses" only exists when the site enables it; otherwise the dashboard already lists courses.
        if (!empty($CFG->enablemycourses)) {
            $links[] = ['url' => new moodle_url('/my/courses.php'), 'label' => get_string('mycourses'),
                'icon' => 'i/course', 'tone' => 'violet'];
        }
        $links = array_merge($links, [
            ['url' => new moodle_url('/calendar/view.php', ['view' => 'upcoming']),
                'label' => get_string('upcomingtasks', 'theme_et37'), 'icon' => 'i/scheduled', 'tone' => 'orange'],
            ['url' => new moodle_url('/calendar/view.php', ['view' => 'month']), 'label' => get_string('calendar', 'calendar'),
                'icon' => 'i/calendar', 'tone' => 'teal'],
            ['url' => new moodle_url('/grade/report/overview/index.php'), 'label' => get_string('grades', 'grades'),
                'icon' => 'i/grades', 'tone' => 'pink'],
        ]);
        if (!empty($CFG->messaging)) {
            $links[] = ['url' => new moodle_url('/message/index.php'), 'label' => get_string('messages', 'message'),
                'icon' => 't/messages', 'tone' => 'violet'];
        }
        foreach ($links as &$link) {
            $link['url'] = $link['url']->out(false);
        }
        return [
            'today' => userdate(time(), get_string('strftimedaydate', 'langconfig')),
            'links' => $links,
        ];
    }
}
