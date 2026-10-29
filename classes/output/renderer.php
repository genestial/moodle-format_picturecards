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

namespace format_picturecards\output;

use core_courseformat\output\section_renderer;
use moodle_page;

/**
 * Basic renderer for picturecards format.
 *
 * @package    format_picturecards
 * @copyright  2026 GENESTIAL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends section_renderer {
    /**
     * Override the icon block for full-page and reactive editor entry templates.
     * Nested icon data is exported as an array by core, so its template name alone
     * is insufficient to replace the core Mustache partial.
     * @param string $templatename requested template
     * @param mixed $context exported data
     * @return string rendered HTML
     */
    public function render_from_template($templatename, $context) {
        $templates = [
            'core_courseformat/local/content' => 'format_picturecards/local/content',
            'core_courseformat/local/content/section' => 'format_picturecards/local/content/section',
            'core_courseformat/local/content/section/cmitem' => 'format_picturecards/local/content/section/cmitem',
        ];
        return parent::render_from_template($templates[$templatename] ?? $templatename, $context);
    }

    /**
     * Constructor.
     *
     * @param moodle_page $page page instance
     * @param string $target rendering target
     */
    public function __construct(moodle_page $page, $target) {
        parent::__construct($page, $target);
        $page->set_other_editing_capability('moodle/course:update');
        $page->requires->js_call_amd('format_picturecards/view', 'init');
    }

    /**
     * Generate the section title.
     *
     * @param section_info|stdClass $section section record
     * @param stdClass $course course record
     * @return string
     */
    public function section_title($section, $course) {
        return $this->render(\course_get_format($course)->inplace_editable_render_section_name($section));
    }

    /**
     * Generate section title without a link.
     *
     * @param section_info|stdClass $section section record
     * @param stdClass|int $course course record
     * @return string
     */
    public function section_title_without_link($section, $course) {
        return $this->render(\course_get_format($course)->inplace_editable_render_section_name($section, false));
    }
}
