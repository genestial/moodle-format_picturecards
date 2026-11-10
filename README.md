# PictureCards course format

PictureCards (`format_picturecards`) is a free topic-based course format. The course page shows a banner, topic cards, and activity cards. Editing teachers choose a picture or a built-in icon for each topic, and Moodle's icon, a built-in icon, or an uploaded image for each activity. Original course format; not a fork of Tiles.

**Maintainer:** GENESTIAL  
**Email:** admin@genestial.com  
**Website:** https://www.genestial.com  

Copyright © 2026 GENESTIAL. Free software under GNU GPL v3 or later; see [LICENSE](LICENSE). There is no paid tier, premium upgrade, or provider-operated service.

Project documentation and support: https://github.com/genestial/moodle-format_picturecards. Plugin source is distributed in the installable ZIP. GENESTIAL maintains this plugin. Report bugs and feature requests through the [issue tracker](https://github.com/genestial/moodle-format_picturecards/issues). For suspected security vulnerabilities, follow the private reporting instructions in [SECURITY.md](SECURITY.md).

## Compatibility

PictureCards 1.0.0 supports Moodle **4.5 through 5.3**. Moodle 5.0 no longer receives security updates; use a maintained Moodle version where possible. No extra plugins, paid services, API keys, external fonts, or administrator build steps. Browser use only; Moodle App support is not claimed. Boost is the tested theme.

## Install

1. Back up the site and test on staging.
2. Install the ZIP through Site administration → Plugins → Install plugins, or extract its `picturecards/` directory into the site's course format directory: `{moodleroot}/course/format/` on Moodle 4.5–5.0, or `{moodleroot}/public/course/format/` on Moodle 5.1–5.3.
3. Visit Site administration → Notifications, or run `php admin/cli/upgrade.php` from the Moodle root.
4. Choose **PictureCards format** in a course's settings.

No Composer or npm commands are required on the receiving site.

## Using the format

The course page displays a banner using the course title and **Course settings → Course image**. In the course format settings, enable **Show course description in banner** to include the course description, or disable **Use custom course header** to use the theme's normal course heading. Enable **Hide General when empty** to hide General when it has no summary content or activities; it remains available in editing mode. The General section follows the banner; its activities appear as compact pills. Tiles shows topics as cards; open a topic card to see its activity tiles. List shows full sections and their activities. Editing mode keeps full sections visible for Moodle's course editor. In a one-section-per-page course, previous and next topic links appear below the activity cards and skip topics the learner cannot open.

### Palettes

Site administration → Plugins → Course formats → PictureCards format sets the site default palette, including optional custom colours. Courses inherit that palette by default. Course format settings can override it with Ocean, Forest, Sunset, Slate, Bright, or five custom colours, with a live swatch preview. Palette colours are used for topics that are not showing a picture and for default activity icon backgrounds. Lighter colours are darkened for white icon contrast. Uploaded pictures and custom activity styles take precedence. The initial site default is Ocean.

### Topic appearance

Open the course, switch **Edit mode** on, then click **Topic appearance** in the upper-right corner of the topic banner. The same control is available on a topic's own page.

- **Use uploaded image.** Add one JPG, JPEG, PNG, GIF, or WebP picture in **Topic picture** and save. To replace it, choose another file. To remove it, remove the file in the manager and save. The banner then uses the palette background and the default topic icon.
- **Use custom icon.** Choose one of Bookmark, Chapter, Compass, Cube, Default, Discussion, Folder, Goal, Idea, Lab, Layers, List, Open folder, Path, Reading, or Video. The icon is shown on the topic card and the single-topic banner. The picker shows each glyph beside its name.

Switching from a picture to an icon keeps the saved picture, so choosing **Use uploaded image** again restores it. A topic picture is independent of its activities' images. The first activity picture is never used as a topic banner. The image and icon are decorative; the visible topic title provides the label. Only users who can edit the course can make changes. Students and unenrolled users cannot access pictures belonging to hidden or inaccessible topics.

Pictures are subject to the effective site, course, user, and plugin size limit.

### Activity tiles

Switch **Edit mode** on and choose **Tile appearance** on an activity card, or edit the activity and expand **Activity card media**.

- **Use default activity icon.** Moodle's activity icon, centred on the topic palette colour.
- **Use custom icon.** One of the sixteen icons listed above, drawn in white on one of eight background colours: blue, teal, plum, orange, violet, slate, brown, or charcoal. The picker shows each glyph beside its name.
- **Use uploaded image.** One JPG, JPEG, PNG, GIF, or WebP. In **Tile appearance**, click the preview or enter exact horizontal and vertical percentages directly beneath it. Horizontal runs from 0 (left) to 100 (right); vertical runs from 0 (top) to 100 (bottom). Use 50 to centre either direction.

Images are decorative; the activity name provides the accessible label. A missing image or an unrecognised icon falls back to the default icon. SVG is excluded; previously uploaded SVG files are no longer displayed or served. Replace them with raster images.

Switching away from image mode keeps the stored image. Removing the file in the file manager and saving removes that saved copy. Cancelling the form preserves the previously saved media.

Site administration → Plugins → Course formats → PictureCards format provides an optional maximum image size in bytes. `0` uses Moodle's ordinary limits; a positive value further restricts the site, course, and user limits. Changes require `moodle/course:manageactivities` and Moodle's activity-edit authorisation and session-key checks.

## Data and lifecycle

`{format_picturecards_media}` stores media type, icon key, colour, crop focus, and modification time against each activity, without a user identifier. Activity images use File API component `format_picturecards`, area `activityimage`, item ID `0` in module contexts. Topic pictures use File API area `topicimage` in the course context, with the section ID as item ID. Topic icon choices are course format options `topicmediatype` and `topiciconkey`; they do not store a user identifier. File metadata can identify the uploader, author, source, and upload time. Images may contain personal information. Nothing is transmitted externally.

The Privacy API discovers uploaders and exports and deletes their files in approved contexts. Other uploaders' files and shared icon, colour, and topic settings remain intact. Core handles drafts, logs, and course preferences through its own providers. Missing images fall back to default icons.

Activity and section deletion removes plugin records and files through event observers. Course content deletion removes orphaned media rows even after switching formats; core context cleanup removes the files. Uninstall removes plugin images, the table, and configuration. Changing course formats preserves media for switching back. Make backups while using PictureCards; other formats do not promise to preserve dormant PictureCards data. Duplication, import, and restore remap media and topic pictures to new activity and section IDs, including topic icon choices.

## Development

Run tests only against isolated database prefixes and file stores. In a Moodle test checkout with development dependencies installed:

On Moodle 4.5–5.0:

```sh
php admin/tool/phpunit/cli/init.php --disable-composer
php vendor/bin/phpunit --testsuite format_picturecards_testsuite
```

On Moodle 5.1–5.2:

```sh
php public/admin/tool/phpunit/cli/init.php --disable-composer
php vendor/bin/phpunit --testsuite format_picturecards_testsuite
```

On Moodle 5.3:

```sh
php public/admin/tool/phpunit/cli/util.php --install
php public/admin/tool/phpunit/cli/util.php --run --testsuite format_picturecards_testsuite
```

See [reviewer tests](docs/REVIEWER_TESTS.md) and the [changelog](CHANGES.md).
