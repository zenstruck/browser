Feature: Playwright browser interactions

    Scenario: Wait for an element to become visible and hidden
        Given I am on "/javascript"
        Then the "#timeout-box" element should be visible
        And the "#show-box" element should not be visible
        When I press "show"
        Then the "#show-box" element should be visible
        And the "#hide-box" element should be visible
        When I press "hide"
        Then the "#hide-box" element should not be visible
        When I press "toggle"
        Then the "#toggle-box" element should not be visible
        When I press "toggle"
        Then the "#toggle-box" element should be visible

    Scenario: Wait for text to appear and disappear
        Given I am on "/javascript"
        When I fill in "input" with "some text"
        And I press "submit"
        Then I should see "some text" in the "#output" element
        When I press "clear"
        Then I should not see "some text" in the "#output" element

    Scenario: Assert visibility of present, hidden and missing elements
        Given I am on "/javascript"
        Then the "#hide-box" element should be visible
        And the "#show-box" element should not be visible
        And the "#invalid-element" element should not be visible

    Scenario Outline: Use mouse gestures on an element
        Given I am on "/page1"
        When I click the "<selector>" element
        Then I should be on "/page1"
        When I <gesture> the "<selector>" element
        Then I should be on "/page2"

        Examples:
            | gesture      | selector             |
            | double-click | #double-click-link a |
            | right-click  | #context-menu-link a |

    @pending
    Scenario Outline: <case>
        Then this scenario is not yet implemented because "<reason>"

        Examples:
            | case                                    | reason                                                           |
            | Can take screenshot                     | steps for saving and inspecting Playwright artifacts are missing |
            | Can save console log                    | steps for saving and inspecting Playwright artifacts are missing |
            | Can dump console log with console error | steps for saving and inspecting Playwright artifacts are missing |
            | Can wait                                | there is no fixed-delay step                                     |
            | Cannot follow invisible link            | steps for asserting an action failed are missing                 |

    @skip
    Scenario Outline: <case>
        The PHPUnit case stays in PHPUnit because <reason>.

        Examples:
            | case                                                           | reason                                                                 |
            | Can use playwright browser as typehint                         | it checks PHP callbacks or browser object identity                     |
            | A kernel exception does not prevent reusing the shared browser | it checks PHP callbacks or browser object identity                     |
            | Extension saves a screenshot and console log when a test fails | it exercises the PHPUnit extension lifecycle                           |
            | Can dump console log with throw error                          | the Playwright transport does not expose the uncaught page error event |
