@chrome @en.wikipedia.beta.wmflabs.org @firefox @localhost @vagrant
Feature: Basic features for no-js functionality

  Background:
    Given I am using a nojs browser

  Scenario: Clicking alerts badge goes to Special:Notifications
    Given I am logged in
    When I click the alert badge
    And I wait for the page to load
    Then I am on Special Notifications page
