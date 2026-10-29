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
 * Main class for PictureCards course format.
 *
 * @package    format_picturecards
 * @copyright  2026 GENESTIAL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/course/format/lib.php');

use core\output\inplace_editable;

/**
 * Main class for the PictureCards format.
 */
class format_picturecards extends core_courseformat\base {
    /**
     * Returns true if this format uses sections.
     *
     * @return bool
     */
    public function uses_sections() {
        return true;
    }

    /**
     * This format supports the course index.
     *
     * @return bool
     */
    public function uses_course_index() {
        return true;
    }

    /**
     * Returns the display name of the given section.
     *
     * @param int|stdClass $section section object or section number
     * @return string
     */
    public function get_section_name($section) {
        $section = $this->get_section($section);
        if ((string)$section->name !== '') {
            return format_string($section->name, true, ['context' => context_course::instance($this->courseid)]);
        }
        return $this->get_default_section_name($section);
    }

    /**
     * Returns the default section name.
     *
     * @param int|stdClass $section section object or section number
     * @return string
     */
    public function get_default_section_name($section) {
        $section = $this->get_section($section);
        if ($section->sectionnum == 0) {
            return get_string('section0name', 'format_picturecards');
        }
        return get_string('newsection', 'format_picturecards');
    }

    /**
     * Generate page title.
     *
     * @return string
     */
    public function page_title(): string {
        return get_string('sectionoutline');
    }

    /**
     * Returns view URL.
     *
     * @param int|stdClass|null $section section object or section number
     * @param array $options options
     * @return moodle_url
     */
    public function get_view_url($section, $options = []) {
        $options['expanded'] = false;
        return parent::get_view_url($section, $options);
    }

    /**
     * Returns AJAX support information.
     *
     * @return stdClass
     */
    public function supports_ajax() {
        $ajaxsupport = new stdClass();
        $ajaxsupport->capable = true;
        return $ajaxsupport;
    }

    /**
     * Returns component support.
     *
     * @return bool
     */
    public function supports_components() {
        return true;
    }

    /**
     * Return the list of blocks to be automatically added for newly created courses.
     *
     * @return array
     */
    public function get_default_blocks() {
        return [
            BLOCK_POS_LEFT => [],
            BLOCK_POS_RIGHT => [],
        ];
    }

