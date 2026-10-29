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

/**
 * Settings for format_picturecards.
 *
 * @package    format_picturecards
 * @copyright  2026 GENESTIAL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    require_once($CFG->dirroot . '/course/format/picturecards/lib.php');
    $palettes = [];
    foreach (['ocean', 'forest', 'sunset', 'slate', 'bright', 'custom'] as $palette) {
        $palettes[$palette] = get_string('palette' . $palette, 'format_picturecards');
    }
    $palettesetting = new admin_setting_configselect(
        'format_picturecards/defaultpalette',
        get_string('defaultpalette', 'format_picturecards'),
        get_string('defaultpalette_desc', 'format_picturecards'),
        'ocean',
        $palettes
    );
    $defaults = ['#173e63', '#216c96', '#087d80', '#6baec6', '#a9d5c9'];
    $colourinputs = [];
    $coloursettings = [];
    foreach ($defaults as $index => $colour) {
        $setting = new admin_setting_configcolourpicker(
            'format_picturecards/defaultpalette' . ($index + 1),
            get_string('sitepalettecolour', 'format_picturecards', $index + 1),
            get_string('sitepalettecolour_desc', 'format_picturecards'),
            $colour
        );
        $colourinputs[] = $setting->get_full_name();
        $coloursettings[] = $setting;
    }
    $preview = html_writer::tag('div', '', [
        'class' => 'format-picturecards-palette-preview',
        'data-picturecards-palette-preview' => json_encode(format_picturecards::get_palette_presets()),
        'data-picturecards-palette-select' => $palettesetting->get_full_name(),
        'data-picturecards-palette-inputs' => json_encode($colourinputs),
        'aria-live' => 'polite',
    ]);
    $settings->add(new admin_setting_heading(
        'format_picturecards/palettepreview',
        get_string('palettepreview', 'format_picturecards'),
        $preview
    ));
    $settings->add($palettesetting);
    foreach ($coloursettings as $setting) {
        $settings->add($setting);
    }
    $PAGE->requires->js_call_amd('format_picturecards/palette', 'init');
    $settings->add(new admin_setting_configtext(
        'format_picturecards/maximagesizebytes',
        get_string('maximagesizebytes', 'format_picturecards'),
        get_string('maximagesizebytes_desc', 'format_picturecards'),
        0,
        PARAM_INT
    ));
}
