@tiny @tiny_muai @tiny_muai_testcases @javascript
Feature: Manage tiny_muai test cases
  In order to iterate on AI prompt configuration without changing site-wide settings
  As an administrator
  I need to create, edit, and delete tiny_muai test cases

  Background:
    Given I log in as "admin"

  Scenario: A user without the capability cannot access the test cases page
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | Reg       | Ular     | user1@example.com |
    And I log out
    And I log in as "user1"
    When I visit "/lib/editor/tiny/plugins/muai/testcases/index.php"
    Then I should see "Access denied"

  Scenario: An empty test cases list shows the New test case button
    When I navigate to "Plugins > Text editors > TinyMCE editor > Test cases" in site administration
    Then I should see "Tiny muai test cases"
    And I should see "New test case"

  Scenario: Creating a test case persists it and returns to the list
    Given I navigate to "Plugins > Text editors > TinyMCE editor > Test cases" in site administration
    When I press "New test case"
    And I set the following fields to these values:
      | Test case name  | Sample case                 |
      | Context id      | 1                           |
      | Page            | page-course-editsection     |
      | Editor context  | id_summary_editor           |
      | Editor content  | A short paragraph to review |
    And I press "Save changes"
    Then I should see "Sample case"

  Scenario: Test case names must be globally unique
    Given I navigate to "Plugins > Text editors > TinyMCE editor > Test cases" in site administration
    And I press "New test case"
    And I set the following fields to these values:
      | Test case name  | Duplicate                  |
      | Context id      | 1                          |
      | Page            | page-course-editsection    |
      | Editor context  | id_summary_editor          |
      | Editor content  | first body                 |
    And I press "Save changes"
    When I press "New test case"
    And I set the following fields to these values:
      | Test case name  | Duplicate                  |
      | Context id      | 1                          |
      | Page            | page-course-editsection    |
      | Editor context  | id_summary_editor          |
      | Editor content  | second body                |
    And I press "Save changes"
    Then I should see "A test case with this name already exists"

  Scenario: An existing test case can be edited via the name link
    Given I navigate to "Plugins > Text editors > TinyMCE editor > Test cases" in site administration
    And I press "New test case"
    And I set the following fields to these values:
      | Test case name  | Editable                   |
      | Context id      | 1                          |
      | Page            | page-course-editsection    |
      | Editor context  | id_summary_editor          |
      | Editor content  | original body              |
    And I press "Save changes"
    When I follow "Editable"
    And I set the field "Editor content" to "updated body"
    And I press "Save changes"
    And I follow "Editable"
    Then the field "Editor content" matches value "updated body"

    Scenario: Deleting a test case from the list removes the row
    Given I navigate to "Plugins > Text editors > TinyMCE editor > Test cases" in site administration
    And I press "New test case"
    And I set the following fields to these values:
      | Test case name  | Disposable                 |
      | Context id      | 1                          |
      | Page            | page-course-editsection    |
      | Editor context  | id_summary_editor          |
      | Editor content  | trash content              |
    And I press "Save changes"
    When I click on "Delete" "link" in the "Disposable" "table_row"
    And I click on "Delete" "button" in the "Confirmation" "dialogue"
    Then I should not see "Disposable"
