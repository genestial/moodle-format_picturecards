# Release evidence — PictureCards 0.1.0-rc1

**Not release ready.** PictureCards is a new Moodle component derived from the unpublished Subtiles prototype. The component rename changes its database table, file areas, course options, backup identifiers and CSS/JavaScript names; the renamed plugin and any local data migration must be tested independently before any compatibility or release claim.

## Current status

| Check | Result |
|---|---|
| Original Subtiles prototype on Moodle 5.3, PHP 8.3 and MySQL 8.4 | Its earlier 27-test suite and 4 new focused tests passed. This does not verify the renamed component. |
| Original Subtiles prototype on Moodle 4.5, PHP 8.1 and MySQL 8.4 | After correcting missing visibility strings and a version-specific test API, the complete suite passed: 31 tests, 124 assertions. This does not verify the renamed component. |
| Renamed PictureCards on Moodle 5.3+ build 20261021, PHP 8.3.36 and MySQL 8.4.3 | Initial 31-test suite passed, 124 assertions. Further checks after subsequent changes are still required. |
| PictureCards migration and release ZIP | Not yet verified. |
| Moodle 5.0–5.2, other PHP and database combinations, Behat and accessibility | Not yet verified. Isolated test environments and a proposed CI matrix are in preparation. |
| Repository and tracker | [Public repository and issue tracker](https://github.com/genestial/moodle-format_picturecards) created; private vulnerability reporting enabled. |
| Marketplace listing, additional support contacts and cleared screenshots | Not yet prepared. |

The initial GitHub CI run found language-string ordering and coverage annotation issues. Those have been corrected in the development working copy; a fresh hosted run is required to verify the fixes and expose later check results.

The proposed CI matrix changes minimum-version metadata only inside the CI test checkout; the release metadata remains limited to Moodle 5.3 until the renamed plugin has been verified. No Marketplace submission has been made.
