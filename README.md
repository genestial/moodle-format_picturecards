# PictureCards course format

PictureCards (`format_picturecards`) displays topics with image banners and activity cards. Editing teachers can choose each topic's picture independently, use Moodle's activity icons, select built-in icons, or upload an image per activity. Original course format; not a fork of Tiles.

Copyright © 2026 GENESTIAL. Free software under GNU GPL v3 or later; see [LICENSE](LICENSE).

## Compatibility

The current release candidate targets Moodle **5.3**. Support for Moodle 4.5 through 5.2 is being developed and verified. **0.1.0-rc1** is unreleased: consult [release evidence](docs/RELEASE_EVIDENCE.md) for actual test coverage. No extra plugins, paid services, API keys, external fonts or administrator build steps. Browser use only; Moodle App support is not claimed. Boost is the initial test theme.

## Install

1. Back up the site and test on staging.
2. Install the ZIP through Site administration → Plugins → Install plugins, or extract its `picturecards/` directory into `{moodleroot}/public/course/format/picturecards`.
3. Visit Site administration → Notifications, or run `php admin/cli/upgrade.php` from the Moodle root above `public/`.
4. Choose **PictureCards format** in a course's settings.

No Composer/npm commands are required on the receiving site.

## Activity media

The course page displays a banner using the course title and **Course settings → Course image**. In the course format settings, enable **Show course description in banner** to include the course description. The General section follows the banner; its activities appear as compact pills. Tiles shows topics as picture cards; open a topic card to see its activity tiles. List shows full sections and their activities. Editing mode keeps full sections visible for Moodle's course editor. In a one-section-per-page course, previous and next topic links appear below the activity cards and skip topics the learner cannot open.

### Topic pictures

Site administration → Plugins → Course formats → PictureCards format sets the site default palette, including optional custom colours. Courses inherit that palette by default; course format settings can override it with Ocean, Forest, Sunset, Slate, Bright or five custom colours, with a live swatch preview. Palette colours are used for topics without pictures and for default activity icon backgrounds; lighter colours are darkened for white icon contrast. Uploaded pictures and custom activity styles take precedence. The initial site default is Ocean.

Open the course, switch **Edit mode** on, then click **Topic appearance** in the upper-right corner of the topic banner. In the **Topic picture** file manager, add one picture and click **Save changes**. You can open any topic on its own page to see the same control. To replace it, open **Topic appearance** again and replace the file. To remove it, remove the file in the manager and save; the banner returns to its plain background. A topic picture is independent of its activities' images.

Topic pictures accept one JPG/JPEG, PNG, GIF or WebP image, subject to the effective site/course/user/plugin size limit. The first activity picture is never used as a topic banner. The image is decorative, with the visible topic title providing the label. Only users with course editing permission can make changes. Students and unenrolled users cannot access pictures belonging to hidden or inaccessible topics.

### Activity tiles

Switch **Edit mode** on and choose **Tile appearance** on an activity card, or edit the activity and expand **Activity card media**. Choose default icon, custom icon (Discussion, Reading, Video, Lab, Goal, Path, Idea or Default), or uploaded image. The tile editor also offers eight background colours for custom icons and horizontal/vertical crop focus for images. Save and return to the course. Images are decorative; the activity name provides the accessible label. Missing images/invalid icons fall back to the default icon.

Upload one JPG/JPEG, PNG, GIF or WebP. SVG is excluded; previously uploaded SVG files are no longer displayed/served. Replace them with raster images. Switching to default/built-in icon mode removes the stored image. Removing a file in the file manager removes its saved copy on save; cancelling preserves saved media.

Site administration → Plugins → Course formats → PictureCards format provides an optional maximum image size in bytes. `0` uses Moodle's ordinary limits; positive values further restrict site/course/user limits. Changes require `moodle/course:manageactivities` and standard activity-edit authorization/session-key checks.

## Data and lifecycle

`{format_picturecards_media}` stores media type, icon key, colour, crop focus and modification time against each activity, without a user identifier. Activity images use File API component `format_picturecards`, area `activityimage`, item ID `0` in module contexts. Topic pictures use File API area `topicimage` in the course context, with the section ID as item ID. Metadata can identify uploader, author, source and upload times; images may contain personal information. Nothing is transmitted externally.

The Privacy API discovers uploaders and exports/deletes their files in approved contexts. Other uploaders' files and shared icon settings remain intact. Core handles drafts, logs and course preferences through its own providers. Missing images fall back to default icons.

Activity/section/course deletion removes plugin records and files via observers and core context cleanup. Uninstall removes plugin images, table and configuration. Changing course formats preserves media for switching back. Make backups while using PictureCards; other formats do not promise to preserve dormant PictureCards data. Duplication/import/restore remap media and topic pictures to new activity and section IDs.

## Development and release

Use a public repository named `moodle-format_picturecards`, with this directory's contents at its root. CI targets `MOODLE_503_STABLE`, PHP 8.3/8.4, PostgreSQL 17, MariaDB 11.4 and MySQL 8.4. Configured jobs are not evidence that every combination has run.

Run tests only against isolated database prefixes and file stores. In a Moodle test checkout with development dependencies installed:

```sh
php public/admin/tool/phpunit/cli/init.php --disable-composer
vendor/bin/phpunit --testsuite format_picturecards_testsuite
```

See [reviewer tests](docs/REVIEWER_TESTS.md), [release evidence](docs/RELEASE_EVIDENCE.md), [changelog](CHANGES.md) and [listing draft](docs/MARKETPLACE.md). Add public source/issue-tracker URLs and support/security contacts before submission. This candidate has not been submitted, accepted or published by Moodle.
