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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace format_picturecards\local;

/**
 * Teacher-selected topic pictures in the course File API context.
 * @package format_picturecards
 * @copyright 2026 GENESTIAL
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class topic_image {
    /**
     * Resolve a section and its course, rejecting cross-course identifiers.
     * @param int $sectionid section identifier
     * @return array course and section records
     */
    public static function resolve(int $sectionid): array {
        global $DB;
        $section = $DB->get_record('course_sections', ['id' => $sectionid], '*', MUST_EXIST);
        $course = get_course($section->course);
        if ($course->format !== 'picturecards' || $section->section < 1) {
            throw new \moodle_exception('invalidcourseformat', 'format_picturecards');
        }
        return [$course, $section];
    }

    /**
     * Store the current editor's draft, including explicit removal of the picture.
     * @param int $sectionid destination section
     * @param int $draftid editor-owned draft item
     */
    public static function save(int $sectionid, int $draftid): void {
        [$course] = self::resolve($sectionid);
        $context = \context_course::instance($course->id);
        require_capability('moodle/course:update', $context);
        if ($draftid <= 0) {
            throw new \moodle_exception('invalidimage', 'format_picturecards');
        }
        activity_media::validate_draft($draftid, $context);
        file_save_draft_area_files(
            $draftid,
            $context->id,
            'format_picturecards',
            'topicimage',
            $sectionid,
            activity_media::file_manager_options($context)
        );
        self::reset_cache($course->id);
        rebuild_course_cache($course->id, true);
    }

    /**
     * Load topic pictures once per course request.
     * @param int $courseid course identifier
     * @return array safe image files indexed by section
     */
    public static function course_files(int $courseid): array {
        $cache = \cache::make_from_params(\cache_store::MODE_REQUEST, 'format_picturecards', 'topicimages');
        $files = $cache->get($courseid);
        if ($files !== false) {
            return $files;
        }
        $contextid = \context_course::instance($courseid)->id;
        $files = [];
        $allfiles = get_file_storage()->get_area_files($contextid, 'format_picturecards', 'topicimage', false, 'id DESC', false);
        foreach ($allfiles as $file) {
            $sectionid = $file->get_itemid();
            if (!isset($files[$sectionid]) && activity_media::is_safe_image($file)) {
                $files[$sectionid] = $file;
            }
        }
        $cache->set($courseid, $files);
        return $files;
    }

    /**
     * Resolve a safe image URL for display.
     * @param int $courseid course
     * @param int $sectionid section
     * @return \moodle_url|null
     */
    public static function get_url(int $courseid, int $sectionid): ?\moodle_url {
        $file = self::course_files($courseid)[$sectionid] ?? null;
        if (!$file) {
            return null;
        }
        return \moodle_url::make_pluginfile_url(
            \context_course::instance($courseid)->id,
            'format_picturecards',
            'topicimage',
            $sectionid,
            '/',
            $file->get_filename()
        );
    }

    /**
     * Remove files belonging to a deleted section.
     * @param int $courseid course
     * @param int $sectionid deleted section
     */
    public static function delete(int $courseid, int $sectionid): void {
        $context = \context_course::instance($courseid, IGNORE_MISSING);
        if ($context) {
            get_file_storage()->delete_area_files($context->id, 'format_picturecards', 'topicimage', $sectionid);
            self::reset_cache($courseid);
        }
    }

    /**
     * Invalidate course request cache after a save or deletion.
     * @param int $courseid course identifier
     */
    public static function reset_cache(int $courseid): void {
        \cache::make_from_params(\cache_store::MODE_REQUEST, 'format_picturecards', 'topicimages')->delete($courseid);
    }
}
