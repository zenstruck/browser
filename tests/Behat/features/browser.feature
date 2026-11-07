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
        When I additionally select "admin" from "Roles"
        Then "member" should be selected in "Roles"
        And "admin" should be selected in "Roles"
        When I clear the "Roles" selection
        Then "admin" should not be selected in "Roles"
        When I press "Send"
        Then I should not be on "/behat"
        And the response should be successful
        And the "Content-Type" response header should equal "application/json"
        And the response should contain "Hello"
        And the response should not contain "roles"

    @javascript
    Scenario: Switch to PlaywrightBrowser
        Given I open "/behat" with Playwright from my own context
        Then the "playwright" browser is used
        And I should see "Storage: empty"
        And the "Content-Type" response header should contain "html"
        And "member" should be selected in "Roles"
        When I clear the "Roles" selection
        Then "member" should not be selected in "Roles"
        And the "#output" element's "id" attribute should not contain "missing"
        When I fill in "Message" with "Hello"
        And I click the "#update" element
        Then the "#output" element's "class" attribute should contain "updated"
        And the "#output" element should be visible
        When I press "Clear"
        Then the "#output" element should not be visible
        When I double-click the "#storage" element
        Then I should see "Double-clicked"
        When I right-click the "#storage" element
        Then I should see "Right-clicked"
        When I follow "Next page"
        Then I should see "success"

    Scenario: Switch back to KernelBrowser
        Given I am on "/behat"
        Then the "kernel" browser is used
        When I click the "a[href='/page2']" element
        Then I should see "success"
