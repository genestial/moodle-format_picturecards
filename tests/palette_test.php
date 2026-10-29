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
 * Course palette inheritance and override tests.
 * @package format_picturecards
 * @copyright 2026 GENESTIAL
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \format_picturecards
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\format_picturecards::class)]
final class palette_test extends \advanced_testcase {
    /**
     * Site changes propagate to inheriting courses, while overrides remain stable.
     */
    public function test_site_inheritance_and_course_override(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['format' => 'picturecards']);
        $format = course_get_format($course);
        set_config('defaultpalette', 'forest', 'format_picturecards');
        $this->assertSame('#244b3d', $format->get_palette_colour(1)['background']);
        $format->update_course_format_options(['palette' => 'sunset']);
        $this->assertSame('#743653', $format->get_palette_colour(1)['background']);
        set_config('defaultpalette', 'bright', 'format_picturecards');
        $this->assertSame('#743653', $format->get_palette_colour(1)['background']);
        $format->update_course_format_options(['palette' => 'site']);
        $this->assertSame('#5147a8', $format->get_palette_colour(1)['background']);
    }

    /**
     * Custom colour values are validated before they reach inline styles.
     */
    public function test_custom_palette_validation_and_fallback(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['format' => 'picturecards']);
        $format = course_get_format($course);
        $format->update_course_format_options(['palette' => 'custom', 'palette1' => '#ABCDEF']);
        $this->assertSame('#abcdef', $format->get_palette_colour(1)['background']);
        $this->assertArrayHasKey('palette1', $format->edit_form_validation([
            'palette' => 'custom', 'palette1' => 'invalid', 'palette2' => '#000000',
            'palette3' => '#000000', 'palette4' => '#000000', 'palette5' => '#000000',
        ], [], []));
        $format->update_course_format_options(['palette1' => 'invalid']);
        $this->assertSame('#173e63', $format->get_palette_colour(1)['background']);
    }

    /**
     * A site custom palette is inherited and invalid stored colours fall back safely.
     */
    public function test_inherited_site_custom_palette(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['format' => 'picturecards']);
        $format = course_get_format($course);
        set_config('defaultpalette', 'custom', 'format_picturecards');
        set_config('defaultpalette1', '#123456', 'format_picturecards');
        set_config('defaultpalette2', 'invalid', 'format_picturecards');
        $this->assertSame('#123456', $format->get_palette_colour(1)['background']);
        $this->assertSame('#216c96', $format->get_palette_colour(2)['background']);
    }
}
