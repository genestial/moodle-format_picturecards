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

namespace format_picturecards;

use context_course;
use format_picturecards\local\activity_media;


/**
 * Unit tests for {@see activity_media}.
 *
 * @package    format_picturecards
 * @copyright  2026 GENESTIAL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(activity_media::class)]
final class activity_media_test extends \advanced_testcase {
    /**
     * Verify normalise media type.
     */
    public function test_normalise_media_type(): void {
        $this->assertSame(activity_media::TYPE_DEFAULT, activity_media::normalise_media_type('invalid'));
        $this->assertSame(activity_media::TYPE_ICON, activity_media::normalise_media_type('icon'));
        $this->assertSame(activity_media::TYPE_IMAGE, activity_media::normalise_media_type('image'));
        $this->assertSame(activity_media::TYPE_DEFAULT, activity_media::normalise_media_type('default'));
    }

    /**
     * Verify normalise icon key.
     */
    public function test_normalise_icon_key(): void {
        $this->assertSame('', activity_media::normalise_icon_key('notanicon'));
        $this->assertSame('video', activity_media::normalise_icon_key('video'));
        $this->assertSame('', activity_media::normalise_icon_key(''));
    }

    /**
     * Verify save icon then default when icon missing.
     */
    public function test_save_icon_then_default_when_icon_missing(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course(['format' => 'picturecards']);
        $page = $gen->create_module('page', ['course' => $course->id]);

        activity_media::save((int) $page->cmid, activity_media::TYPE_ICON, 'lab', 0);
        $rec = $DB->get_record('format_picturecards_media', ['coursemoduleid' => $page->cmid], '*', MUST_EXIST);
        $this->assertSame('icon', $rec->mediatype);
        $this->assertSame('lab', $rec->iconkey);

        activity_media::save((int) $page->cmid, activity_media::TYPE_ICON, '', 0);
        $rec = $DB->get_record('format_picturecards_media', ['coursemoduleid' => $page->cmid], '*', MUST_EXIST);
        $this->assertSame(activity_media::TYPE_DEFAULT, $rec->mediatype);
        $this->assertSame('', $rec->iconkey);
    }

    /**
     * Verify resolve max bytes respects course limit.
     */
    public function test_resolve_max_bytes_respects_course_limit(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course(['format' => 'picturecards', 'maxbytes' => 200 * 1024]);
        set_config('maximagesizebytes', 50 * 1024 * 1024, 'format_picturecards');
        $ctx = context_course::instance($course->id);
        $resolved = activity_media::resolve_max_bytes($ctx);
        $this->assertLessThanOrEqual(200 * 1024, $resolved);
    }

    /**
     * Verify export for cm with custom icon.
     */
    public function test_export_for_cm_with_custom_icon(): void {
        global $PAGE;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course(['format' => 'picturecards']);
        $page = $gen->create_module('page', ['course' => $course->id]);
        activity_media::save((int) $page->cmid, activity_media::TYPE_ICON, 'reading', 0);

        $PAGE->set_url('/course/view.php', ['id' => $course->id]);
        $PAGE->set_context(context_course::instance($course->id));
        $cm = get_fast_modinfo($course)->get_cm($page->cmid);
        $data = activity_media::export_for_cm($cm, $PAGE->get_renderer('core'));

        $this->assertSame(activity_media::TYPE_ICON, $data['mediatype']);
        $this->assertArrayHasKey('iconclass', $data);
        $this->assertStringContainsString('fa-', $data['iconclass']);
    }

    /**
     * Verify export for cm image without file falls back.
     */
    public function test_export_for_cm_image_without_file_falls_back(): void {
        global $PAGE;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course(['format' => 'picturecards']);
        $page = $gen->create_module('page', ['course' => $course->id]);
        activity_media::save((int) $page->cmid, activity_media::TYPE_IMAGE, '', 0);

        $PAGE->set_url('/course/view.php', ['id' => $course->id]);
        $PAGE->set_context(context_course::instance($course->id));
        $cm = get_fast_modinfo($course)->get_cm($page->cmid);
        $data = activity_media::export_for_cm($cm, $PAGE->get_renderer('core'));

        $this->assertSame(activity_media::TYPE_DEFAULT, $data['mediatype']);
    }

    /**
     * A student cannot mutate an activity even when its identifier is known.
     */
    public function test_save_denies_student(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['format' => 'picturecards']);
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');
        $this->setUser($user);
        $this->expectException(\required_capability_exception::class);
        activity_media::save($page->cmid, 'icon', 'lab', 0);
    }

    /**
     * Programmatic updates without media fields must not erase teacher choices.
     */
    public function test_programmatic_update_preserves_media(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course(['format' => 'picturecards']);
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        activity_media::save($page->cmid, 'icon', 'lab', 0);
        \format_picturecards_coursemodule_edit_post_actions((object)['coursemodule' => $page->cmid], $course);
        $this->assertSame('lab', activity_media::get_record($page->cmid)->iconkey);
    }

    /**
     * Batch loading must cache negative lookups and invalidate after saving.
     */
    public function test_course_records_cache_is_invalidated(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course(['format' => 'picturecards']);
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        activity_media::course_records($course->id);
        $before = $DB->perf_get_queries();
        activity_media::course_records($course->id);
        $this->assertSame($before, $DB->perf_get_queries());
        activity_media::save($page->cmid, 'icon', 'lab', 0);
        $this->assertSame('lab', activity_media::course_records($course->id)[$page->cmid]->iconkey);
    }

    /**
     * Image persistence, removal and backup use real File API content.
     */
    public function test_image_duplicate_and_delete(): void {
        global $PAGE;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course(['format' => 'picturecards']);
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $draftid = file_get_unused_draft_itemid();
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a3WQAAAAASUVORK5CYII=');
        $usercontext = \context_user::instance(\core\session\manager::get_realuser()->id);
        get_file_storage()->create_file_from_string([
            'contextid' => $usercontext->id, 'component' => 'user', 'filearea' => 'draft',
            'itemid' => $draftid, 'filepath' => '/', 'filename' => 'badge.png',
        ], $png);
        activity_media::save($page->cmid, 'image', '', $draftid, ['colour' => 'teal', 'focusx' => 38, 'focusy' => 30]);
        $this->assertNotNull(activity_media::get_image_url($page->cmid));
        $PAGE->set_url('/course/view.php', ['id' => $course->id]);
        $PAGE->set_course($course);
        $cm = get_fast_modinfo($course)->get_cm($page->cmid);
        $tile = activity_media::export_for_cm($cm, $PAGE->get_renderer('format_picturecards'));
        $this->assertSame('image', $tile['mediatype']);
        $this->assertStringContainsString('badge.png', $tile['imageurl']);
        $actions = \core_courseformat\formatactions::cm($course);
        if (method_exists($actions, 'duplicate')) {
            $copy = $actions->duplicate($page->cmid);
        } else {
            $copy = duplicate_module($course, $cm);
        }
        $this->assertSame('image', activity_media::get_record($copy->id)->mediatype);
        $this->assertSame('teal', activity_media::get_record($copy->id)->colour);
        $this->assertEquals(38, activity_media::get_record($copy->id)->focusx);
        $this->assertEquals(30, activity_media::get_record($copy->id)->focusy);
        $this->assertNotNull(activity_media::get_image_url($copy->id));
        $this->assertNotEquals($page->cmid, $copy->id);
        if (method_exists($actions, 'delete')) {
            $actions->delete($copy->id);
        } else {
            course_delete_module($copy->id);
        }
        $this->assertNull(activity_media::get_record($copy->id));
        $this->assertNotNull(activity_media::get_image_url($page->cmid));
        activity_media::save($page->cmid, 'default', '', 0);
        $this->assertNull(activity_media::get_image_url($page->cmid));
    }

    /**
     * Rendering additional image cards reuses the course-wide file lookup.
     */
    public function test_course_image_lookup_is_batched(): void {
        global $DB, $PAGE, $USER;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course(['format' => 'picturecards']);
        $cmids = [];
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a3WQAAAAASUVORK5CYII=');
        for ($i = 0; $i < 3; $i++) {
            $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
            $cmids[] = $page->cmid;
            $draftid = file_get_unused_draft_itemid();
            get_file_storage()->create_file_from_string([
                'contextid' => \context_user::instance($USER->id)->id,
                'component' => 'user', 'filearea' => 'draft', 'itemid' => $draftid,
                'filepath' => '/', 'filename' => 'card' . $i . '.png', 'userid' => $USER->id,
            ], $png);
            activity_media::save($page->cmid, 'image', '', $draftid);
        }
        $PAGE->set_url('/course/view.php', ['id' => $course->id]);
        $PAGE->set_course($course);
        $renderer = $PAGE->get_renderer('format_picturecards');
        $modinfo = get_fast_modinfo($course);
        $first = activity_media::export_for_cm($modinfo->get_cm($cmids[0]), $renderer);
        $this->assertSame('image', $first['mediatype']);
        $queries = $DB->perf_get_queries();
        foreach (array_slice($cmids, 1) as $cmid) {
            $tile = activity_media::export_for_cm($modinfo->get_cm($cmid), $renderer);
            $this->assertSame('image', $tile['mediatype']);
        }
        $this->assertSame($queries, $DB->perf_get_queries());
    }

    /**
     * SVG and forged raster files must fail before any media row is saved.
     */
    public function test_invalid_draft_is_rejected(): void {
        global $USER;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course(['format' => 'picturecards']);
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $draftid = file_get_unused_draft_itemid();
        get_file_storage()->create_file_from_string([
            'contextid' => \context_user::instance($USER->id)->id, 'component' => 'user', 'filearea' => 'draft',
            'itemid' => $draftid, 'filepath' => '/', 'filename' => 'badge.png',
        ], '<script>alert(1)</script>');
        try {
            activity_media::save($page->cmid, 'image', '', $draftid);
            $this->fail('Invalid image accepted');
        } catch (\moodle_exception $exception) {
            $this->assertSame('invalidimage', $exception->errorcode);
        }
        $this->assertNull(activity_media::get_record($page->cmid));
    }
}
