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

/**
 * Activity-level media restore.
 * @package format_picturecards
 * @copyright 2026 GENESTIAL
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Restore media using the destination module context and identifier.
 */
class restore_format_picturecards_plugin extends restore_format_plugin {
    /**
     * Section picture mapping path.
     * @return restore_path_element[]
     */
    protected function define_section_plugin_structure() {
        return [new restore_path_element('picturecards_topic', $this->get_pathfor('/'))];
    }

    /**
     * Remap the source section identifier in File API item IDs.
     * @param array $data backed-up section data
     */
    public function process_picturecards_topic($data): void {
        if (empty($data['sectionid'])) {
            return;
        }
        $this->set_mapping('itemid', $data['sectionid'], $this->task->get_sectionid(), true);
        $this->add_related_files('format_picturecards', 'topicimage', 'itemid');
        \format_picturecards\local\topic_image::reset_cache((int)$this->task->get_courseid());
    }

    /**
     * Define module media paths.
     * @return restore_path_element[] module media paths
     */
    protected function define_module_plugin_structure() {
        return [new restore_path_element('picturecards_media', $this->get_pathfor('/media'))];
    }

    /**
     * Restore course content without attributing shared media settings to a user.
     * @param array $data backed-up media record
     */
    public function process_picturecards_media($data): void {
        global $DB;
        $cmid = $this->task->get_moduleid();
        $record = (object)([
            'coursemoduleid' => $cmid,
            'mediatype' => \format_picturecards\local\activity_media::normalise_media_type($data['mediatype']),
            'iconkey' => \format_picturecards\local\activity_media::normalise_icon_key($data['iconkey'] ?? ''),
            'timemodified' => (int)$data['timemodified'],
        ] + \format_picturecards\local\activity_media::normalise_appearance($data));
        $existing = $DB->get_record('format_picturecards_media', ['coursemoduleid' => $cmid]);
        if ($existing) {
            $record->id = $existing->id;
            $DB->update_record('format_picturecards_media', $record);
        } else {
            $DB->insert_record('format_picturecards_media', $record);
        }
        \format_picturecards\local\activity_media::reset_course_cache((int)$this->task->get_courseid());
    }

    /**
     * Restore itemid-zero images after core has mapped the module context.
     */
    public function after_restore_module(): void {
        $this->add_related_files('format_picturecards', 'activityimage', null);
    }
}
