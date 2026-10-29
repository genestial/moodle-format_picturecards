@format @format_picturecards @javascript
Feature: Teachers customise activity cards
  In order to distinguish activities visually
  As an editing teacher
  I can choose icons and raster images without changing learner access

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher1 | Teacher   | One      | teacher@example.com |
      | student1 | Student   | One      | student@example.com |
    And the following "courses" exist:
      | fullname       | shortname | format   | numsections |
      | PictureCards demo  | SUB       | picturecards | 2           |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | SUB    | editingteacher |
      | student1 | SUB    | student        |
    And the following "activities" exist:
      | activity | course | name          | section | idnumber | content       | contentformat |
      | page     | SUB    | Reading notes | 1       | reading  | Reading notes | 1             |

  Scenario: Custom icons persist and remain present after AJAX editing
    Given I am on the "Reading notes" "page activity editing" page logged in as teacher1
    And I expand all fieldsets
    When I set the following fields to these values:
      | Media type | Use custom icon |
      | Custom icon | Reading        |
    And I press "Save and return to course"
    Then ".format-picturecards-customicon .fa-book-open" "css_element" should exist
    And I turn editing mode on
    When I set the field "Edit title" in the "Reading notes" "activity" to "Updated reading notes"
    Then ".format-picturecards-customicon .fa-book-open" "css_element" should exist
    And I am on "PictureCards demo" course homepage
    And I should see "Updated reading notes"
    And ".format-picturecards-customicon .fa-book-open" "css_element" should exist
    And I log out
    And I am on the "PictureCards demo" course page logged in as student1
    And ".format-picturecards-topic-card" "css_element" should exist
    When I click on "Topic 1" "link" in the ".format-picturecards-topic-card" "css_element"
    And ".format-picturecards-customicon .fa-book-open" "css_element" should exist

  Scenario: Upload and replace an image, then return to a default icon
    Given I am on the "Reading notes" "page activity editing" page logged in as teacher1
    And I expand all fieldsets
    When I set the field "Media type" to "Use uploaded image"
    And I upload "course/format/picturecards/tests/fixtures/badge.png" file to "Activity image" filemanager
    And I press "Save and return to course"
    Then ".format-picturecards-image" "css_element" should exist
    When I am on the "Reading notes" "page activity editing" page
    And I expand all fieldsets
    And I set the field "Media type" to "Use default activity icon"
    And I press "Save and return to course"
    Then ".format-picturecards-image" "css_element" should not exist
    And ".format-picturecards-media .activityicon" "css_element" should exist

  Scenario: Choose a picture for a topic independently of activity cards
    Given I am on the "PictureCards demo" course page logged in as teacher1
    And I turn editing mode on
    When I click on "Topic appearance" "button" in the "li[data-for='section'][data-number='1']" "css_element"
    And I upload "course/format/picturecards/tests/fixtures/badge.png" file to "Topic picture" filemanager
    And I press "Save changes"
    Then ".format-picturecards-topic-cover img" "css_element" should exist
    When I am on "PictureCards demo" course homepage
    Then ".format-picturecards-topic-cover img" "css_element" should exist
    And I log out
    And I am on the "PictureCards demo" course page logged in as student1
    And ".format-picturecards-topic-cover img" "css_element" should exist
    And "[data-picturecards-topic-appearance]" "css_element" should not exist

  Scenario: One layout control changes the whole course
    Given I am on the "PictureCards demo" course page logged in as teacher1
    Then ".format-picturecards-course-toolbar [data-picturecards-layout='list']" "css_element" should exist
    And ".format-picturecards-toolbar [data-picturecards-layout]" "css_element" should not exist
    And ".format-picturecards-topic-grid" "css_element" should exist
    When I click on "List" "button" in the ".format-picturecards-course-toolbar" "css_element"
    Then ".course-content.format-picturecards-list" "css_element" should exist
    And ".format-picturecards-course-body.format-picturecards-list" "css_element" should exist
    And ".format-picturecards-course-toolbar [data-picturecards-layout='list'][aria-pressed='true']" "css_element" should exist
