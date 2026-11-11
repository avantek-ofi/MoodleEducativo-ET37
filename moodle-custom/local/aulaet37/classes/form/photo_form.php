<?php
// This file is part of Aula ET37 - https://moodle.org/
//
// Aula ET37 is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_aulaet37\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Single-purpose form to upload the profile photo.
 *
 * @package    local_aulaet37
 * @copyright  2026 Aula ET37
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class photo_form extends \moodleform {
    /**
     * Form definition.
     */
    protected function definition(): void {
        $mform = $this->_form;
        $mform->addElement('filemanager', 'imagefile', get_string('newpicture'), null,
            $this->_customdata['filemanageroptions']);
        $mform->addHelpButton('imagefile', 'newpicture');
        $mform->addRule('imagefile', get_string('photorequired', 'local_aulaet37'), 'required', null, 'client');
        $this->add_action_buttons(false, get_string('savephoto', 'local_aulaet37'));
    }

    /**
     * Requires one file in the draft area.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        $draft = file_get_drafarea_files((int) ($data['imagefile'] ?? 0), '/');
        if (empty($draft->list)) {
            $errors['imagefile'] = get_string('photorequired', 'local_aulaet37');
        }
        return $errors;
    }
}
