<?php
// This file is part of Moodle - http://moodle.org/
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

/**
 * Permission-checked tile appearance form, served by Moodle's dynamic form API.
 * @package format_picturecards
 * @copyright 2026 GENESTIAL
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class appearance extends \core_form\dynamic_form {
    /**
     * Module context supplied by the request.
     * @return \context
     */
    protected function get_context_for_dynamic_submission(): \context {
        return \context_module::instance($this->optional_param('cmid', 0, PARAM_INT));
    }

    /**
     * Check editing rights in the actual destination module.
     */
    protected function check_access_for_dynamic_submission(): void {
        $context = $this->get_context_for_dynamic_submission();
        require_capability('moodle/course:manageactivities', $context);
        $course = get_course($context->get_course_context()->instanceid);
        if ($course->format !== 'picturecards') {
            throw new \moodle_exception('invalidcourseformat', 'format_picturecards');
        }
    }

    /**
     * Appearance controls use core forms and the File API.
     */
    protected function definition(): void {
        global $PAGE, $OUTPUT;
        $context = $this->get_context_for_dynamic_submission();
        $cm = get_coursemodule_from_id('', $context->instanceid, 0, false, MUST_EXIST);
        $form = $this->_form;
        $form->addElement('hidden', 'cmid', $cm->id);
        $form->setType('cmid', PARAM_INT);
        $form->addElement('static', 'activitytitle', '', format_string($cm->name, true, ['context' => $context]));
        $form->addElement('select', 'mediatype', get_string('tilestyle', 'format_picturecards'), [
            'image' => get_string('styleimage', 'format_picturecards'),
            'icon' => get_string('styleicon', 'format_picturecards'),
            'default' => get_string('styledefault', 'format_picturecards'),
        ]);
        $form->setType('mediatype', PARAM_ALPHA);
        $form->addElement('select', 'iconkey', get_string('customicon', 'format_picturecards'), activity_media::icon_options());
        $form->setType('iconkey', PARAM_ALPHANUMEXT);
        $form->hideIf('iconkey', 'mediatype', 'neq', activity_media::TYPE_ICON);
        $form->addElement(
            'select',
            'colour',
            get_string('backgroundcolour', 'format_picturecards'),
            activity_media::colour_options()
        );
        $form->setType('colour', PARAM_ALPHA);
        $form->hideIf('colour', 'mediatype', 'neq', activity_media::TYPE_ICON);
        $icons = [];
        foreach (activity_media::icon_options() as $key => $name) {
            if ($key) {
                $icons[] = ['key' => $key, 'name' => $name, 'class' => activity_media::icon_class($key)];
            }
        }
        $colours = [];
        foreach (activity_media::colour_options() as $key => $name) {
            $colours[] = ['key' => $key, 'name' => $name];
        }
        $image = activity_media::get_image_url($cm->id);
        $form->addElement('html', $OUTPUT->render_from_template('format_picturecards/appearance_controls', [
            'icons' => $icons,
            'colours' => $colours,
            'imageurl' => $image ? $image->out(false) : '',
            'defaulticon' => $OUTPUT->image_url('monologo', 'mod_' . $cm->modname)->out(false),
            'title' => strip_tags(format_string($cm->name, true, ['context' => $context])),
            'cmid' => $cm->id,
        ]));
        $form->addElement(
            'filemanager',
            'image',
            get_string('activityimage', 'format_picturecards'),
            null,
            activity_media::file_manager_options($context)
        );
        $form->hideIf('image', 'mediatype', 'neq', activity_media::TYPE_IMAGE);
        $form->addElement('static', 'imagehelp', '', get_string('imageinstructions', 'format_picturecards'));
        $form->hideIf('imagehelp', 'mediatype', 'neq', activity_media::TYPE_IMAGE);
        foreach (['focusx', 'focusy'] as $coordinate) {
            $form->addElement('text', $coordinate, get_string($coordinate, 'format_picturecards'), [
                'type' => 'number', 'min' => 0, 'max' => 100,
            ]);
            $form->setType($coordinate, PARAM_INT);
            $form->setDefault($coordinate, 50);
            $form->hideIf($coordinate, 'mediatype', 'neq', activity_media::TYPE_IMAGE);
        }
        $PAGE->requires->js_call_amd('format_picturecards/appearance', 'init', [$cm->id]);
    }

    /**
     * Validate file contents before saving any settings.
     * @param array $data submitted fields
     * @param array $files uploaded files
     * @return array field errors
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        if ($data['mediatype'] === activity_media::TYPE_IMAGE) {
            try {
                activity_media::validate_draft((int)$data['image'], $this->get_context_for_dynamic_submission());
            } catch (\moodle_exception $exception) {
                $errors['image'] = $exception->getMessage();
            }
        }
        if ($data['mediatype'] === activity_media::TYPE_ICON && !activity_media::normalise_icon_key($data['iconkey'] ?? '')) {
            $errors['iconkey'] = get_string('chooseicon', 'format_picturecards');
        }
        foreach (['focusx', 'focusy'] as $coordinate) {
            if ($data[$coordinate] < 0 || $data[$coordinate] > 100) {
                $errors[$coordinate] = get_string('invalidfocus', 'format_picturecards');
            }
        }
        return $errors;
    }

    /**
     * Save validated settings using the same service as module editing.
     * @return array saved module identifier
     */
    public function process_dynamic_submission(): array {
        $this->check_access_for_dynamic_submission();
        $data = $this->get_data();
        activity_media::save((int)$data->cmid, $data->mediatype, $data->iconkey ?? '', (int)$data->image, (array)$data);
        return ['cmid' => (int)$data->cmid];
    }

    /**
     * Prepare a draft belonging only to the current editor.
     */
    public function set_data_for_dynamic_submission(): void {
        $context = $this->get_context_for_dynamic_submission();
        $cmid = $context->instanceid;
        $record = activity_media::get_record($cmid);
        $draftid = 0;
        file_prepare_draft_area(
            $draftid,
            $context->id,
            'format_picturecards',
            'activityimage',
            0,
            activity_media::file_manager_options($context)
        );
        $this->set_data((object)([
            'cmid' => $cmid,
            'mediatype' => $record->mediatype ?? activity_media::TYPE_DEFAULT,
            'iconkey' => $record->iconkey ?? '',
            'image' => $draftid,
        ] + activity_media::normalise_appearance((array)($record ?? new \stdClass()))));
    }

    /**
     * Page URL used by Moodle's dynamic form service.
     * @return \moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): \moodle_url {
        return new \moodle_url('/course/view.php', [
            'id' => $this->get_context_for_dynamic_submission()->get_course_context()->instanceid,
        ]);
    }
}
