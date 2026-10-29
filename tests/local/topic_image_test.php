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
 * Cover selected topic pictures, cleanup and permission boundaries.
 * @package format_picturecards
 * @copyright 2026 GENESTIAL
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \format_picturecards\local\topic_image
 */
#[\PHPUnit\Framework\Attributes\CoversClass(topic_image::class)]
final class topic_image_test extends \advanced_testcase {
    /**
     * A topic picture is independent of images attached to activities.
     */
    public function test_selection_and_removal(): void {
        global $USER;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course(['format' => 'picturecards']);
        $section = get_fast_modinfo($course)->get_section_info(1);
        $draftid = file_get_unused_draft_itemid();
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a3WQAAAAASUVORK5CYII=');
        get_file_storage()->create_file_from_string([
            'contextid' => \context_user::instance($USER->id)->id,
            'component' => 'user', 'filearea' => 'draft', 'itemid' => $draftid,
            'filepath' => '/', 'filename' => 'topic.png', 'userid' => $USER->id,
        ], $png);
        topic_image::save($section->id, $draftid);
        $url = topic_image::get_url($course->id, $section->id);
        $this->assertNotNull($url);
        $this->assertStringContainsString('/topicimage/' . $section->id . '/topic.png', $url->out(false));
        $newdraft = file_get_unused_draft_itemid();
        topic_image::save($section->id, $newdraft);
        $this->assertNull(topic_image::get_url($course->id, $section->id));
    }

    /**
     * A student cannot change the topic picture by supplying a section ID.
     */
    public function test_student_denied(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['format' => 'picturecards']);
        $section = get_fast_modinfo($course)->get_section_info(1);
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);
        $this->expectException(\required_capability_exception::class);
        topic_image::save($section->id, 123);
    }

    /**
     * A direct file URL cannot reveal the picture of a hidden topic.
     */
    public function test_hidden_topic_file_denied(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course(['format' => 'picturecards']);
        $section = get_fast_modinfo($course)->get_section_info(1);
        $DB->set_field('course_sections', 'visible', 0, ['id' => $section->id]);
        rebuild_course_cache($course->id, true);
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);
        // Send_file_not_found() emits a 404 header before throwing. CLI PHPUnit has already written output.
        set_error_handler(static function (int $code, string $message): bool {
            return str_contains($message, 'Cannot modify header information');
        });
        try {
            $this->expectException(\moodle_exception::class);
            format_picturecards_pluginfile(
                $course,
                null,
                \context_course::instance($course->id),
                'topicimage',
                [$section->id, 'topic.png'],
                false
            );
        } finally {
            restore_error_handler();
        }
    }

    /**
     * A course format change keeps the picture, while deleting its section removes the file.
     */
    public function test_format_switch_and_section_deletion(): void {
        global $CFG, $USER;
        $this->resetAfterTest();
        $this->setAdminUser();
        require_once($CFG->dirroot . '/course/lib.php');
        $course = $this->getDataGenerator()->create_course(['format' => 'picturecards']);
        $section = get_fast_modinfo($course)->get_section_info(1);
        $draftid = file_get_unused_draft_itemid();
        get_file_storage()->create_file_from_string([
            'contextid' => \context_user::instance($USER->id)->id,
            'component' => 'user', 'filearea' => 'draft', 'itemid' => $draftid,
            'filepath' => '/', 'filename' => 'topic.png', 'userid' => $USER->id,
        ], base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a3WQAAAAASUVORK5CYII='));
        topic_image::save($section->id, $draftid);
        $contextid = \context_course::instance($course->id)->id;
        update_course((object)['id' => $course->id, 'format' => 'topics']);
        $this->assertCount(1, get_file_storage()->get_area_files(
            $contextid,
            'format_picturecards',
            'topicimage',
            $section->id,
            'id',
            false
        ));
        update_course((object)['id' => $course->id, 'format' => 'picturecards']);
        $this->assertNotNull(topic_image::get_url($course->id, $section->id));
        $this->assertTrue(\core_courseformat\formatactions::section($course)->delete($section));
        $this->assertEmpty(get_file_storage()->get_area_files(
            $contextid,
            'format_picturecards',
            'topicimage',
            $section->id,
            'id',
            false
        ));
    }
}
