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
 * Test appearance submissions at the dynamic form permission boundary.
 * @package format_picturecards
 * @copyright 2026 GENESTIAL
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(appearance::class)]
final class appearance_test extends \advanced_testcase {
    /**
     * An editor can save real appearance settings through the form.
     */
    public function test_editor_submission(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course(['format' => 'picturecards']);
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $data = appearance::mock_ajax_submit([
            'cmid' => $page->cmid, 'mediatype' => 'icon', 'iconkey' => 'idea',
            'colour' => 'plum', 'focusx' => 38, 'focusy' => 30, 'image' => 0,
        ]);
        $form = new appearance(ajaxformdata: $data, isajaxsubmission: true);
        $this->assertTrue($form->is_validated());
        $this->assertSame(['cmid' => (int)$page->cmid], $form->process_dynamic_submission());
        $record = activity_media::get_record($page->cmid);
        $this->assertSame('plum', $record->colour);
        $this->assertEquals(38, $record->focusx);
        $this->assertEquals(30, $record->focusy);
    }

    /**
     * A student cannot even load the editor for a known module.
     */
    public function test_student_cannot_load_form(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['format' => 'picturecards']);
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);
        $this->expectException(\required_capability_exception::class);
        new appearance(ajaxformdata: ['cmid' => $page->cmid], isajaxsubmission: true);
    }

    /**
     * Editing another course must not be allowed by substituting a module id.
     */
    public function test_teacher_cannot_edit_another_course(): void {
        $this->resetAfterTest();
        $owncourse = $this->getDataGenerator()->create_course(['format' => 'picturecards']);
        $othercourse = $this->getDataGenerator()->create_course(['format' => 'picturecards']);
        $page = $this->getDataGenerator()->create_module('page', ['course' => $othercourse->id]);
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $owncourse->id, 'editingteacher');
        $this->setUser($teacher);
        $this->expectException(\moodle_exception::class);
        new appearance(ajaxformdata: ['cmid' => $page->cmid], isajaxsubmission: true);
    }
}
