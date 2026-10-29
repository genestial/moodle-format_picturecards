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

namespace format_picturecards\privacy;


use core_privacy\local\metadata\collection;
use core_privacy\local\metadata\provider as metadata_provider;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\core_userlist_provider;
use core_privacy\local\request\plugin\provider as plugin_provider;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for format_picturecards.
 *
 * @package    format_picturecards
 * @copyright  2026 GENESTIAL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements core_userlist_provider, metadata_provider, plugin_provider {
    /**
     * Describe uploaded images and their File API metadata.
     * @param collection $collection metadata collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        return $collection->add_subsystem_link('core_files', [], 'privacy:metadata:files');
    }

    /**
     * Find course and module contexts with pictures uploaded by a user.
     * @param int $userid user identifier
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $list = new contextlist();
        $list->add_from_sql('SELECT DISTINCT c.id FROM {context} c JOIN {files} f ON f.contextid = c.id
             WHERE ((c.contextlevel = :modulelevel AND f.filearea = :activityarea)
                    OR (c.contextlevel = :courselevel AND f.filearea = :topicarea))
               AND f.component = :component
               AND f.userid = :userid AND f.filename <> :directory', [
            'modulelevel' => CONTEXT_MODULE, 'activityarea' => 'activityimage',
            'courselevel' => CONTEXT_COURSE, 'topicarea' => 'topicimage', 'component' => 'format_picturecards',
            'userid' => $userid, 'directory' => '.',
        ]);
        return $list;
    }

    /**
     * Identify uploaders in this course or module context.
     * @param userlist $userlist users to populate
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        $area = self::filearea($context);
        if ($area === null) {
            return;
        }
        $userlist->add_from_sql('userid', 'SELECT DISTINCT userid FROM {files}
            WHERE contextid = :contextid AND component = :component AND filearea = :area
              AND filename <> :directory AND userid > 0', [
            'contextid' => $context->id, 'component' => 'format_picturecards', 'area' => $area, 'directory' => '.',
        ]);
    }

    /**
     * Export only the approved user's images and upload metadata.
     * @param approved_contextlist $contextlist approved contexts
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        foreach ($contextlist->get_contexts() as $context) {
            if (self::filearea($context) === null) {
                continue;
            }
            foreach (self::files($context) as $file) {
                if ((int)$file->get_userid() !== (int)$contextlist->get_user()->id) {
                    continue;
                }
                $path = $context->contextlevel === CONTEXT_COURSE ?
                    [get_string('topicpicture', 'format_picturecards'), (string)$file->get_itemid()] :
                    [get_string('activityimage', 'format_picturecards')];
                writer::with_context($context)->export_file($path, $file);
                writer::with_context($context)->export_data($path, (object)[
                    'filename' => $file->get_filename(),
                    'author' => $file->get_author(),
                    'source' => $file->get_source(),
                    'license' => $file->get_license(),
                    'timecreated' => $file->get_timecreated(),
                    'timemodified' => $file->get_timemodified(),
                ]);
            }
        }
    }

    /**
     * Remove all uploaded personal content in an approved course or module context.
     * Shared icon settings contain no user identifier and remain intact.
     * @param \context $context approved context
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        $area = self::filearea($context);
        if ($area !== null) {
            get_file_storage()->delete_area_files($context->id, 'format_picturecards', $area);
            if ($context->contextlevel === CONTEXT_COURSE) {
                \format_picturecards\local\topic_image::reset_cache((int)$context->instanceid);
            }
        }
    }

    /**
     * Delete the approved user's images, preserving other uploaders' files.
     * @param approved_contextlist $contextlist approved contexts
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        foreach ($contextlist->get_contexts() as $context) {
            self::delete_files($context, [(int)$contextlist->get_user()->id]);
        }
    }

    /**
     * Delete approved users' images in one context.
     * @param approved_userlist $userlist approved users
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        self::delete_files($userlist->get_context(), array_map('intval', $userlist->get_userids()));
    }

    /**
     * Return plugin image files, never files from other components.
     * @param \context $context course or module context
     * @return \stored_file[]
     */
    private static function files(\context $context): array {
        $area = self::filearea($context);
        return $area === null ? [] : get_file_storage()->get_area_files(
            $context->id,
            'format_picturecards',
            $area,
            false,
            'id',
            false
        );
    }

    /**
     * Remove selected uploaders' files.
     * @param \context $context approved context
     * @param int[] $userids approved users
     */
    private static function delete_files(\context $context, array $userids): void {
        if (self::filearea($context) === null) {
            return;
        }
        foreach (self::files($context) as $file) {
            if (in_array((int)$file->get_userid(), $userids, true)) {
                $file->delete();
                if ($context->contextlevel === CONTEXT_COURSE) {
                    \format_picturecards\local\topic_image::reset_cache((int)$context->instanceid);
                }
            }
        }
    }

    /**
     * Pick the owned File API area for an approved context.
     * @param \context $context context
     * @return string|null file area
     */
    private static function filearea(\context $context): ?string {
        return match ($context->contextlevel) {
            CONTEXT_MODULE => 'activityimage',
            CONTEXT_COURSE => 'topicimage',
            default => null,
        };
    }
}
