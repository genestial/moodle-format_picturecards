@format @format_picturecards @javascript
Feature: Administrators preview and customise the site palette
  In order to choose colours for courses using the site default
  As an administrator
  I can preview a preset and turn it into a custom palette

  Scenario: A preset can be customised and remains custom after saving
    Given I log in as "admin"
    And I navigate to "Plugins > Course formats > PictureCards format" in site administration
    When I set the field "Default colour palette" to "Bright"
    Then the field "Site custom colour 1" matches value "#5147a8"
    And the field "Site custom colour 2" matches value "#008d91"
    And "[data-picturecards-palette-preview] span" "css_element" should exist
    When I set the field "Site custom colour 1" to "#123456"
    Then the field "Default colour palette" matches value "Custom"
    And I press "Save changes"
    And the field "Default colour palette" matches value "Custom"
    And the field "Site custom colour 1" matches value "#123456"

  Scenario: A course can override the site palette with customised colours
    Given the following "courses" exist:
      | fullname            | shortname | format       |
      | Palette test course | PAL       | picturecards |
    And I am on the "PAL" "course editing" page logged in as "admin"
    And I expand all fieldsets
    When I set the field "Colour palette" to "Forest"
    Then the field "Custom colour 1" matches value "#244b3d"
    And "[data-picturecards-palette-preview] span" "css_element" should exist
    When I set the field "Custom colour 1" to "#123456"
    Then the field "Colour palette" matches value "Custom"
    And I press "Save and display"
    And I am on the "PAL" "course editing" page
    And I expand all fieldsets
    And the field "Colour palette" matches value "Custom"
    And the field "Custom colour 1" matches value "#123456"
