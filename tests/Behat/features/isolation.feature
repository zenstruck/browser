Feature: Isolate application sessions
    Scenario Outline: Start with a clean application session for <run>
        Given I am on "/read-session"
        Then I should see "(none)"
        And the browser has no test cookie
        And the "<browser>" browser is used
        When I set a test cookie
        And I go to "/page1?start-session=1"
        And I go to "/read-session"
        Then I should see "value"

        Examples:
            | browser | run |
            | kernel  | 1   |
            | kernel  | 2   |

        @javascript
        Examples:
            | browser    | run |
            | playwright | 1   |
            | playwright | 2   |
