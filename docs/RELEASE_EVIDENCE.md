# Release evidence — PictureCards 0.1.0-rc1

**Not release ready.** PictureCards is a new Moodle component derived from the unpublished Subtiles prototype. The component rename changes its database table, file areas, course options, backup identifiers and CSS/JavaScript names; the renamed plugin and any local data migration must be tested independently before any compatibility or release claim.

## Current status

| Check | Result |
|---|---|
| Original Subtiles prototype on Moodle 5.3, PHP 8.3 and MySQL 8.4 | Its earlier 27-test suite and 4 new focused tests passed. This does not verify the renamed component. |
| Original Subtiles prototype on Moodle 4.5, PHP 8.1 and MySQL 8.4 | After correcting missing visibility strings and a version-specific test API, the complete suite passed: 31 tests, 124 assertions. This does not verify the renamed component. |
| Renamed PictureCards on Moodle 5.3+ build 20261021, PHP 8.3.36 and MySQL 8.4.3 | Initial 31-test suite passed, 124 assertions. Further checks after subsequent changes are still required. |
| Renamed PictureCards on Moodle 5.3+ build 20261021, PHP 8.3.36 and PostgreSQL 17.2 | PHPUnit: 31 tests, 124 assertions passed with a non-default prefix. The disposable test site's core OpenSSL key-generation warning was traced to the local PHP environment: a standalone key generation failed without `OPENSSL_CONF` and passed when set to the PHP distribution's `extras/ssl/openssl.cnf`. The test run itself was not repeated with that environment correction. |
| Renamed PictureCards on Moodle 4.5.15+ build 20261021, PHP 8.1.33 and MySQL 8.4.3 | PHPUnit: 31 tests, 124 assertions passed. Test checkout temporarily used Moodle 4.5 minimum metadata; release metadata is unchanged. |
| Renamed PictureCards on Moodle 5.0.11 build 20261005, PHP 8.3.36 and MySQL 8.4.3 | PHPUnit: 31 tests, 124 assertions passed with temporary test-only minimum metadata. |
| Renamed PictureCards on Moodle 5.1.8+ and 5.2.4+ build 20261021, PHP 8.3.36 and MySQL 8.4.3 | PHPUnit: 31 tests, 124 assertions passed on each branch with temporary test-only minimum metadata. |
| Local prototype migration | Two isolated migration tests passed (20 assertions). Three local courses were migrated after a database backup; their media record counts, image content hashes and owners, and site palette settings were checked against the originals. |
| Moodle 5.3 disposable-site uninstall and reinstall, PHP 8.3 / MySQL 8.4, `sf_` prefix | Passed for the source checkout. Before uninstall the site had one PictureCards media record and one picture; uninstall removed its table and files, preserved an unrelated Subtiles course and file, and reinstall recreated the table. A picture-backed topic and activity icon rendered successfully after reinstall. The exact release ZIP remains untested. |
| Built-in activity palette contrast | Calculated white icon contrast after the plugin's darkening rule: minimum ratios Ocean 4.93:1, Forest 4.98:1, Sunset 4.45:1, Slate 4.41:1 and Bright 5.44:1. This calculation does not replace keyboard, screen-reader or visual accessibility testing. |
| Release ZIP, other PHP and database combinations, Behat and accessibility | Not yet verified. |
| Repository and tracker | [Public repository and issue tracker](https://github.com/genestial/moodle-format_picturecards) created; private vulnerability reporting enabled. |
| Marketplace listing, additional support contacts and cleared screenshots | Not yet prepared. |

The initial GitHub CI runs found language-string ordering, coverage annotations and Mustache HTML validation issues. A later run passed these checks and the JavaScript and CSS linters, then reported that the packaged JavaScript differed from Moodle's generated files. The assets have since been rebuilt with Moodle's toolchain; hosted checks must be repeated.

Hosted run [40937748851](https://github.com/genestial/moodle-format_picturecards/actions/runs/40937748851) passed PHPUnit with warnings treated as failures on 27 Moodle 4.5–5.2/PHP/database combinations before its browser scenarios failed. The generated assets from its Moodle 5.3 job were retrieved using the artifact checksum and committed; the browser fixtures were corrected after inspecting the failure output. These results do not establish that the changed candidate or the full matrix has passed. The downloaded build artifact SHA-256 was `c52833a905e1263a4fd9f5cb61f35848cf44de6caa85ac1094122265b1365b31`.

The next hosted browser run exposed a real activity-editing failure: calling the activity form's `set_data()` a second time re-ran its preprocessing with incomplete defaults. The callback now sets only the PictureCards QuickForm defaults. Hosted run [40939865174](https://github.com/genestial/moodle-format_picturecards/actions/runs/40939865174) passed PHPUnit and frontend checks in all 39 configured Moodle/PHP/database combinations; its browser runs still failed a scenario looking for an eyebrow as link text. Representative logs show the three other scenarios passing. The browser selector has been corrected and the full run must be repeated.

Moodle's [release schedule](https://moodledev.io/general/releases) shows that Moodle 5.0 security support ended on 5 October 2026. It remains a requested compatibility target, but site administrators should use a maintained Moodle version. The current Marketplace submission pages could not be retrieved via web extraction on 29 October 2026; the 24 October policy research baseline remains provisional until the live pages can be reviewed.

Hosted run [40956834033](https://github.com/genestial/moodle-format_picturecards/actions/runs/40956834033) passed all 39 Moodle/PHP/database jobs, including PHPUnit and four browser scenarios per job, with compatibility metadata adjusted only inside each test checkout. The candidate's actual minimum and supported metadata have now been updated to Moodle 4.5–5.3; a fresh run with the actual metadata and added palette browser scenarios remains necessary. No Marketplace submission has been made.
