@managing_redirects
Feature: Editing a redirect
  In order to change redirect details
  As an Administrator
  I want to be able to edit a redirect

  Background:
    Given the store has a redirect from path "/source" to "/destination"
    And I am logged in as an administrator

  @ui
  Scenario: Changing the source of a redirect
    Given I want to modify the redirect with source "/source"
    When I update the source to "/real-source"
    And I save my changes
    Then I should be notified that it has been successfully edited
    And this redirects source should be "/real-source"
