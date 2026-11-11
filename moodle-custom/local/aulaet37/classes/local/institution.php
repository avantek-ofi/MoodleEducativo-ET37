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
 * School-wide channels built on native Moodle features.
 *
 * - Announcements board: the site "Announcements" forum (front page news forum), with forced
 *   subscription so every account receives each post as a notification and by email.
 * - Shared dates: site calendar events, visible to every account.
 *
 * @package    local_aulaet37
 * @copyright  2026 Aula ET37
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class institution {
    /**
     * The site announcements forum, if it exists (never creates it).
     *
     * @return stdClass|null
     */
    public static function get_news_forum(): ?stdClass {
        global $DB;
        $forums = $DB->get_records('forum', ['course' => SITEID, 'type' => 'news'], 'id ASC', '*', 0, 1);
        return $forums ? reset($forums) : null;
    }

    /**
     * Creates the site announcements forum if needed, names it and forces subscription.
     *
     * @return stdClass The forum record.
     */
    public static function ensure_news_forum(): stdClass {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/forum/lib.php');
        $forum = forum_get_course_forum(SITEID, 'news');
        $changes = [];
        if ((int) $forum->forcesubscribe !== FORUM_FORCESUBSCRIBE) {
            $changes['forcesubscribe'] = FORUM_FORCESUBSCRIBE;
        }
        // Moodle's default English name is replaced only while nobody renamed it.
        $defaultnames = ['Site announcements'];
        foreach (['en', 'es', current_language()] as $lang) {
            $defaultnames[] = get_string_manager()->get_string('namenews', 'forum', null, $lang);
            $defaultnames[] = get_string_manager()->get_string('sitenews', 'moodle', null, $lang);
        }
        if (in_array($forum->name, $defaultnames, true) || trim((string) $forum->name) === '') {
            $changes['name'] = get_string('newsforumname', 'local_aulaet37');
            $changes['intro'] = get_string('newsforumintro', 'local_aulaet37');
        }
        if ($changes) {
            foreach ($changes as $field => $value) {
                $DB->set_field('forum', $field, $value, ['id' => $forum->id]);
                $forum->$field = $value;
            }
            rebuild_course_cache(SITEID, true);
        }
        return $forum;
    }
}
