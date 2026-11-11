<?php
// This file is part of Aula ET37 - https://moodle.org/
//
// Aula ET37 is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace theme_et37\output;

use context_course;
use context_system;
use context_user;
use core_course_category;
use moodle_page;
use moodle_url;
use renderer_base;

/**
 * Left navigation drawer: every native Moodle page the current user can open, grouped by purpose.
 *
 * It never grants access: each link is shown only when the capability or site setting that the
 * target page checks is present, so hiding an entry here does not replace Moodle's own checks.
 *
 * @package    theme_et37
 * @copyright  2026 Aula ET37
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sidenav implements \core\output\templatable {
    /** Maximum number of enrolled courses listed directly in the drawer. */
    const MAX_COURSES = 12;

    /** @var moodle_page */
    protected $page;

    /**
     * Constructor.
     *
     * @param moodle_page $page
     */
    public function __construct(moodle_page $page) {
        $this->page = $page;
    }

    /**
     * Template context.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        if (!isloggedin() || isguestuser() || during_initial_install() || $this->page->pagelayout === 'maintenance') {
            return ['sections' => [], 'hasthiscourse' => false];
        }
        $sections = [];
        $course = $this->page->course;
        $incourse = $course && (int) $course->id !== (int) SITEID;
        if ($incourse) {
            $sections[] = $this->section('thiscourse', $this->course_links($course));
        }
        $sections[] = $this->section('myspace', $this->personal_links());
        $sections[] = $this->section('classrooms', $this->course_list_links());
        $sections[] = $this->section('management', $this->management_links());
        $sections = array_values(array_filter($sections, fn($section) => !empty($section['links'])));
        foreach ($sections as $index => &$section) {
            $section['index'] = $index;
            $section['isthiscourse'] = $section['key'] === 'thiscourse';
            // Inside a course the drawer focuses on it; the other groups start folded unless they hold the current page.
            $section['open'] = !$incourse || $section['isthiscourse'] || $section['hascurrent'];
        }
        return ['sections' => $sections, 'hasthiscourse' => $incourse];
    }

    /**
     * Builds a section and marks the link of the current page.
     *
     * @param string $key Language string key in theme_et37.
     * @param array $links
     * @return array
     */
    protected function section(string $key, array $links): array {
        $hascurrent = false;
        foreach ($links as &$link) {
            $link['current'] = $this->is_current($link['url']);
            $hascurrent = $hascurrent || $link['current'];
            $link['url'] = $link['url']->out(false);
        }
        return [
            'key' => $key,
            'title' => get_string('sidenav' . $key, 'theme_et37'),
            'links' => $links,
            'hascurrent' => $hascurrent,
        ];
    }

    /**
     * Whether a link points to the page being displayed.
     *
     * @param moodle_url $url
     * @return bool
     */
    protected function is_current(moodle_url $url): bool {
        if (!$this->page->has_set_url()) {
            return false;
        }
        return $this->page->url->compare($url, URL_MATCH_PARAMS);
    }

    /**
     * One link entry.
     *
     * @param moodle_url $url
     * @param string $label Already translated text.
     * @param string $icon Core pix identifier.
     * @return array
     */
    protected function link(moodle_url $url, string $label, string $icon): array {
        return ['url' => $url, 'label' => $label, 'icon' => $icon];
    }

    /**
     * Personal pages available to every authenticated user.
     *
     * @return array
     */
    protected function personal_links(): array {
        global $CFG, $USER;
        $usercontext = context_user::instance($USER->id);
        $links = [
            $this->link(new moodle_url('/my/'), get_string('myhome'), 'i/dashboard'),
            $this->link(new moodle_url('/', ['redirect' => 0]), get_string('sitehome'), 'i/home'),
        ];
        if (!empty($CFG->enablemycourses)) {
            $links[] = $this->link(new moodle_url('/my/courses.php'), get_string('mycourses'), 'i/course');
        }
        if (class_exists('\local_aulaet37\local\timetable')) {
            $links[] = $this->link(new moodle_url('/local/aulaet37/horario.php'), get_string('mytimetable', 'local_aulaet37'),
                'i/scheduled');
        }
        if (class_exists('\local_aulaet37\local\institution') &&
                ($newsforum = \local_aulaet37\local\institution::get_news_forum())) {
            $links[] = $this->link(new moodle_url('/mod/forum/view.php', ['f' => $newsforum->id]),
                get_string('newsforumname', 'local_aulaet37'), 'i/bullhorn');
        }
        $links[] = $this->link(new moodle_url('/calendar/view.php', ['view' => 'upcoming']),
            get_string('upcomingtasks', 'theme_et37'), 'i/duration');
        $links[] = $this->link(new moodle_url('/calendar/view.php', ['view' => 'month']),
            get_string('calendar', 'calendar'), 'i/calendar');
        $links[] = $this->link(new moodle_url('/grade/report/overview/index.php'), get_string('grades', 'grades'), 'i/grades');
        if (!empty($CFG->messaging)) {
            $links[] = $this->link(new moodle_url('/message/index.php'), get_string('messages', 'message'), 't/message');
        }
        $links[] = $this->link(new moodle_url('/message/output/popup/notifications.php'),
            get_string('notifications', 'message'), 'i/notifications');
        if (has_capability('moodle/user:manageownfiles', $usercontext)) {
            $links[] = $this->link(new moodle_url('/user/files.php'), get_string('privatefiles'), 'i/privatefiles');
        }
        if (!empty($CFG->enablebadges) && has_capability('moodle/badges:manageownbadges', $usercontext)) {
            $links[] = $this->link(new moodle_url('/badges/mybadges.php'), get_string('badges', 'badges'), 'i/badge');
        }
        if (get_config('core_competency', 'enabled') && has_capability('moodle/competency:planviewown', $usercontext)) {
            $links[] = $this->link(new moodle_url('/admin/tool/lp/plans.php'), get_string('learningplans', 'tool_lp'),
                'i/competencies');
        }
        if (!empty($CFG->enableblogs) && has_capability('moodle/blog:view', context_system::instance())) {
            $links[] = $this->link(new moodle_url('/blog/index.php', ['userid' => $USER->id]), get_string('blogs', 'blog'),
                'i/news');
        }
        $links[] = $this->link(new moodle_url('/user/profile.php'), get_string('profile'), 'i/user');
        $links[] = $this->link(new moodle_url('/user/preferences.php'), get_string('preferences'), 'i/settings');
        return $links;
    }

    /**
     * Enrolled courses plus the course catalogue.
     *
     * @return array
     */
    protected function course_list_links(): array {
        $links = [];
        $courses = enrol_get_my_courses(['id', 'fullname'], 'visible DESC, fullname ASC', self::MAX_COURSES);
        foreach ($courses as $course) {
            $name = format_string($course->fullname, true, ['context' => context_course::instance($course->id)]);
            $links[] = $this->link(new moodle_url('/course/view.php', ['id' => $course->id]), $name, 'i/course');
        }
        // Directors and coordinators see classrooms they are not enrolled in from the catalogue.
        $links[] = $this->link(new moodle_url('/course/index.php'), get_string('sidenavallclassrooms', 'theme_et37'),
            'i/withsubcat');
        return $links;
    }

    /**
     * Pages of the current course, filtered by the capabilities those pages require.
     *
     * @param \stdClass $course
     * @return array
     */
    protected function course_links(\stdClass $course): array {
        global $CFG;
        $context = context_course::instance($course->id);
        $id = $course->id;
        $links = [$this->link(new moodle_url('/course/view.php', ['id' => $id]), get_string('sidenavcoursehome', 'theme_et37'),
            'i/course')];
        if (has_capability('moodle/course:viewparticipants', $context)) {
            $links[] = $this->link(new moodle_url('/user/index.php', ['id' => $id]), get_string('participants'), 'i/users');
        }
        if (class_exists('\local_aulaet37\local\timetable')) {
            $links[] = $this->link(new moodle_url('/local/aulaet37/horario_aula.php', ['id' => $id]),
                get_string('coursetimetable', 'local_aulaet37'), 'i/scheduled');
        }
        if (has_any_capability(['moodle/grade:viewall', 'moodle/grade:view'], $context)) {
            $links[] = $this->link(new moodle_url('/grade/report/index.php', ['id' => $id]), get_string('grades', 'grades'),
                'i/grades');
        }
        // Attendance (mod_attendance) instances the user can see in this course.
        if (\core_component::get_component_directory('mod_attendance')) {
            $modinfo = get_fast_modinfo($course);
            foreach ($modinfo->get_instances_of('attendance') as $cm) {
                if ($cm->uservisible) {
                    $links[] = $this->link(new moodle_url('/mod/attendance/view.php', ['id' => $cm->id]),
                        $cm->get_formatted_name(), 'i/checkpermissions');
                }
            }
        }
        if (!empty($CFG->enablecompletion) && $course->enablecompletion &&
                has_capability('report/progress:view', $context)) {
            $links[] = $this->link(new moodle_url('/report/progress/index.php', ['course' => $id]),
                get_string('pluginname', 'report_progress'), 'i/completion_self');
        }
        if (has_capability('moodle/site:viewreports', $context)) {
            $links[] = $this->link(new moodle_url('/report/view.php', ['courseid' => $id]), get_string('reports'), 'i/report');
        }
        if (has_capability('moodle/course:managegroups', $context)) {
            $links[] = $this->link(new moodle_url('/group/index.php', ['id' => $id]), get_string('groups'), 'i/group');
        }
        if (!empty($CFG->enablebadges) && !empty($CFG->badges_allowcoursebadges) &&
                has_capability('moodle/badges:viewbadges', $context)) {
            $links[] = $this->link(new moodle_url('/badges/index.php', ['type' => 2, 'id' => $id]),
                get_string('badges', 'badges'), 'i/badge');
        }
        if (has_capability('moodle/course:update', $context)) {
            $links[] = $this->link(new moodle_url('/course/edit.php', ['id' => $id]), get_string('settings'), 'i/settings');
        }
        if (has_capability('moodle/backup:backupcourse', $context)) {
            $links[] = $this->link(new moodle_url('/backup/backup.php', ['id' => $id]), get_string('backup'), 'i/backup');
        }
        return $links;
    }

    /**
     * Site and category management, only for roles that hold the matching capabilities.
     *
     * @return array
     */
    protected function management_links(): array {
        $system = context_system::instance();
        $links = [];
        if (has_capability('moodle/site:config', $system)) {
            $links[] = $this->link(new moodle_url('/admin/search.php'), get_string('administrationsite'), 'i/settings');
        }
        if (core_course_category::has_capability_on_any(['moodle/category:manage', 'moodle/course:create'])) {
            $links[] = $this->link(new moodle_url('/course/management.php'), get_string('coursemgmt', 'admin'), 'i/withsubcat');
        }
        if ($category = core_course_category::get_nearest_editable_subcategory(core_course_category::top(), ['create'])) {
            $links[] = $this->link(new moodle_url('/course/edit.php', ['category' => $category->id]),
                get_string('sidenavnewclassroom', 'theme_et37'), 't/add');
        }
        if (has_capability('moodle/user:update', $system)) {
            $links[] = $this->link(new moodle_url('/admin/user.php'), get_string('userlist', 'admin'), 'i/users');
        }
        if (has_capability('moodle/cohort:view', $system)) {
            $links[] = $this->link(new moodle_url('/cohort/index.php'), get_string('cohorts', 'cohort'), 'i/cohort');
        }
        if (has_capability('moodle/role:manage', $system)) {
            $links[] = $this->link(new moodle_url('/admin/roles/manage.php'), get_string('defineroles', 'role'),
                'i/permissions');
        }
        if (has_capability('moodle/role:assign', $system)) {
            $links[] = $this->link(new moodle_url('/admin/roles/assign.php', ['contextid' => $system->id]),
                get_string('assignglobalroles', 'role'), 'i/assignroles');
        }
        if (has_capability('report/log:view', $system)) {
            $links[] = $this->link(new moodle_url('/report/log/index.php', ['id' => SITEID]), get_string('logs'), 'i/log');
        }
        return $links;
    }
}
