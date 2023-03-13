@redirect
Feature: Redirect
  In order to find the correct page at all times
  As a customer
  I need to be redirected to the new page if a page changes url

  Background:
    Given the store operates on a channel named "United States"
    And the store operates on another channel named "Denmark"

  Scenario: Redirect from old path to new path
    Given I change my current channel to "United States"
    And the store has a redirect from path "/old-path" to "/new-path"
    When I try to access "/old-path"
    Then I should be redirected "/new-path"

  Scenario: Redirect from old path to new path on specific channel
    Given I change my current channel to "United States"
    And the store has a redirect from path "/old-path" to "/new-path" on channel "United States"
    When I try to access "/old-path"
    Then I should be redirected "/new-path"

  Scenario: Do not redirect from old path to new path if I am not on correct channel
    Given I change my current channel to "Denmark"
    And the store has a redirect from path "/old-path" to "/new-path" on channel "United States"
    When I try to access "/old-path"
    Then I should still be on "/old-path"

  Scenario: Redirect from old path to new path and keep query string
    Given I change my current channel to "United States"
    And the store has a redirect from path "/old-path" to "/new-path"
    When I try to access "/old-path?q=ts"
    Then I should be redirected "/new-path?q=ts"
