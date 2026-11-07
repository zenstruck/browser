Feature: Turbo Drive, Frames and Streams

    Scenario: Turbo is loaded
        Given I am on "/nav"
        Then I should see "turbo active" in the "#turbo-status" element

    Scenario: Visits keep permanent elements
        Given I am on "/nav"
        When I fill in "Permanent" with "kept"
        And I follow "advance link"
        Then the "Permanent" field should contain "kept"
        And I should be on "/nav/target"

    Scenario: Can follow a Turbo method link
        Given I am on "/nav"
        When I follow "delete link"
        Then I should see "deleted=1" in the "#query" element
        And I should be on "/nav/target"

    Scenario: Can accept a Turbo confirmation
        Given I am on "/nav"
        And I accept the next confirmation
        When I follow "confirm link"
        Then I should see "navigation target"
        And I should be on "/nav/target"
        And the confirmation should say "Are you sure?"

    Scenario: Link inside a frame only updates the frame
        Given I am on "/nav/frames"
        When I follow "frame link"
        Then I should see "frame1 content loaded" in the "#frame1" element
        And I should be on "/nav/frames"
        And I should see "outside frame"
        And I should not see "outside of the frames"

    Scenario: Link outside a frame can target it
        Given I am on "/nav/frames"
        When I follow "outside link targeting frame"
        Then I should see "frame1 content loaded" in the "#frame1" element
        And I should be on "/nav/frames"

    Scenario: Link inside a frame can target the whole page
        Given I am on "/nav/frames"
        When I follow "top link"
        Then I should see "navigation target"
        And I should be on "/nav/target"

    Scenario: Advancing frame updates the URL
        Given I am on "/nav/frames"
        When I follow "advancing frame link"
        Then I should see "advance-frame content loaded" in the "#advance-frame" element
        And I should be on "/nav/frames/content"
        And I should see "outside frame"

    Scenario: Frame missing from the response shows an error
        Given I am on "/nav/frames"
        When I follow "missing frame link"
        Then I should see "Content missing" in the "#missing-frame" element
        And I should be on "/nav/frames"

    Scenario: Can submit a form inside a frame
        Given I am on "/nav/frames"
        When I fill in "Frame Title" with "framed"
        And I press "save in frame"
        Then I should see "form-frame content loaded framed" in the "#form-frame" element
        And I should be on "/nav/frames"

    Scenario: Eager frame loads its source
        Given I am on "/nav/frames"
        Then I should see "eager-frame content loaded" in the "#eager-frame" element

    Scenario: Lazy frame loads its source when scrolled into view
        Given I am on "/nav/frames"
        Then I should see "lazy frame loading" in the "#lazy-frame" element
        When I click the "#lazy-frame" element
        Then I should see "lazy-frame content loaded" in the "#lazy-frame" element

    Scenario Outline: Form response can stream into the page
        Given I am on "/nav/streams"
        When I press "<button>"
        Then I should see "<text>" in the "<selector>" element
        And I should be on "/nav/streams"

        Examples:
            | button         | selector               | text            |
            | append stream  | #items li:last-child   | appended item   |
            | prepend stream | #items li:first-child  | prepended item  |
            | replace stream | #item-1                | replaced item   |
            | update stream  | #message               | updated message |
            | before stream  | #items li:first-child  | before item     |
            | after stream   | #items li:nth-child(2) | after item      |

    Scenario: Form response can stream a removal
        Given I am on "/nav/streams"
        Then I should see an "#item-2" element
        When I press "remove stream"
        Then I should be on "/nav/streams"
        And I should not see an "#item-2" element

    Scenario: Form response can stream a page refresh
        Given I am on "/nav/streams"
        Then I should see "refreshes: 0" in the "#refreshes" element
        When I press "refresh stream"
        Then I should see "refreshes: 1" in the "#refreshes" element
        And I should be on "/nav/streams"

    Scenario: Stream element added to the page is applied
        Given I am on "/nav/streams"
        When I press "inline stream"
        Then I should see "inline message" in the "#message" element
