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

#[\PHPUnit\Framework\Attributes\CoversClass(\format_picturecards\output\renderer::class)]
/**
 * Verify real rendering paths, including AJAX fragments.
 * @package format_picturecards
 * @copyright 2026 GENESTIAL
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \format_picturecards\output\renderer
 */
final class output_test extends \advanced_testcase {
    /**
     * The topic card groups visible activities and respects a custom topic label.
     */
    public function test_topic_card_counts_and_label(): void {
        global $DB, $PAGE;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course([
            'format' => 'picturecards', 'numsections' => 1, 'fullname' => 'Plans & Projects',
        ]);
        $section = get_fast_modinfo($course)->get_section_info(1);
        $DB->set_field('course_sections', 'name', 'Scope & stakeholders', ['id' => $section->id]);
        rebuild_course_cache($course->id, true);
        $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'section' => 1]);
        $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'section' => 1]);
        $this->getDataGenerator()->create_module('forum', ['course' => $course->id, 'section' => 1]);
        $format = course_get_format($course);
        $format->update_course_format_options(['showtopiceyebrow' => 1, 'topiceyebrowword' => 'Week']);
        $PAGE->set_url('/course/view.php', ['id' => $course->id]);
        $PAGE->set_course($course);
        $renderer = $PAGE->get_renderer('format_picturecards');
        $classname = $format->get_output_classname('content');
        $html = $renderer->render(new $classname($format));
        $this->assertStringContainsString('Week 1', $html);
        $this->assertStringContainsString('Plans &amp; Projects', $html);
        $this->assertStringContainsString('Scope &amp; stakeholders', $html);
        $this->assertStringContainsString('aria-label="Page: 2"', $html);
        $this->assertStringContainsString('aria-label="Forum: 1"', $html);
        $format->update_course_format_options(['showtopiceyebrow' => 0]);
        $html = $renderer->render(new $classname($format));
        $this->assertStringNotContainsString('Week 1', $html);
    }
    /**
     * The custom icon must reach the final HTML, not just exported template data.
     */
    public function test_full_course_and_fragment_icons(): void {
        global $PAGE, $USER;
        $this->resetAfterTest();
        $this->setAdminUser();
        $USER->editing = 1;
        $course = $this->getDataGenerator()->create_course(['format' => 'picturecards']);
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'section' => 1]);
        activity_media::save($page->cmid, 'icon', 'reading', 0, ['colour' => 'teal']);
        $PAGE->set_url('/course/view.php', ['id' => $course->id]);
        $PAGE->set_course($course);
        $format = course_get_format($course);
        $renderer = $PAGE->get_renderer('format_picturecards');
        $classname = $format->get_output_classname('content');
        $html = $renderer->render(new $classname($format));
        $this->assertStringContainsString('format-picturecards-customicon', $html);
        $this->assertStringContainsString('fa-book-open', $html);
        $this->assertStringContainsString('format-picturecards-colour-teal', $html);
        $this->assertStringContainsString('data-picturecards-layout="tiles"', $html);
        $this->assertStringContainsString('data-picturecards-topic-appearance', $html);
        $modinfo = get_fast_modinfo($course);
        $section = $modinfo->get_section_info(1);
        $cm = $modinfo->get_cm($page->cmid);
        $html = $renderer->course_section_updated_cm_item($format, $section, $cm);
        $this->assertStringContainsString('fa-book-open', $html);
        $html = $renderer->course_section_updated($format, $section);
        $this->assertStringContainsString('fa-book-open', $html);
    }

    /**
     * The course image and General section content form the welcome card.
     */
    public function test_welcome_card_and_course_layout_control(): void {
        global $DB, $PAGE;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course(['format' => 'picturecards', 'numsections' => 2]);
        $section = get_fast_modinfo($course)->get_section_info(0);
        $DB->set_field('course_sections', 'name', 'Welcome to the course', ['id' => $section->id]);
        $DB->set_field('course_sections', 'summary', '<p>Start with Topic 1.</p>', ['id' => $section->id]);
        rebuild_course_cache($course->id, true);
        get_file_storage()->create_file_from_string([
            'contextid' => \context_course::instance($course->id)->id,
            'component' => 'course', 'filearea' => 'overviewfiles', 'itemid' => 0,
            'filepath' => '/', 'filename' => 'overview.png',
        ], base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a3WQAAAAASUVORK5CYII='));
        $PAGE->set_url('/course/view.php', ['id' => $course->id]);
        $PAGE->set_course($course);
        $format = course_get_format($course);
        $renderer = $PAGE->get_renderer('format_picturecards');
        $classname = $format->get_output_classname('content');
        $html = $renderer->render(new $classname($format));
        $this->assertSame(1, substr_count($html, 'data-picturecards-layout="tiles"'));
        $this->assertStringContainsString('format-picturecards-course-hero', $html);
        $this->assertStringContainsString('overview.png', $html);
        $this->assertStringContainsString('Welcome to the course', $html);
        $this->assertStringContainsString('Start with Topic 1.', $html);
        $this->assertStringContainsString('format-picturecards-topic-grid', $html);
        $this->assertSame(2, substr_count($html, 'format-picturecards-topic-card-media'));
        $this->assertStringContainsString('Topics', $html);
        $firsttopic = get_fast_modinfo($course)->get_section_info(1);
        $this->assertStringContainsString('data-picturecards-section-id="' . $firsttopic->id . '"', $html);
        $this->assertStringContainsString('/course/section.php?id=' . $firsttopic->id, $html);
    }

    /**
     * Topic cards use learner completion and omit hidden topics.
     */
    public function test_topic_cards_respect_visibility_and_completion(): void {
        global $DB, $PAGE;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course([
            'format' => 'picturecards',
            'numsections' => 2,
            'enablecompletion' => 1,
        ]);
        $page = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'section' => 1,
            'completion' => COMPLETION_TRACKING_MANUAL,
        ]);
        $hidden = get_fast_modinfo($course)->get_section_info(2);
        $DB->set_field('course_sections', 'visible', 0, ['id' => $hidden->id]);
        rebuild_course_cache($course->id, true);
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);
        $PAGE->set_url('/course/view.php', ['id' => $course->id]);
        $PAGE->set_course($course);
        $format = course_get_format($course);
        $renderer = $PAGE->get_renderer('format_picturecards');
        $classname = $format->get_output_classname('content');
        $html = $renderer->render(new $classname($format));
        $this->assertSame(1, substr_count($html, 'format-picturecards-topic-card-media'));
        $this->assertStringContainsString('0 of 1', $html);
        $this->assertStringNotContainsString('/course/section.php?id=' . $hidden->id, $html);
        $completion = new \completion_info($course);
        $completion->update_state(get_fast_modinfo($course)->get_cm($page->cmid), COMPLETION_COMPLETE, $student->id);
        $html = $renderer->render(new $classname($format));
        $this->assertStringContainsString('1 of 1', $html);
        $this->assertStringContainsString('format-picturecards-topic-card-complete', $html);
    }

    /**
     * A single topic has bottom links even when the theme supplies a course index.
     */
    public function test_single_topic_footer_navigation(): void {
        global $DB, $PAGE;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course([
            'format' => 'picturecards',
            'numsections' => 3,
            'coursedisplay' => COURSE_DISPLAY_MULTIPAGE,
        ]);
        foreach ([1 => 'Planning a project', 2 => 'Doing the work', 3 => 'Scheduling the work'] as $number => $name) {
            $sectionrecord = get_fast_modinfo($course)->get_section_info($number);
            $DB->set_field('course_sections', 'name', $name, ['id' => $sectionrecord->id]);
        }
        rebuild_course_cache($course->id, true);
        $format = course_get_format($course);
        $format->set_sectionnum(2);
        $section = get_fast_modinfo($course)->get_section_info(2);
        $PAGE->set_url('/course/section.php', ['id' => $section->id]);
        $PAGE->set_course($course);
        $renderer = $PAGE->get_renderer('format_picturecards');
        $classname = $format->get_output_classname('content');
        $html = $renderer->render(new $classname($format));
        $this->assertStringContainsString('format-picturecards-topic-navigation', $html);
        $this->assertStringContainsString('Planning a project', $html);
        $this->assertStringContainsString('Scheduling the work', $html);
        $this->assertStringContainsString('format-picturecards-single-title', $html);
    }

    /**
     * A learner never gets a footer link to a hidden topic or General section.
     */
    public function test_footer_skips_hidden_topics(): void {
        global $DB, $PAGE;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course([
            'format' => 'picturecards',
            'numsections' => 3,
            'coursedisplay' => COURSE_DISPLAY_MULTIPAGE,
        ]);
        $modinfo = get_fast_modinfo($course);
        $first = $modinfo->get_section_info(1);
        $second = $modinfo->get_section_info(2);
        $DB->set_field('course_sections', 'visible', 0, ['id' => $first->id]);
        rebuild_course_cache($course->id, true);
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);
        $PAGE->set_url('/course/section.php', ['id' => $second->id]);
        $PAGE->set_course($course);
        $format = course_get_format($course);
        $format->set_sectionnum(2);
        $renderer = $PAGE->get_renderer('format_picturecards');
        $classname = $format->get_output_classname('content');
        $html = $renderer->render(new $classname($format));
        $this->assertStringContainsString('format-picturecards-topic-navigation-next', $html);
        $this->assertSame(1, substr_count($html, 'format-picturecards-topic-navigation-link'));
        $this->assertStringNotContainsString('id=' . $first->id . '"', $html);
    }
}
