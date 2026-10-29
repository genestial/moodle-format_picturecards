# Reviewer and manual acceptance tests

Use a dedicated test course. Use disposable databases/file storage for uninstall, upgrades and privacy deletion. Never initialise PHPUnit/Behat against ordinary site data.

1. Install the release ZIP on each advertised Moodle branch with developer debugging. Verify no errors and that PictureCards is listed.
2. Create a two-section course, editing teacher, student and unenrolled user. Add Page, Forum and File activities. Check icons, index, section names, completion and access.
3. In edit mode, choose **Topic appearance** in a topic banner, upload a picture, save and check the banner on both course and single-topic views. Replace and remove it, and check that the banner updates immediately. Confirm the topic picture is independent of activity pictures.
   With edit mode off, verify that Tiles shows picture-backed topic cards and that each card opens the matching section page. Switch to List and check that all topic sections and activities reappear with one course-wide layout control. Verify hidden/restricted topics do not gain links for students.
   Set the course image, name and description, then check the course banner and its optional description. Rename General, add summary text and three activities, and check their compact pills. Check the previous/next links on a single-topic page.
4. Select every activity icon; save/reload/reopen the form. Rename, move and duplicate using AJAX. Verify media immediately and after reload.
5. Upload/replace/remove activity images and cancel a replacement. Switch image mode to default/icon. Reject oversized/multiple files, SVG and scripts renamed `.png` without altering saved media.
6. Test editing/direct activity and topic image access as student, unenrolled and logged-out users. Cover hidden activities/sections and availability restrictions. Tamper with contexts, item IDs and paths; test invalid session keys through the standard form.
7. Switch to Topics and back. Duplicate/import activities and restore backups with/without user data. Verify new identifiers/contexts and intact source data. Back up in PictureCards format.
8. Discover/export/delete data for two uploaders via individual, bulk and context requests. Preserve unrelated users, activities and components.
9. Delete activities synchronously/asynchronously and courses, including after changing format. Check orphans. Test upgrades from any supported released PictureCards version. Uninstall/reinstall only on disposable sites.
10. Check Boost with keyboard, focus, screen reader, 200% zoom, narrow viewport, RTL and long names. Images are decorative; activity names provide accessible labels. Do not infer App support.
11. Compare queries on 100 activities. Verify one media-record and topic-file load per course/request; record remaining per-image File API work and responsiveness.
12. Test site and course palette presets, inheritance, custom colours, the live previews and saved values. Check custom activity images and icon colours continue to take precedence. Test topic label visibility and custom wording.

Record actual versions, browser/theme, expected/actual results and screenshots. This checklist alone is not execution evidence.
