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

use format_picturecards\local\activity_media;
use format_picturecards\local\topic_image;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

#[\PHPUnit\Framework\Attributes\CoversClass(\backup_format_picturecards_plugin::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\restore_format_picturecards_plugin::class)]
/**
 * Verify course-format data and files follow restored sections and modules.
 *
 * @package format_picturecards
 * @copyright 2026 GENESTIAL
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \backup_format_picturecards_plugin
 * @covers \restore_format_picturecards_plugin
 */
final class backup_restore_test extends \advanced_testcase {
    /**
     * A course backup remaps both kinds of images to new contexts and IDs.
     */
    public function test_course_round_trip(): void {
        global $CFG, $DB, $USER;
        $this->resetAfterTest();
        $this->setAdminUser();
        $CFG->backup_file_logger_level = \backup::LOG_NONE;
        $course = $this->getDataGenerator()->create_course(['format' => 'picturecards', 'numsections' => 1]);
        course_get_format($course)->update_course_format_options([
            'palette' => 'custom',
            'palette1' => '#123456',
            'palette2' => '#234567',
            'palette3' => '#345678',
            'palette4' => '#456789',
            'palette5' => '#56789a',
            'showtopiceyebrow' => 0,
            'topiceyebrowword' => 'Week',
        ]);
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'section' => 1]);
        $section = get_fast_modinfo($course)->get_section_info(1);
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a3WQAAAAASUVORK5CYII=');
        $draftid = file_get_unused_draft_itemid();
        get_file_storage()->create_file_from_string([
            'contextid' => \context_user::instance($USER->id)->id,
            'component' => 'user', 'filearea' => 'draft', 'itemid' => $draftid,
            'filepath' => '/', 'filename' => 'topic.png', 'userid' => $USER->id,
        ], $png);
        topic_image::save($section->id, $draftid);
        $draftid = file_get_unused_draft_itemid();
        get_file_storage()->create_file_from_string([
            'contextid' => \context_user::instance($USER->id)->id,
            'component' => 'user', 'filearea' => 'draft', 'itemid' => $draftid,
            'filepath' => '/', 'filename' => 'card.png', 'userid' => $USER->id,
        ], $png);
        activity_media::save($page->cmid, activity_media::TYPE_IMAGE, '', $draftid);

        $backup = new \backup_controller(
            \backup::TYPE_1COURSE,
            $course->id,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_IMPORT,
            $USER->id
        );
        $backupid = $backup->get_backupid();
        $backup->execute_plan();
        $backup->destroy();
        $backupdir = $CFG->tempdir . '/backup/' . $backupid;
        $sectionfiles = glob($backupdir . '/sections/*/section.xml');
        $this->assertNotEmpty($sectionfiles, $backupdir);
        $sectionxml = file_get_contents($sectionfiles[1] ?? $sectionfiles[0]);
        $this->assertStringContainsString('plugin_format_picturecards_section', $sectionxml);
        $this->assertStringContainsString('topicimage', file_get_contents($backupdir . '/files.xml'));

        $newcourseid = \restore_dbops::create_new_course('Restored PictureCards', 'restored-picturecards', $course->category);
        $restore = new \restore_controller(
            $backupid,
            $newcourseid,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            $USER->id,
            \backup::TARGET_NEW_COURSE
        );
        $this->assertTrue($restore->execute_precheck());
        $restore->execute_plan();
        $restore->destroy();

        $this->assertSame('picturecards', $DB->get_field('course', 'format', ['id' => $newcourseid]));
        $options = course_get_format($newcourseid)->get_format_options();
        $this->assertSame('custom', $options['palette']);
        $this->assertSame('#123456', $options['palette1']);
        $this->assertSame('#56789a', $options['palette5']);
        $this->assertEquals(0, $options['showtopiceyebrow']);
        $this->assertSame('Week', $options['topiceyebrowword']);
        $newsection = get_fast_modinfo($newcourseid)->get_section_info(1);
        $this->assertNotEquals($section->id, $newsection->id);
        $restoredfiles = get_file_storage()->get_area_files(
            \context_course::instance($newcourseid)->id,
            'format_picturecards',
            'topicimage',
            $newsection->id,
            'id',
            false
        );
        $this->assertCount(1, $restoredfiles);
        $this->assertNotNull(topic_image::get_url($newcourseid, $newsection->id));
        $newpage = $DB->get_record('page', ['course' => $newcourseid], '*', MUST_EXIST);
        $newcm = get_coursemodule_from_instance('page', $newpage->id, $newcourseid, false, MUST_EXIST);
        $this->assertNotEquals($page->cmid, $newcm->id);
        $this->assertNotNull(activity_media::get_image_url($newcm->id));
        $this->assertNotNull(topic_image::get_url($course->id, $section->id));
    }
}