    /**
     * Course format options.
     *
     * @param bool $foreditform include form metadata
     * @return array
     */
    public function course_format_options($foreditform = false) {
        static $courseformatoptions = false;
        if ($courseformatoptions === false) {
            $courseconfig = get_config('moodlecourse');
            $courseformatoptions = [
                'hiddensections' => [
                    'default' => $courseconfig->hiddensections,
                    'type' => PARAM_INT,
                ],
                'coursedisplay' => [
                    'default' => $courseconfig->coursedisplay,
                    'type' => PARAM_INT,
                ],
                'showherodescription' => [
                    'default' => 0,
                    'type' => PARAM_INT,
                ],
                'showtopiceyebrow' => ['default' => 1, 'type' => PARAM_INT],
                'topiceyebrowword' => ['default' => '', 'type' => PARAM_TEXT],
                'palette' => ['default' => 'site', 'type' => PARAM_ALPHANUMEXT],
                'palette1' => ['default' => '#173e63', 'type' => PARAM_TEXT],
                'palette2' => ['default' => '#216c96', 'type' => PARAM_TEXT],
                'palette3' => ['default' => '#087d80', 'type' => PARAM_TEXT],
                'palette4' => ['default' => '#6baec6', 'type' => PARAM_TEXT],
                'palette5' => ['default' => '#a9d5c9', 'type' => PARAM_TEXT],
            ];
        }

        if ($foreditform && !isset($courseformatoptions['coursedisplay']['label'])) {
            $courseformatoptionsedit = [
                'hiddensections' => [
                    'label' => new lang_string('hiddensections'),
                    'help' => 'hiddensections',
                    'help_component' => 'moodle',
                    'element_type' => 'select',
                    'element_attributes' => [
                        [
                            0 => new lang_string('hiddensectionscollapsed'),
                            1 => new lang_string('hiddensectionsinvisible'),
                        ],
                    ],
                ],
                'coursedisplay' => [
                    'label' => new lang_string('coursedisplay'),
                    'element_type' => 'select',
                    'element_attributes' => [
                        [
                            COURSE_DISPLAY_SINGLEPAGE => new lang_string('coursedisplay_single'),
                            COURSE_DISPLAY_MULTIPAGE => new lang_string('coursedisplay_multi'),
                        ],
                    ],
                    'help' => 'coursedisplay',
                    'help_component' => 'moodle',
                ],
                'showherodescription' => [
                    'label' => new lang_string('showherodescription', 'format_picturecards'),
                    'element_type' => 'select',
                    'element_attributes' => [[0 => new lang_string('no'), 1 => new lang_string('yes')]],
                    'help' => 'showherodescription',
                    'help_component' => 'format_picturecards',
                ],
                'showtopiceyebrow' => [
                    'label' => new lang_string('showtopiceyebrow', 'format_picturecards'),
                    'element_type' => 'select',
                    'element_attributes' => [[0 => new lang_string('no'), 1 => new lang_string('yes')]],
                ],
                'topiceyebrowword' => [
                    'label' => new lang_string('topiceyebrowword', 'format_picturecards'),
                    'element_type' => 'text',
                    'help' => 'topiceyebrowword',
                    'help_component' => 'format_picturecards',
                ],
                'palette' => [
                    'label' => new lang_string('palette', 'format_picturecards'),
                    'element_type' => 'select',
                    'element_attributes' => [[
                        'site' => new lang_string('palettesite', 'format_picturecards'),
                        'ocean' => new lang_string('paletteocean', 'format_picturecards'),
                        'forest' => new lang_string('paletteforest', 'format_picturecards'),
                        'sunset' => new lang_string('palettesunset', 'format_picturecards'),
                        'slate' => new lang_string('paletteslate', 'format_picturecards'),
                        'bright' => new lang_string('palettebright', 'format_picturecards'),
                        'custom' => new lang_string('palettecustom', 'format_picturecards'),
                    ]],
                    'help' => 'palette', 'help_component' => 'format_picturecards',
                ],
            ];
            for ($i = 1; $i <= 5; $i++) {
                $courseformatoptionsedit['palette' . $i] = [
                    'label' => new lang_string('palettecolour', 'format_picturecards', $i),
                    'element_type' => 'text',
                ];
            }
            $courseformatoptions = array_merge_recursive($courseformatoptions, $courseformatoptionsedit);
        }
        return $courseformatoptions;
    }

    /**
     * Add palette controls to the course format form.
     * @param MoodleQuickForm $mform form to extend
     * @param bool $forsection whether this is a section form
     * @return array added elements
     */
    public function create_edit_form_elements(&$mform, $forsection = false) {
        $elements = parent::create_edit_form_elements($mform, $forsection);
        if (!$forsection) {
            global $PAGE;
            for ($i = 1; $i <= 5; $i++) {
                $name = 'palette' . $i;
                $mform->getElement($name)->updateAttributes(['type' => 'color']);
            }
            $presets = $this->get_palette_presets();
            $presets['site'] = $this->get_site_palette();
            $preview = html_writer::tag('div', '', [
                'class' => 'format-picturecards-palette-preview',
                'data-picturecards-palette-preview' => json_encode($presets),
                'data-picturecards-palette-select' => 'palette',
                'data-picturecards-palette-prefix' => 'palette',
                'aria-live' => 'polite',
            ]);
            $elements[] = $mform->addElement(
                'static',
                'palettepreview',
                get_string('palettepreview', 'format_picturecards'),
                $preview
            );
            $PAGE->requires->js_call_amd('format_picturecards/palette', 'init');
        }
        return $elements;
    }

    /**
     * Validate custom palette colours.
     * @param array $data submitted form data
     * @param array $files submitted files
     * @param array $errors existing errors
     * @return array validation errors
     */
    public function edit_form_validation($data, $files, $errors) {
        $result = parent::edit_form_validation($data, $files, $errors);
        if (($data['palette'] ?? '') === 'custom') {
            for ($i = 1; $i <= 5; $i++) {
                $name = 'palette' . $i;
                if (!preg_match('/^#[0-9a-fA-F]{6}$/D', $data[$name] ?? '')) {
                    $result[$name] = get_string('paletteinvalid', 'format_picturecards');
                }
            }
        }
        return $result;
    }

