Feature: zenstruck/browser inside a Behat scenario
  In order to write acceptance tests with the Behat BDD framework
  As a Symfony developer
  I need the zenstruck/browser fluent API available from a Behat context

  Scenario: Visit a simple page
    When I visit "/page2"
    Then the response should be successful
    And I should see "success"

  Scenario: Visit a text response
    When I visit "/text"
    Then the response status code should be 200
    And the response body should contain "text content"
