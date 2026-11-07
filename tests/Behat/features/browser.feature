Feature: Use Browser in Behat contexts
    Scenario: Use KernelBrowser by default
        Given I am on "/behat"
        Then the "kernel" browser is used
        And I should see "Static greeting"
        And I should not see "Storage: empty"

    Scenario: Share the browser with custom Symfony contexts
        Given I open "/behat" from my own context
        Then I should see "Static greeting"
        When I fill in "Message" with "Hello"
        Then the "Message" field should contain "Hello"
        And the response status code should be 200
        When I press "Send"
        Then I should see "success"

    @javascript
    Scenario: Switch to PlaywrightBrowser
        Given I open "/behat" from my own context
        Then the "playwright" browser is used
        And I should see "Storage: empty"
        When I follow "Next page"
        Then I should see "success"

    Scenario: Switch back to KernelBrowser
        Given I am on "/behat"
        Then the "kernel" browser is used
        When I follow "Next page"
        Then I should see "success"
