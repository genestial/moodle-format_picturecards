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

namespace format_picturecards\local;

use cm_info;
use context;
use context_module;
use moodle_url;
use renderer_base;
use stdClass;

/**
 * Activity media helper for the PictureCards format.
 *
 * @package    format_picturecards
 * @copyright  2026 GENESTIAL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity_media {
    /**
     * @var string default media type
     */
    public const TYPE_DEFAULT = 'default';
    /**
     * @var string icon media type
     */
    public const TYPE_ICON = 'icon';
    /**
     * @var string image media type
     */
    public const TYPE_IMAGE = 'image';

    /**
     * Return media type options.
     *
     * @return array
     */
    public static function media_type_options(): array {
        return [
            self::TYPE_DEFAULT => get_string('mediatype_default', 'format_picturecards'),
            self::TYPE_ICON => get_string('mediatype_icon', 'format_picturecards'),
            self::TYPE_IMAGE => get_string('mediatype_image', 'format_picturecards'),
        ];
    }

    /**
     * Return icon picker options.
     *
     * @return array
     */
    public static function icon_options(): array {
        return [
            '' => get_string('choose', 'moodle'),
            'discussion' => get_string('picturecards:visual_discussion', 'format_picturecards'),
            'reading' => get_string('picturecards:visual_reading', 'format_picturecards'),
            'video' => get_string('picturecards:visual_video', 'format_picturecards'),
            'lab' => get_string('picturecards:visual_lab', 'format_picturecards'),
            'goal' => get_string('picturecards:visual_goal', 'format_picturecards'),
            'path' => get_string('picturecards:visual_path', 'format_picturecards'),
            'idea' => get_string('picturecards:visual_idea', 'format_picturecards'),
            'default' => get_string('picturecards:visual_default', 'format_picturecards'),
        ];
    }

    /**
     * Return font-awesome class for icon key.
     *
     * @param string $iconkey icon key
     * @return string|null
     */
    public static function icon_class(string $iconkey): ?string {
        $map = [
            'discussion' => 'fa-regular fa-comments',
            'reading' => 'fa-solid fa-book-open',
            'video' => 'fa-regular fa-circle-play',
            'lab' => 'fa-solid fa-flask',
            'goal' => 'fa-solid fa-bullseye',
            'path' => 'fa-solid fa-route',
            'idea' => 'fa-regular fa-lightbulb',
            'default' => 'fa-solid fa-shapes',
        ];
        return $map[$iconkey] ?? null;
    }

    /**
     * Effective max upload size for activity images (respects site, course, and plugin cap).
     *
     * @param context $context Course or module context for the activity form / storage
     * @return int max bytes
     */
    public static function resolve_max_bytes(context $context): int {
        global $CFG;
        $coursectx = $context->get_course_context();
        $course = get_course($coursectx->instanceid);
        $usermax = get_user_max_upload_file_size($context, $CFG->maxbytes, $course->maxbytes);
        $configured = (int)get_config('format_picturecards', 'maximagesizebytes');
        if ($configured <= 0) {
            return $usermax;
        }
        return min($configured, $usermax);
    }

    /**
     * File manager options for draft and stored activity images.
     *
     * @param context $context Course context (add activity) or module context (edit)
     * @return array options for filemanager / file_prepare_draft_area / file_save_draft_area_files
     */
    public static function file_manager_options(context $context): array {
        global $CFG;
        require_once($CFG->dirroot . '/repository/lib.php');
        return [
            'subdirs' => 0,
            'maxfiles' => 1,
            'accepted_types' => ['.jpg', '.jpeg', '.png', '.gif', '.webp'],
            'maxbytes' => self::resolve_max_bytes($context),
            'return_types' => FILE_INTERNAL,
        ];
    }

    /**
     * Small visual legend of built-in icons (for the activity settings form).
     *
     * @return string HTML fragment
     */
    public static function icon_preview_legend_html(): string {
        $items = [];
        foreach (self::icon_options() as $key => $label) {
            if ($key === '') {
                continue;
            }
            $class = self::icon_class((string)$key);
            if (!$class) {
                continue;
            }
            $icon = \html_writer::tag('i', '', ['class' => $class, 'aria-hidden' => 'true']);
            $text = \html_writer::span($label, 'format-picturecards-icon-legend-label');
            $items[] = \html_writer::span($icon . ' ' . $text, 'format-picturecards-icon-legend-item', ['title' => $label]);
        }
        return \html_writer::div(
            \html_writer::tag(
                'div',
                get_string('iconlegend', 'format_picturecards'),
                ['class' => 'format-picturecards-icon-legend-title']
            ) .
            \html_writer::div(implode('', $items), 'format-picturecards-icon-legend-row'),
            'format-picturecards-icon-legend mb-2'
        );
    }

    /**
     * Get stored media record for a coursemodule.
     *
     * @param int $cmid course module id
     * @return stdClass|null
     */
    public static function get_record(int $cmid): ?stdClass {
        global $DB;
        return $DB->get_record('format_picturecards_media', ['coursemoduleid' => $cmid]) ?: null;
    }

    /**
     * Save media settings and draft image files for a module.
     *
     * @param int $cmid course module id
     * @param string $mediatype media type
     * @param string $iconkey icon key
     * @param int $draftitemid draft item id
     * @param array|null $appearance optional colour and focal point; omitted values are preserved
     * @return void
     */
    public static function save(int $cmid, string $mediatype, string $iconkey, int $draftitemid, ?array $appearance = null): void {
        global $DB;

        $cm = get_coursemodule_from_id('', $cmid, 0, false, MUST_EXIST);
        $modcontext = context_module::instance($cmid);
        require_capability('moodle/course:manageactivities', $modcontext);
        if (get_course($cm->course)->format !== 'picturecards') {
            throw new \coding_exception('Activity media can only be edited in a PictureCards course.');
        }
        $mediatype = self::normalise_media_type($mediatype);
        if ($mediatype === self::TYPE_IMAGE && $draftitemid > 0) {
            self::validate_draft($draftitemid, $modcontext);
        }
        $iconkey = self::normalise_icon_key($iconkey);
        if ($mediatype !== self::TYPE_ICON) {
            $iconkey = '';
        }
        if ($mediatype === self::TYPE_ICON && $iconkey === '') {
            $mediatype = self::TYPE_DEFAULT;
        }

        $transaction = $DB->start_delegated_transaction();
        $record = self::get_record($cmid);
        $appearance = self::normalise_appearance($appearance ?? (array)($record ?? new stdClass()));
        $now = time();
        if ($record) {
            $record->mediatype = $mediatype;
            $record->iconkey = $iconkey;
            $record->timemodified = $now;
            foreach ($appearance as $field => $value) {
                $record->$field = $value;
            }
            $DB->update_record('format_picturecards_media', $record);
        } else {
            $record = (object)([
                'coursemoduleid' => $cmid,
                'mediatype' => $mediatype,
                'iconkey' => $iconkey,
                'timemodified' => $now,
            ] + $appearance);
            $DB->insert_record('format_picturecards_media', $record);
        }

        if ($mediatype !== self::TYPE_IMAGE) {
            get_file_storage()->delete_area_files($modcontext->id, 'format_picturecards', 'activityimage');
        } else if ($draftitemid > 0) {
            file_save_draft_area_files(
                $draftitemid,
                $modcontext->id,
                'format_picturecards',
                'activityimage',
                0,
                self::file_manager_options($modcontext)
            );
        }
        $transaction->allow_commit();
        self::reset_course_cache((int)$cm->course);
    }

    /**
     * Validate the current user's draft before changing persistent data.
     * @param int $draftitemid draft area
     * @param context $context target context
     */
    public static function validate_draft(int $draftitemid, context $context): void {
        global $USER;
        $usercontext = \context_user::instance($USER->id);
        $files = get_file_storage()->get_area_files($usercontext->id, 'user', 'draft', $draftitemid, 'id', false);
        if (count($files) > 1) {
            throw new \moodle_exception('invalidimage', 'format_picturecards');
        }
        $maxbytes = self::resolve_max_bytes($context);
        foreach ($files as $file) {
            if (
                !self::is_safe_image($file) ||
                    $file->get_filepath() !== '/' || ($maxbytes > 0 && $file->get_filesize() > $maxbytes)
            ) {
                throw new \moodle_exception('invalidimage', 'format_picturecards');
            }
        }
    }

    /**
     * Load course media once per request, including negative lookups.
     * @param int $courseid course identifier
     * @return array records indexed by course module
     */
    public static function course_records(int $courseid): array {
        global $DB;
        $cache = \cache::make_from_params(\cache_store::MODE_REQUEST, 'format_picturecards', 'media');
        $records = $cache->get($courseid);
        if ($records === false) {
            $records = $DB->get_records_sql('SELECT m.coursemoduleid, m.id, m.mediatype, m.iconkey, m.timemodified,
                         m.colour, m.focusx, m.focusy
                  FROM {format_picturecards_media} m
                  JOIN {course_modules} cm ON cm.id = m.coursemoduleid WHERE cm.course = ?', [$courseid]);
            $cache->set($courseid, $records);
        }
        return $records;
    }

    /**
     * Invalidate request data after a mutation.
     * @param int $courseid course identifier
     */
    public static function reset_course_cache(int $courseid): void {
        \cache::make_from_params(\cache_store::MODE_REQUEST, 'format_picturecards', 'media')->delete($courseid);
        \cache::make_from_params(\cache_store::MODE_REQUEST, 'format_picturecards', 'activityimages')->delete($courseid);
    }

    /**
     * Load activity image files in one query for a whole course.
     * @param int $courseid course identifier
     * @return array files indexed by course module
     */
    private static function course_image_files(int $courseid): array {
        global $DB;
        $cache = \cache::make_from_params(\cache_store::MODE_REQUEST, 'format_picturecards', 'activityimages');
        $files = $cache->get($courseid);
        if ($files !== false) {
            return $files;
        }
        $sql = 'SELECT f.id AS fileid, f.*, r.repositoryid, r.reference,
                       r.lastsync AS referencelastsync, c.instanceid AS cmid
                  FROM {files} f
                  JOIN {context} c ON c.id = f.contextid AND c.contextlevel = :contextlevel
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND cm.course = :courseid
             LEFT JOIN {files_reference} r ON r.id = f.referencefileid
                 WHERE f.component = :component AND f.filearea = :filearea
                       AND f.itemid = 0 AND f.filename <> :dot
              ORDER BY f.timemodified DESC, f.id DESC';
        $records = $DB->get_records_sql($sql, [
            'contextlevel' => CONTEXT_MODULE,
            'courseid' => $courseid,
            'component' => 'format_picturecards',
            'filearea' => 'activityimage',
            'dot' => '.',
        ]);
        $files = [];
        $storage = get_file_storage();
        foreach ($records as $record) {
            $cmid = (int)$record->cmid;
            if (!array_key_exists($cmid, $files)) {
                $file = $storage->get_file_instance($record);
                $files[$cmid] = self::is_safe_image($file) ? $file : null;
            }
        }
        $cache->set($courseid, $files);
        return $files;
    }

    /**
     * Delete only this plugin's data for a module.
     * @param int $cmid course module identifier
     * @param int|null $contextid context, when it still exists
     */
    public static function delete(int $cmid, ?int $contextid = null): void {
        global $DB;
        $courseid = $DB->get_field('course_modules', 'course', ['id' => $cmid]);
        $DB->delete_records('format_picturecards_media', ['coursemoduleid' => $cmid]);
        if ($contextid !== null) {
            get_file_storage()->delete_area_files($contextid, 'format_picturecards', 'activityimage');
        }
        if ($courseid) {
            self::reset_course_cache((int)$courseid);
        }
    }

    /**
     * Build media display data for a course module.
     *
     * @param cm_info $mod course module info
     * @param renderer_base $output renderer
     * @return array
     */
    public static function export_for_cm(cm_info $mod, renderer_base $output): array {
        $record = self::course_records((int)$mod->course)[$mod->id] ?? null;
        if (!$record) {
            return ['mediatype' => self::TYPE_DEFAULT];
        }

        $mediatype = self::normalise_media_type($record->mediatype);
        $data = ['mediatype' => $mediatype] + self::normalise_appearance((array)$record);

        if ($mediatype === self::TYPE_IMAGE) {
            $imageurl = self::get_image_url((int)$mod->id, (int)$mod->course);
            if ($imageurl) {
                $data['imageurl'] = $imageurl->out(false);
            } else {
                $data['mediatype'] = self::TYPE_DEFAULT;
            }
        } else if ($mediatype === self::TYPE_ICON) {
            $iconclass = self::icon_class((string)$record->iconkey);
            if ($iconclass) {
                $data['iconclass'] = $iconclass;
                $data['iconlabel'] = self::icon_options()[$record->iconkey] ?? get_string('customicon', 'format_picturecards');
            } else {
                $data['mediatype'] = self::TYPE_DEFAULT;
            }
        }

        return $data;
    }

    /**
     * Get the first custom image file URL for a module.
     *
     * @param int $cmid course module id
     * @param int|null $courseid optional known course to batch display lookups
     * @return moodle_url|null
     */
    public static function get_image_url(int $cmid, ?int $courseid = null): ?moodle_url {
        if ($courseid !== null) {
            $file = self::course_image_files($courseid)[$cmid] ?? null;
        } else {
            $context = context_module::instance($cmid);
            $files = get_file_storage()->get_area_files(
                $context->id,
                'format_picturecards',
                'activityimage',
                0,
                'timemodified DESC, id DESC',
                false
            );
            $file = $files ? reset($files) : null;
        }
        if (!$file || ($courseid === null && !self::is_safe_image($file))) {
            return null;
        }
        return moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            'format_picturecards',
            'activityimage',
            0,
            '/',
            $file->get_filename()
        );
    }

    /**
     * Whether a stored file is an allowed raster image.
     * @param \stored_file $file image
     * @return bool
     */
    public static function is_safe_image(\stored_file $file): bool {
        $extension = strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION));
        $mimetypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (
            !in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true) ||
                !in_array($file->get_mimetype(), $mimetypes, true)
        ) {
            return false;
        }
        $info = $file->get_imageinfo();
        return $info && $info['mimetype'] === $file->get_mimetype() && in_array($info['mimetype'], $mimetypes, true);
    }

    /**
     * Normalize media type value.
     *
     * @param string $mediatype input media type
     * @return string
     */
    public static function normalise_media_type(string $mediatype): string {
        $allowed = [self::TYPE_DEFAULT, self::TYPE_ICON, self::TYPE_IMAGE];
        if (!in_array($mediatype, $allowed, true)) {
            return self::TYPE_DEFAULT;
        }
        return $mediatype;
    }

    /**
     * Normalize icon key value.
     *
     * @param string $iconkey input icon key
     * @return string
     */
    public static function normalise_icon_key(string $iconkey): string {
        $options = self::icon_options();
        return array_key_exists($iconkey, $options) ? $iconkey : '';
    }

    /**
     * Available, contrast-tested icon background colours.
     * @return array colour identifiers and translated names
     */
    public static function colour_options(): array {
        $options = [];
        foreach (['blue', 'teal', 'plum', 'orange', 'violet', 'slate', 'brown', 'charcoal'] as $colour) {
            $options[$colour] = get_string('colour_' . $colour, 'format_picturecards');
        }
        return $options;
    }

    /**
     * Allow only fixed colour names and bounded integer focal coordinates.
     * @param array $appearance raw settings
     * @return array safe values
     */
    public static function normalise_appearance(array $appearance): array {
        $colour = $appearance['colour'] ?? 'blue';
        return [
            'colour' => array_key_exists($colour, self::colour_options()) ? $colour : 'blue',
            'focusx' => max(0, min(100, (int)($appearance['focusx'] ?? 50))),
            'focusy' => max(0, min(100, (int)($appearance['focusy'] ?? 50))),
        ];
    }
}
