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

namespace format_picturecards\output\courseformat;

/**
 * Course content with topic cards and footer navigation.
 * @package format_picturecards
 * @copyright 2026 GENESTIAL
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class content extends \core_courseformat\output\local\content {
    /**
     * Add course topic cards and footer navigation for single topic pages.
     * @param \renderer_base $output renderer
     * @return \stdClass template data
     */
    public function export_for_template(\renderer_base $output): \stdClass {
        global $PAGE, $USER;
        $data = parent::export_for_template($output);
        if (empty($data->singlesection)) {
            global $CFG;
            require_once($CFG->dirroot . '/course/lib.php');
            $course = $this->format->get_course();
            $data->picturecardscoursehero = (object)[
                'title' => format_string($course->fullname, true, ['context' => $PAGE->context]),
            ];
            if ($image = course_get_courseimage($course)) {
                $data->picturecardscoursehero->image = \moodle_url::make_pluginfile_url(
                    $image->get_contextid(),
                    $image->get_component(),
                    $image->get_filearea(),
                    null,
                    $image->get_filepath(),
                    $image->get_filename()
                )->out(false);
            }
            $options = $this->format->get_format_options();
            if (!empty($options['showherodescription']) && !empty($course->summary)) {
                $context = \context_course::instance($course->id);
                $summary = file_rewrite_pluginfile_urls(
                    $course->summary,
                    'pluginfile.php',
                    $context->id,
                    'course',
                    'summary',
                    null
                );
                $data->picturecardscoursehero->description = format_text($summary, $course->summaryformat, ['context' => $context]);
            }
        }
        if (empty($data->singlesection) && !$PAGE->user_is_editing()) {
            $modinfo = $this->format->get_modinfo();
            $completion = new \completion_info($this->format->get_course());
            $tracked = $completion->is_tracked_user($USER->id);
            $cards = [];
            foreach ($data->sections as $section) {
                if (empty($section->num) || empty($section->picturecardshero) || !empty($section->isstealth)) {
                    continue;
                }
                $icons = [];
                $total = 0;
                $done = 0;
                foreach ($modinfo->sections[$section->num] ?? [] as $cmid) {
                    $cm = $modinfo->get_cm($cmid);
                    if (
                        !$cm->is_visible_on_course_page() ||
                        (method_exists($cm, 'is_of_type_that_can_display') && !$cm->is_of_type_that_can_display())
                    ) {
                        continue;
                    }
                    if (!isset($icons[$cm->modname])) {
                        $icons[$cm->modname] = (object)[
                            'url' => $output->image_url('monologo', $cm->modname)->out(false),
                            'name' => get_string('modulename', 'mod_' . $cm->modname),
                            'count' => 0,
                        ];
                    }
                    $icons[$cm->modname]->count++;
                    if ($tracked && $cm->uservisible && $completion->is_enabled($cm)) {
                        $total++;
                        $state = $completion->get_data($cm, true)->completionstate;
                        if (in_array($state, [COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS], true)) {
                            $done++;
                        }
                    }
                }
                $cards[] = (object)[
                    'id' => $section->id,
                    'number' => $this->format->get_topic_eyebrow((int)$section->num),
                    'name' => $section->header->name ?? $section->sectionname,
                    'url' => !empty($section->header->url) ?
                        (new \moodle_url('/course/section.php', ['id' => $section->id]))->out(false) : null,
                    'cover' => $section->picturecardscover ?? null,
                    'colour' => $section->picturecardscolour,
                    'foreground' => $section->picturecardsforeground,
                    'icons' => array_values($icons),
                    'hasicons' => !empty($icons),
                    'progress' => $total ? (object)[
                        'label' => get_string('progresscount', 'format_picturecards', (object)[
                            'done' => $done,
                            'total' => $total,
                        ]),
                        'percent' => (int)round(100 * $done / $total),
                    ] : null,
                    'iscomplete' => $total > 0 && $done === $total,
                    'ishidden' => !empty($section->ishidden),
                    'restricted' => !empty($section->restrictionlock),
                ];
            }
            if ($cards) {
                $data->picturecardstopiccards = $cards;
                $data->hastopiccards = true;
            }
        }
        if (empty($data->singlesection) || (int)$data->singlesection->num < 1) {
            return $data;
        }
        if (!empty($data->sectionnavigation)) {
            $navigation = clone $data->sectionnavigation;
        } else {
            $sectionnavigation = new \core_courseformat\output\local\content\sectionnavigation(
                $this->format,
                (int)$this->format->get_sectionnum()
            );
            $navigation = $sectionnavigation->export_for_template($output);
        }
        $generalurl = course_get_url($this->format->get_course(), 0, ['navigation' => true]);
        if (
            (int)$data->singlesection->num === 1 ||
                ($generalurl && !empty($navigation->previousurl) &&
                    $navigation->previousurl->out(false) === $generalurl->out(false))
        ) {
            $navigation->hasprevious = false;
        }
        if (!empty($navigation->hasprevious) || !empty($navigation->hasnext)) {
            $data->picturecardsnavigation = $navigation;
        }
        return $data;
    }
}
