@tiny @tiny_muai
Feature: Basic tests for Muai

  @javascript
  Scenario: Plugin tiny_muai appears in the list of installed additional plugins
    Given I log in as "admin"
    When I navigate to "Plugins > Plugins overview" in site administration
    And I follow "Additional plugins"
    Then I should see "Massey University artificial intelligence"
    And I should see "tiny_muai"
