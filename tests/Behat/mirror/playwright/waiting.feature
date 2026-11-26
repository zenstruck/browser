Feature: Playwright waits for asynchronous changes

    Scenario: Assertions wait for elements to appear
        Given I am on "/nav"
        And the page starts with "" and then runs:
            """
            document.body.insertAdjacentHTML('beforeend', '<button id=late-button>late button</button>');
            """
        Then I should see a "#late-button" element
        And I should see "late button"
        And I should see 1 "#late-button" element

    Scenario: Negative assertions wait for elements to disappear
        Given I am on "/nav"
        And the page starts with "<p id='doomed'>Contents of doomed box</p>" and then runs:
            """
            document.getElementById('doomed').remove();
            """
        Then I should not see a "#doomed" element
        And I should not see "Contents of doomed box"

    Scenario: Scoped text appears
        Given I am on "/nav"
        And the page starts with "<p id='changing'>waiting</p>" and then runs:
            """
            document.getElementById('changing').textContent = 'Ready';
            """
        Then I should see "ready" in the "#changing" element

    Scenario: Page text appears
        Given I am on "/nav"
        And the page starts with "" and then runs:
            """
            document.body.insertAdjacentHTML('beforeend', '<p>Ready now</p>');
            """
        Then I should see "ready now"

    Scenario: Scoped text disappears
        Given I am on "/nav"
        And the page starts with "<p id='changing'>waiting</p>" and then runs:
            """
            document.getElementById('changing').textContent = 'ready';
            """
        Then I should not see "waiting" in the "#changing" element

    Scenario: Page text disappears
        Given I am on "/nav"
        And the page starts with "<p id='changing'>Contents of doomed box</p>" and then runs:
            """
            document.getElementById('changing').remove();
            """
        Then I should not see "Contents of doomed box"

    Scenario: Element count changes
        Given I am on "/nav"
        And the page starts with "<p class='counted'>first</p>" and then runs:
            """
            document.body.insertAdjacentHTML('beforeend', '<p class=counted>second</p>');
            """
        Then I should see 2 ".counted" elements

    Scenario: Attribute changes
        Given I am on "/nav"
        And the page starts with "<p id='changing' data-state='waiting'></p>" and then runs:
            """
            document.getElementById('changing').dataset.state = 'READY';
            """
        Then the "#changing" element's "data-state" attribute should contain "ready"

    Scenario: Field value changes
        Given I am on "/nav"
        And the page starts with "<input id='changing' value='waiting'>" and then runs:
            """
            document.getElementById('changing').value = 'READY';
            """
        Then the "changing" field should contain "ready"

    Scenario: Field value stops matching
        Given I am on "/nav"
        And the page starts with "<input id='changing' value='waiting'>" and then runs:
            """
            document.getElementById('changing').value = 'ready';
            """
        Then the "changing" field should not contain "waiting"

    Scenario: Selection changes
        Given I am on "/nav"
        And the page starts with "<select id='changing'><option>waiting</option><option>ready</option></select>" and then runs:
            """
            document.getElementById('changing').value = 'ready';
            """
        Then "ready" should be selected in "changing"

    Scenario: Checkbox changes
        Given I am on "/nav"
        And the page starts with "<input id='changing' type='checkbox'>" and then runs:
            """
            document.getElementById('changing').checked = true;
            """
        Then the "changing" checkbox should be checked

    Scenario: Element becomes visible
        Given I am on "/nav"
        And the page starts with "<p id='changing' hidden>ready</p>" and then runs:
            """
            document.getElementById('changing').hidden = false;
            """
        Then the "#changing" element should be visible

    Scenario: Element becomes hidden
        Given I am on "/nav"
        And the page starts with "<p id='changing'>ready</p>" and then runs:
            """
            document.getElementById('changing').hidden = true;
            """
        Then the "#changing" element should not be visible

    Scenario: The URL changes
        Given I am on "/nav"
        And the page starts with "" and then runs:
            """
            history.pushState({}, '', '/changed');
            """
        Then I should be on "/changed"

    Scenario: The URL stops matching
        Given I am on "/nav"
        And the page starts with "" and then runs:
            """
            history.pushState({}, '', '/changed');
            """
        Then I should not be on "/nav"

    @pending
    Scenario Outline: <case>
        Then this scenario is not yet implemented because "<reason>"

        Examples:
            | case                                                     | reason                                                                             |
            | Actions wait for elements that are not there yet         | Mink action steps do not wait for element lookup                                   |
            | Clicks wait for a link added later                       | Mink action steps do not wait for element lookup                                   |
            | Delayed encoded URL changes                              | Mink address steps ignore query strings                                            |
            | Negative assertions that already hold return immediately | steps for configuring timeouts and measuring or asserting failed waits are missing |
            | The timeout can be changed at runtime                    | steps for configuring timeouts and measuring or asserting failed waits are missing |
            | The auto wait timeout is configurable                    | steps for configuring timeouts and measuring or asserting failed waits are missing |
            | An expired wait preserves the assertion message          | steps for configuring timeouts and measuring or asserting failed waits are missing |
            | The timeout cannot be zero or negative                   | steps for configuring timeouts and measuring or asserting failed waits are missing |
            | The actionability wait uses the configured timeout       | steps for configuring timeouts and measuring or asserting failed waits are missing |
            | Full page navigation keeps its own timeout               | steps for configuring timeouts and measuring or asserting failed waits are missing |
