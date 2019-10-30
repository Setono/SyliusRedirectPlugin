@managing_redirects
Feature: Adding a new redirect
  In order to redirect users from an old path to a new path
  As an Administrator
  I want to add a new redirect to the shop

  Background:
    Given the store operates on a single channel in "United States"
    And I am logged in as an administrator

  @ui
  Scenario: Adding a new redirect
    Given I want to create a new redirect
    When I set its source to "/source"
    And I set its destination to "/destination"
    And I add it
    Then I should be notified that it has been successfully created
    And the redirect with source "/source" and destination "/destination" should appear in the store
