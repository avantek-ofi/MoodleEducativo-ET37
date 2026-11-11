<?php
// This file is part of Aula ET37 - https://moodle.org/
//
// Aula ET37 is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * SCSS callbacks.
 *
 * @package    theme_et37
 * @copyright  2026 Aula ET37
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Boost preset followed by the Aula ET37 styles.
 *
 * @param theme_config $theme
 * @return string
 */
function theme_et37_get_main_scss_content($theme): string {
    return theme_boost_get_main_scss_content($theme) . "\n" .
        file_get_contents(__DIR__ . '/scss/et37.scss') . "
" .
        file_get_contents(__DIR__ . '/scss/activities.scss');
}

/**
 * Design tokens prepended before Boost so Bootstrap uses them.
 *
 * @param theme_config $theme
 * @return string
 */
function theme_et37_get_pre_scss($theme): string {
    return file_get_contents(__DIR__ . '/scss/pre.scss');
}

/**
 * Rules that must come after Boost's extra SCSS (it sets the login photo).
 *
 * @param theme_config $theme
 * @return string
 */
function theme_et37_get_extra_scss($theme): string {
    return file_get_contents(__DIR__ . '/scss/post.scss');
}
