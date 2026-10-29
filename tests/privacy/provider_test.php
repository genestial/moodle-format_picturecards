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

namespace format_picturecards\privacy;

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;

/**
 * Privacy ownership and isolation tests.
 * @package format_picturecards
 * @copyright 2026 GENESTIAL
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(provider::class)]
final class provider_test extends \core_privacy\tests\provider_testcase {
    /**
     * Discover, export and delete only approved users' data.
     */
    public function test_file_privacy_is_scoped_to_uploader_and_context(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['format' => 'picturecards']);
        $page = $generator->create_module('page', ['course' => $course->id]);
        $otherpage = $generator->create_module('page', ['course' => $course->id]);
        $alice = $generator->create_user();
        $bob = $generator->create_user();
        $context = \context_module::instance($page->cmid);
        $othercontext = \context_module::instance($otherpage->cmid);
        $fs = get_file_storage();
        $base = ['contextid' => $context->id, 'component' => 'format_picturecards', 'filearea' => 'activityimage',
            'itemid' => 0, 'filepath' => '/', 'userid' => $alice->id, 'filename' => 'alice.png'];
        $alicefile = $fs->create_file_from_string($base, 'Alice personal image');
        $base['filename'] = 'bob.png';
        $base['userid'] = $bob->id;
        $bobfile = $fs->create_file_from_string($base, 'Bob personal image');
        $base['contextid'] = $othercontext->id;
        $otherfile = $fs->create_file_from_string($base, 'Unrelated activity');

        $this->assertEquals([$context->id], provider::get_contexts_for_userid($alice->id)->get_contextids());
        $users = new userlist($context, 'format_picturecards');
        provider::get_users_in_context($users);
        $this->assertEqualsCanonicalizing([$alice->id, $bob->id], $users->get_userids());
        $approved = new approved_contextlist($alice, 'format_picturecards', [$context->id]);
        provider::export_user_data($approved);
        $writer = \core_privacy\local\request\writer::with_context($context);
        $this->assertTrue($writer->has_any_data());
        $data = $writer->get_data([get_string('activityimage', 'format_picturecards')]);
        $this->assertSame('alice.png', $data->filename);
        provider::delete_data_for_user($approved);
        $this->assertFalse($fs->get_file_by_id($alicefile->get_id()));
        $this->assertNotFalse($fs->get_file_by_id($bobfile->get_id()));
        provider::delete_data_for_users(new approved_userlist($context, 'format_picturecards', [$bob->id]));
        $this->assertFalse($fs->get_file_by_id($bobfile->get_id()));
        $this->assertNotFalse($fs->get_file_by_id($otherfile->get_id()));
        provider::delete_data_for_all_users_in_context(\context_course::instance($course->id));
        $this->assertNotFalse($fs->get_file_by_id($otherfile->get_id()));
        provider::delete_data_for_all_users_in_context($othercontext);
        $this->assertFalse($fs->get_file_by_id($otherfile->get_id()));
    }

    /**
     * Course-context topic pictures participate in discovery, export and deletion.
     */
    public function test_topic_picture_privacy(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course(['format' => 'picturecards']);
        $section = get_fast_modinfo($course)->get_section_info(1);
        $user = $this->getDataGenerator()->create_user();
        $context = \context_course::instance($course->id);
        $file = get_file_storage()->create_file_from_string([
            'contextid' => $context->id, 'component' => 'format_picturecards', 'filearea' => 'topicimage',
            'itemid' => $section->id, 'filepath' => '/', 'filename' => 'topic.png', 'userid' => $user->id,
        ], 'Personal topic picture');
        $this->assertEquals([$context->id], provider::get_contexts_for_userid($user->id)->get_contextids());
        $users = new userlist($context, 'format_picturecards');
        provider::get_users_in_context($users);
        $this->assertEquals([$user->id], $users->get_userids());
        $approved = new approved_contextlist($user, 'format_picturecards', [$context->id]);
        provider::export_user_data($approved);
        $path = [get_string('topicpicture', 'format_picturecards'), (string)$section->id];
        $this->assertSame('topic.png', \core_privacy\local\request\writer::with_context($context)->get_data($path)->filename);
        provider::delete_data_for_user($approved);
        $this->assertFalse(get_file_storage()->get_file_by_id($file->get_id()));
    }
}
