<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace format_picturecards\form;

use format_picturecards\local\activity_media;
use format_picturecards\local\topic_image;

/**
 * Core dynamic form used by the topic appearance control.
 * @package format_picturecards
 * @copyright 2026 GENESTIAL
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class topic_appearance extends \core_form\dynamic_form {
    /**
     * The topic belongs to a course context.
     * @return \context
     */
    protected function get_context_for_dynamic_submission(): \context {
        [$course] = topic_image::resolve($this->optional_param('sectionid', 0, PARAM_INT));
        return \context_course::instance($course->id);
    }

    /**
     * Only course editors can change topic imagery.
     */
    protected function check_access_for_dynamic_submission(): void {
        require_capability('moodle/course:update', $this->get_context_for_dynamic_submission());
    }

    /**
     * Image chooser with Moodle's standard file manager.
     */
    protected function definition(): void {
        $sectionid = $this->optional_param('sectionid', 0, PARAM_INT);
        $form = $this->_form;
        $form->addElement('hidden', 'sectionid', $sectionid);
        $form->setType('sectionid', PARAM_INT);
        $form->addElement(
            'filemanager',
            'image',
            get_string('topicpicture', 'format_picturecards'),
            null,
            activity_media::file_manager_options($this->get_context_for_dynamic_submission())
        );
        $form->addHelpButton('image', 'topicpicture', 'format_picturecards');
        $form->addElement('static', 'hint', '', get_string('topicpictureinstructions', 'format_picturecards'));
    }

    /**
     * Reject forged, oversized and non-raster draft files before save.
     * @param array $data submitted values
     * @param array $files raw submitted files
     * @return array field errors
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        try {
            activity_media::validate_draft((int)$data['image'], $this->get_context_for_dynamic_submission());
        } catch (\moodle_exception $exception) {
            $errors['image'] = $exception->getMessage();
        }
        return $errors;
    }

    /**
     * Save the chosen image, or remove it when the file manager is emptied.
     * @return array section identifier for refreshing
     */
    public function process_dynamic_submission(): array {
        $this->check_access_for_dynamic_submission();
        $data = $this->get_data();
        topic_image::save((int)$data->sectionid, (int)$data->image);
        return ['sectionid' => (int)$data->sectionid];
    }

    /**
     * Populate the editor's private draft with any saved picture.
     */
    public function set_data_for_dynamic_submission(): void {
        $sectionid = $this->optional_param('sectionid', 0, PARAM_INT);
        $context = $this->get_context_for_dynamic_submission();
        $draftid = 0;
        file_prepare_draft_area(
            $draftid,
            $context->id,
            'format_picturecards',
            'topicimage',
            $sectionid,
            activity_media::file_manager_options($context)
        );
        $this->set_data((object)['sectionid' => $sectionid, 'image' => $draftid]);
    }

    /**
     * The course page is the host for the modal.
     * @return \moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): \moodle_url {
        return new \moodle_url('/course/view.php', ['id' => $this->get_context_for_dynamic_submission()->instanceid]);
    }
}
