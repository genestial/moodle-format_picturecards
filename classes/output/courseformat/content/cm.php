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

namespace format_picturecards\output\courseformat\content;

use format_picturecards\local\activity_media;

/**
 * Add tile metadata while retaining core visibility, completion and editing.
 * @package format_picturecards
 * @copyright 2026 GENESTIAL
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cm extends \core_courseformat\output\local\content\cm {
    /**
     * Export tile-specific attributes.
     * @param \renderer_base $output renderer
     * @return \stdClass
     */
    public function export_for_template(\renderer_base $output): \stdClass {
        global $USER;
        $data = parent::export_for_template($output);
        $data->cmname['pluginname'] = get_string('pluginname', 'mod_' . $this->mod->modname);
        $data->picturecardsappearance = $data->editing &&
            has_capability('moodle/course:manageactivities', $this->mod->context) &&
            !$this->mod->get_delegated_section_info() && $data->hasname;
        $data->picturecardsappearanceurl = (new \moodle_url('/course/modedit.php', [
            'update' => $this->mod->id,
        ], 'id_format_picturecards_activitymedia'))->out(false);
        $data->picturecardstitle = strip_tags($this->mod->get_formatted_name());
        $data->picturecardsstate = 'untracked';
        $completion = new \completion_info($this->format->get_course());
        if ($this->mod->uservisible && $completion->is_enabled($this->mod) && $completion->is_tracked_user($USER->id)) {
            $state = $completion->get_data($this->mod, true)->completionstate;
            $data->picturecardsstate = in_array($state, [COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS], true) ?
                'done' : 'todo';
        }
        return $data;
    }
}
