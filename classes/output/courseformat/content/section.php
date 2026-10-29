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

namespace format_picturecards\output\courseformat\content;

use format_picturecards\local\topic_image;

/**
 * Topic cover and real completion summary.
 * @package format_picturecards
 * @copyright 2026 GENESTIAL
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section extends \core_courseformat\output\local\content\section {
    /**
     * Decorate visible non-delegated topics with the teacher-selected picture.
     * @param \renderer_base $output renderer
     * @return \stdClass
     */
    public function export_for_template(\renderer_base $output): \stdClass {
        $data = parent::export_for_template($output);
        if ((int)$this->section->section === 0 && $this->section->uservisible) {
            $data->picturecardswelcome = true;
            return $data;
        }
        if (!$this->section->uservisible || $this->section->is_delegated() || !$this->section->section) {
            return $data;
        }
        $data->picturecardshero = true;
        $palette = $this->format->get_palette_colour((int)$this->section->section);
        $data->picturecardscolour = $palette['background'];
        $data->picturecardsforeground = $palette['foreground'];
        $data->picturecardstopiclabel = $this->format->get_topic_eyebrow((int)$this->section->section);
        $data->picturecardsalltopics = (new \moodle_url('/course/view.php', ['id' => $this->format->get_courseid()]))->out(false);
        $data->picturecardsappearance = $data->editing && has_capability(
            'moodle/course:update',
            \context_course::instance($this->format->get_courseid())
        );
        $url = topic_image::get_url($this->format->get_courseid(), $this->section->id);
        $data->picturecardscover = $url ? $url->out(false) : null;
        $total = 0;
        $done = 0;
        foreach ($data->cmlist->cms ?? [] as $item) {
            $state = $item->cmitem->cmformat->picturecardsstate ?? 'untracked';
            $total += $state !== 'untracked' ? 1 : 0;
            $done += $state === 'done' ? 1 : 0;
        }
        if ($total) {
            $data->picturecardsprogress = (object)[
                'label' => get_string('progresscount', 'format_picturecards', (object)['done' => $done, 'total' => $total]),
                'percent' => (int)round(100 * $done / $total),
            ];
        }
        return $data;
    }
}