    /**
     * Return the optional numbered topic label.
     * @param int $number topic number
     * @return string formatted label or empty string
     */
    public function get_topic_eyebrow(int $number): string {
        $options = $this->get_format_options();
        if (empty($options['showtopiceyebrow'])) {
            return '';
        }
        $word = trim($options['topiceyebrowword'] ?? '');
        return $word === '' ? get_string('topicnumber', 'format_picturecards', $number) : $word . ' ' . $number;
    }

    /**
     * Return the built-in course palettes.
     * @return array palette names and colours
     */
    public static function get_palette_presets(): array {
        return [
            'ocean' => ['#173e63', '#216c96', '#087d80', '#6baec6', '#a9d5c9'],
            'forest' => ['#244b3d', '#56866c', '#78864a', '#a3b58a', '#d9c9a7'],
            'sunset' => ['#743653', '#b65c43', '#df8174', '#ca923b', '#ead6bc'],
            'slate' => ['#303c4b', '#4b6c82', '#70869a', '#aabac5', '#d2dce0'],
            'bright' => ['#5147a8', '#008d91', '#b53570', '#d99721', '#8355ac'],
        ];
    }

    /**
     * Return the site palette, including validated custom colours.
     * @return array site colours
     */
    public function get_site_palette(): array {
        $presets = $this->get_palette_presets();
        $choice = get_config('format_picturecards', 'defaultpalette') ?: 'ocean';
        if ($choice !== 'custom') {
            return $presets[$choice] ?? $presets['ocean'];
        }
        $colours = [];
        foreach ($presets['ocean'] as $index => $fallback) {
            $colour = get_config('format_picturecards', 'defaultpalette' . ($index + 1));
            $colours[] = is_string($colour) && preg_match('/^#[0-9a-fA-F]{6}$/D', $colour) ? $colour : $fallback;
        }
        return $colours;
    }

    /**
     * Return validated colours for a numbered section.
     * @param int $number section or activity number
     * @return array background, foreground and activity colours
     */
    public function get_palette_colour(int $number): array {
        $palettes = $this->get_palette_presets();
        $options = $this->get_format_options();
        $index = (max(1, $number) - 1) % 5;
        $choice = $options['palette'] ?? 'site';
        $colours = $choice === 'site' ? $this->get_site_palette() : ($palettes[$choice] ?? $palettes['ocean']);
        $colour = $colours[$index];
        if (($options['palette'] ?? '') === 'custom') {
            $custom = $options['palette' . ($index + 1)] ?? '';
            if (preg_match('/^#[0-9a-fA-F]{6}$/D', $custom)) {
                $colour = strtolower($custom);
            }
        }
        $channels = array_map(static function ($channel) {
            $value = hexdec($channel) / 255;
            return $value <= 0.04045 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
        }, str_split(substr($colour, 1), 2));
        $luminance = 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
        $activity = $colour;
        if ($luminance > 0.179) {
            $activity = sprintf('#%02x%02x%02x', ...array_map(
                static fn($channel) => (int)round(hexdec($channel) * 0.55),
                str_split(substr($colour, 1), 2)
            ));
        }
        return ['background' => $colour, 'foreground' => $luminance > 0.179 ? '#000000' : '#ffffff',
            'activity' => $activity];
    }

    /**
     * Return plugin configs for external functions.
     *
     * @return array
     */
    public function get_config_for_external() {
        return $this->get_format_options();
    }
}

/**
 * In-place editing callback for section names.
 *
 * @param string $itemtype item type
 * @param int $itemid item id
 * @param mixed $newvalue new value
 * @return inplace_editable|null
 */
