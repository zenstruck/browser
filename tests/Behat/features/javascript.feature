@javascript
Feature: Use PlaywrightBrowser for an entire feature
    Scenario Outline: Wait for JavaScript and isolate browser storage for <message>
        Given I am on "/behat"
        Then the "playwright" browser is used
        And I should see "Storage: empty"
        When I fill in "Message" with "<message>"
        Then the "Message" field should contain "<message>"
        And the response status code should be 200
        When I press "Update"
        Then I should see "<message>"
        When I press "Clear"
        Then I should not see an "#output" element
        When I press "Store"
        Then I should see "Storage: saved"
        And waiting for missing content times out

        Examples:
            | message       |
            | First update  |
            | Second update |
