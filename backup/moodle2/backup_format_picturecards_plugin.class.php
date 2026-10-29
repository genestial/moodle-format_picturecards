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
 * Activity-level media backup.
 * @package format_picturecards
 * @copyright 2026 GENESTIAL
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Back up media with its activity in PictureCards courses.
 */
class backup_format_picturecards_plugin extends backup_format_plugin {
    /**
     * Attach the selected course-context picture to its section.
     * @return backup_plugin_element
     */
    protected function define_section_plugin_structure() {
        $plugin = $this->get_plugin_element(null, $this->get_format_condition(), 'picturecards');
        $topic = new backup_nested_element($this->get_recommended_name(), null, ['sectionid']);
        $topic->set_source_sql('SELECT id AS sectionid FROM {course_sections}
             WHERE course = :courseid AND id = :sectionid', [
            'courseid' => backup::VAR_COURSEID, 'sectionid' => backup::VAR_SECTIONID,
        ]);
        $topic->annotate_files('format_picturecards', 'topicimage', 'sectionid');
        $plugin->add_child($topic);
        return $plugin;
    }

    /**
     * Attach media records and module-context files to module.xml.
     * @return backup_plugin_element
     */
    protected function define_module_plugin_structure() {
        $plugin = $this->get_plugin_element(null, $this->get_format_condition(), 'picturecards');
        $wrapper = new backup_nested_element($this->get_recommended_name());
        $media = new backup_nested_element('media', ['id'], ['mediatype', 'iconkey', 'timemodified', 'colour', 'focusx', 'focusy']);
        $plugin->add_child($wrapper);
        $wrapper->add_child($media);
        $media->set_source_table('format_picturecards_media', ['coursemoduleid' => backup::VAR_MODID]);
        $media->annotate_files('format_picturecards', 'activityimage', null);
        return $plugin;
    }
}
