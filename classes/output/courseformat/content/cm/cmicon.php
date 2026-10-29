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

namespace format_picturecards\output\courseformat\content\cm;

use core_courseformat\output\local\content\cm\cmicon as core_cmicon;
use format_picturecards\local\activity_media;

/**
 * Custom activity icon output for the PictureCards format.
 *
 * @package    format_picturecards
 * @copyright  2026 GENESTIAL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cmicon extends core_cmicon {
    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output renderer
     * @return array
     */
    public function export_for_template(\renderer_base $output): array {
        $data = parent::export_for_template($output);
        if (empty($data)) {
            return $data;
        }

        $data['cmid'] = $this->mod->id;
        $sectioncms = $this->format->get_modinfo()->sections[$this->mod->sectionnum] ?? [];
        $position = array_search($this->mod->id, $sectioncms, true);
        $data['picturecardscolour'] = $this->format->get_palette_colour($position === false ? 1 : $position + 1)['activity'];

        $custommedia = activity_media::export_for_cm($this->mod, $output);
        $data['custommedia'] = $custommedia['mediatype'] ?? activity_media::TYPE_DEFAULT;
        $data += activity_media::normalise_appearance($custommedia);

        if (($custommedia['mediatype'] ?? activity_media::TYPE_DEFAULT) === activity_media::TYPE_IMAGE) {
            $data['customimageurl'] = $custommedia['imageurl'] ?? null;
        } else if (($custommedia['mediatype'] ?? activity_media::TYPE_DEFAULT) === activity_media::TYPE_ICON) {
            $data['customiconclass'] = $custommedia['iconclass'] ?? null;
            $data['customiconlabel'] = $custommedia['iconlabel'] ?? null;
        }

        return $data;
    }

    /**
     * Override template name to use picturecards-specific icon markup.
     *
     * @param \renderer_base $renderer renderer
     * @return string
     */
    public function get_template_name(\renderer_base $renderer): string {
        return 'format_picturecards/local/content/cm/cmicon';
    }
}
