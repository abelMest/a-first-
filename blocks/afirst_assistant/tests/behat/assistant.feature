@block @block_afirst_assistant
Feature: Course assistant answers only from approved sources
  In order to trust the assistant
  As a learner
  I need it to refuse questions the approved course content does not cover, and to let me report answers

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | student1 | Student   | One      | student1@example.com |
    And the following "courses" exist:
      | fullname          | shortname |
      | Data Science Test | DS1       |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | DS1    | editingteacher |
      | student1 | DS1    | student        |
    And the following "core_ai > ai providers" exist:
      | provider          | name            | enabled | apikey | orgid |
      | aiprovider_openai | OpenAI provider | 1       | 123    | abc   |
    And the following "blocks" exist:
      | blockname        | contextlevel | reference | pagetypepattern | defaultregion |
      | afirst_assistant | Course       | DS1       | course-view-*   | side-pre      |

  @javascript
  Scenario: Learner accepts the AI policy, is refused when nothing is approved, and reports the refusal
    Given I am on the "DS1" "Course" page logged in as "student1"
    And I should see "Course assistant"
    When I click on "Accept and continue" "button" in the "Course assistant" "block"
    And I set the field "Your question" to "What are the dimensions of data quality?"
    And I click on "Ask" "button" in the "Course assistant" "block"
    Then I should see "I can only answer from the approved course material" in the "Course assistant" "block"
    And I click on "Report a problem with this answer" "button" in the "Course assistant" "block"
    And I set the field "Reason" to "Incomplete"
    And I click on "Send for review" "button" in the "Course assistant" "block"
    And I should see "A reviewer will check this answer within 48 hours" in the "Course assistant" "block"
    And I log out
    And I am on the "DS1" "Course" page logged in as "teacher1"
    And I click on "Review queue" "link" in the "Course assistant" "block"
    And I should see "Incomplete"
    And I should see "What are the dimensions of data quality?"

  @javascript @_file_upload
  Scenario: Teacher uploads a source and approves it
    Given I am on the "DS1" "Course" page logged in as "teacher1"
    When I click on "Approved sources" "link" in the "Course assistant" "block"
    Then I should see "No sources yet"
    And I set the field "Title shown to learners" to "Module 1.2 Data Governance"
    And I upload "blocks/afirst_assistant/tests/fixtures/module12.txt" file to "File (.txt, .md or .html)" filemanager
    And I press "Add a source document"
    And I should see "Pending approval"
    And I press "Approve"
    And I should see "Approved"
