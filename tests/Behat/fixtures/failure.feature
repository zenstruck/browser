@javascript
Feature: Clean up after failed scenarios
    Scenario: Fail with browser state
        Given I am on "/behat"
        When I press "Store"
        Then I should see "This text does not exist"

    Scenario: Start clean after the failure
        Given I am on "/behat"
        Then I should see "Storage: empty"