function format_picturecards_inplace_editable($itemtype, $itemid, $newvalue) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/course/lib.php');
    if ($itemtype === 'sectionname' || $itemtype === 'sectionnamenl') {
        $section = $DB->get_record_sql(
            'SELECT s.* FROM {course_sections} s JOIN {course} c ON s.course = c.id WHERE s.id = ? AND c.format = ?',
            [$itemid, 'picturecards'],
            MUST_EXIST
        );
        return course_get_format($section->course)->inplace_editable_update_section_name($section, $itemtype, $newvalue);
    }
    return null;
}

/**
 * Serve plugin files (activity images).
 *
 * @param stdClass $course course object
 * @param stdClass|null $cm cm object
 * @param context $context context
 * @param string $filearea file area
 * @param array $args file arguments
 * @param bool $forcedownload force download
 * @param array $options options
 * @return void
 */
function format_picturecards_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if ($context->contextlevel === CONTEXT_COURSE && $filearea === 'topicimage') {
        require_login($course);
        if ($course->format !== 'picturecards' || !$args) {
            send_file_not_found();
        }
        $sectionid = (int)array_shift($args);
        $section = get_fast_modinfo($course)->get_section_info_by_id($sectionid);
        if (!$section || !$section->uservisible || (int)$section->course !== (int)$course->id || count($args) !== 1) {
            send_file_not_found();
        }
        $file = get_file_storage()->get_file(
            $context->id,
            'format_picturecards',
            'topicimage',
            $sectionid,
            '/',
            $args[0]
        );
        if (!$file || $file->is_directory() || !\format_picturecards\local\activity_media::is_safe_image($file)) {
            send_file_not_found();
        }
        send_stored_file($file, 0, 0, $forcedownload, $options);
    }
    if ($context->contextlevel !== CONTEXT_MODULE || $filearea !== 'activityimage') {
        send_file_not_found();
    }

    require_login($course, false, $cm);

    if (!$cm || (int)$cm->id !== (int)$context->instanceid || (int)$cm->course !== (int)$course->id) {
        send_file_not_found();
    }
    $modinfo = get_fast_modinfo($course);
    if (!$modinfo->get_cm($cm->id)->uservisible) {
        send_file_not_found();
    }

    if (count($args) < 2) {
        send_file_not_found();
    }

    $itemid = (int)array_shift($args);
    if ($itemid !== 0) {
        send_file_not_found();
    }
    $filename = array_pop($args);
    $filepath = '/' . (empty($args) ? '' : implode('/', $args) . '/');

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'format_picturecards', 'activityimage', $itemid, $filepath, $filename);
    if (!$file || $file->is_directory() || !\format_picturecards\local\activity_media::is_safe_image($file)) {
        send_file_not_found();
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/**
 * Extend all module forms with PictureCards media fields.
 *
 * @param moodleform_mod $formwrapper form wrapper
 * @param MoodleQuickForm $mform quickform instance
 * @return void
 */
function format_picturecards_coursemodule_standard_elements(moodleform_mod $formwrapper, MoodleQuickForm $mform): void {
    if (course_get_format($formwrapper->get_course())->get_format() !== 'picturecards') {
        return;
    }

    $course = $formwrapper->get_course();
    $courseid = is_object($course) ? (int)$course->id : (int)$course;
    $cm = $formwrapper->get_coursemodule();
    $filecontext = $cm ? context_module::instance($cm->id) : context_course::instance($courseid);
    if (!has_capability('moodle/course:manageactivities', $filecontext)) {
        return;
    }
    $filemanageroptions = \format_picturecards\local\activity_media::file_manager_options($filecontext);

    $mform->addElement('header', 'format_picturecards_activitymedia', get_string('activitymedia', 'format_picturecards'));
    $mform->addHelpButton('format_picturecards_activitymedia', 'activitymedia', 'format_picturecards');

    $mform->addElement(
        'select',
        'format_picturecards_mediatype',
        get_string('mediatype', 'format_picturecards'),
        \format_picturecards\local\activity_media::media_type_options()
    );
    $mform->setType('format_picturecards_mediatype', PARAM_ALPHA);
    $mform->setDefault('format_picturecards_mediatype', \format_picturecards\local\activity_media::TYPE_DEFAULT);
    $mform->addHelpButton('format_picturecards_mediatype', 'mediatype', 'format_picturecards');

    $mform->addElement(
        'select',
        'format_picturecards_iconkey',
        get_string('customicon', 'format_picturecards'),
        \format_picturecards\local\activity_media::icon_options()
    );
    $mform->setType('format_picturecards_iconkey', PARAM_ALPHANUMEXT);
    $mform->addHelpButton('format_picturecards_iconkey', 'customicon', 'format_picturecards');
    $mform->addElement(
        'static',
        'format_picturecards_icon_legend',
        '',
        \format_picturecards\local\activity_media::icon_preview_legend_html()
    );
    $mform->hideIf(
        'format_picturecards_icon_legend',
        'format_picturecards_mediatype',
        'neq',
        \format_picturecards\local\activity_media::TYPE_ICON
    );
    $mform->disabledIf(
        'format_picturecards_iconkey',
        'format_picturecards_mediatype',
        'neq',
        \format_picturecards\local\activity_media::TYPE_ICON
    );

    $mform->addElement(
        'filemanager',
        'format_picturecards_image_draftitemid',
        get_string('activityimage', 'format_picturecards'),
        null,
        $filemanageroptions
    );
    $mform->addHelpButton('format_picturecards_image_draftitemid', 'activityimage', 'format_picturecards');
    $mform->disabledIf(
        'format_picturecards_image_draftitemid',
        'format_picturecards_mediatype',
        'neq',
        \format_picturecards\local\activity_media::TYPE_IMAGE
    );
}

/**
 * Populate PictureCards media values after module form data is loaded.
 *
 * @param moodleform_mod $formwrapper form wrapper
 * @param MoodleQuickForm $mform quickform instance
 * @return void
 */
function format_picturecards_coursemodule_definition_after_data(moodleform_mod $formwrapper, MoodleQuickForm $mform): void {
    if (course_get_format($formwrapper->get_course())->get_format() !== 'picturecards') {
        return;
    }

    $cm = $formwrapper->get_coursemodule();
    if (!$cm || !$mform->elementExists('format_picturecards_mediatype')) {
        return;
    }

    // Never overwrite submitted media choices while validating the form.
    if (data_submitted()) {
        return;
    }
    $record = \format_picturecards\local\activity_media::get_record((int)$cm->id);
    $data = new stdClass();
    $data->format_picturecards_mediatype = $record->mediatype ?? \format_picturecards\local\activity_media::TYPE_DEFAULT;
    $data->format_picturecards_iconkey = $record->iconkey ?? '';

    $draftitemid = file_get_submitted_draft_itemid('format_picturecards_image_draftitemid');
    $modcontext = context_module::instance($cm->id);
    file_prepare_draft_area(
        $draftitemid,
        $modcontext->id,
        'format_picturecards',
        'activityimage',
        0,
        \format_picturecards\local\activity_media::file_manager_options($modcontext)
    );
    $data->format_picturecards_image_draftitemid = $draftitemid;
    $formwrapper->set_data($data);
}

/**
 * Save PictureCards media settings after module add/update.
 *
 * @param stdClass $moduleinfo module data
 * @param stdClass $course course data
 * @return stdClass
 */
function format_picturecards_coursemodule_edit_post_actions($moduleinfo, $course) {
    if (course_get_format($course)->get_format() !== 'picturecards') {
        return $moduleinfo;
    }

    if (empty($moduleinfo->coursemodule)) {
        return $moduleinfo;
    }
    // Programmatic updates without these form fields must preserve existing media.
    if (!property_exists($moduleinfo, 'format_picturecards_mediatype')) {
        return $moduleinfo;
    }

    $cmid = (int)$moduleinfo->coursemodule;
    $mediatype = clean_param($moduleinfo->format_picturecards_mediatype ??
        \format_picturecards\local\activity_media::TYPE_DEFAULT, PARAM_ALPHA);
    $iconkey = clean_param($moduleinfo->format_picturecards_iconkey ?? '', PARAM_ALPHANUMEXT);
    $draftitemid = (int)($moduleinfo->format_picturecards_image_draftitemid ?? 0);

    \format_picturecards\local\activity_media::save($cmid, $mediatype, $iconkey, $draftitemid);
    return $moduleinfo;
}
