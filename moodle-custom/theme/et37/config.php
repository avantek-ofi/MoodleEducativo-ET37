<?php
// This file is part of Aula ET37 - https://moodle.org/
//
// Aula ET37 is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Aula ET37 theme configuration (child of Boost).
 *
 * @package    theme_et37
 * @copyright  2026 Aula ET37
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/theme/boost/lib.php');
require_once(__DIR__ . '/lib.php');

$THEME->name = 'et37';
$THEME->parents = ['boost'];
$THEME->sheets = [];
$THEME->editor_sheets = [];
$THEME->scss = function($theme) {
    return theme_et37_get_main_scss_content($theme);
};
$THEME->prescsscallback = 'theme_et37_get_pre_scss';
$THEME->extrascsscallback = 'theme_et37_get_extra_scss';
$THEME->enable_dock = false;

$THEME->yuicssmodules = [];
$THEME->rendererfactory = 'theme_overridden_renderer_factory';
// Same as Boost (theme settings are not inherited): no forced Navigation/Administration blocks,
// since the left drawer and the secondary navigation already cover them; "Add a block" in the drawer.
$THEME->requiredblocks = '';
$THEME->addblockposition = BLOCK_ADDBLOCK_POSITION_FLATNAV;
$THEME->iconsystem = \core\output\icon_system::FONTAWESOME;
$THEME->haseditswitch = true;
$THEME->usescourseindex = true;
$THEME->activityheaderconfig = [
    'notitle' => true,
];
