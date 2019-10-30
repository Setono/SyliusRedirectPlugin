@redirect
Feature: Redirect
  In order to find the correct page at all times
  As a customer
  I need to be redirected to the new page if a page changes url

  Background:
    Given the store operates on a single channel in "United States"

  Scenario: Redirect from old path to new path
    Given the store has a redirect from path "/old-path" to "/new-path"
    When I try to access this old path
    Then I should be redirected to the new path
