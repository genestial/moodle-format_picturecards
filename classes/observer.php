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

namespace format_picturecards;

/**
 * Lifecycle cleanup, including courses which have changed format.
 *
 * @package format_picturecards
 * @copyright 2026 GENESTIAL
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {
    /**
     * Remove media after core has deleted an activity and its module context.
     * @param \core\event\course_module_deleted $event event
     */
    public static function course_module_deleted(\core\event\course_module_deleted $event): void {
        \format_picturecards\local\activity_media::delete((int)$event->objectid);
        \format_picturecards\local\activity_media::reset_course_cache((int)$event->courseid);
    }

    /**
     * Remove the course-context image for a deleted section.
     * @param \core\event\course_section_deleted $event event
     */
    public static function course_section_deleted(\core\event\course_section_deleted $event): void {
        \format_picturecards\local\topic_image::delete((int)$event->courseid, (int)$event->objectid);
    }
}
