Feature: Navigate with and without Turbo

    Scenario: Can follow a link
        Given I am on "/nav"
        When I follow "advance link"
        Then I should see "navigation target"
        And I should be on "/nav/target"

    Scenario: Can follow a replace link
        Given I am on "/nav"
        When I follow "replace link"
        Then I should see "navigation target"
        And I should be on "/nav/target"

    Scenario: Can follow a link with a fragment
        Given I am on "/nav"
        When I follow "fragment link"
        Then I should see "navigation target"
        And I should be on "/nav/target#section"

    Scenario: Can follow an anchor link
        Given I am on "/nav"
        When I follow "anchor link"
        Then I should see "navigation index"
        And I should be on "/nav#local"

    Scenario: Can follow a redirecting link
        Given I am on "/nav"
        When I follow "redirect link"
        Then I should see "redirected=1" in the "#query" element
        And I should be on "/nav/target"

    Scenario: Can follow a link that opts out of Turbo
        Given I am on "/nav"
        When I follow "opt out link"
        Then I should see "navigation target"
        And I should be on "/nav/target"

    Scenario: Following a download link stays on the page
        Given I am on "/nav"
        When I follow "download link"
        Then I should see "navigation index"
        And I should be on "/nav"

    Scenario: Can follow a link to a page that requires a full reload
        Given I am on "/nav"
        When I follow "reload link"
        Then I should see "reloaded page"
        And I should be on "/nav/reload"

    Scenario: Can submit a GET form
        Given I am on "/nav"
        When I fill in "Search" with "turbo"
        And I press "search"
        Then I should see "results for: turbo" in the "#results" element
        And I should be on "/nav/search"

    Scenario: Can submit a POST form that redirects
        Given I am on "/nav"
        When I fill in "Title" with "my-title"
        And I press "create"
        Then I should see "created=my-title" in the "#query" element
        And I should be on "/nav/target"

    Scenario: Can submit a POST form that fails validation
        Given I am on "/nav"
        When I press "create"
        Then I should see "title is required" in the "#error" element

    Scenario: Can submit a form that redirects back to the same page
        Given I am on "/nav/morph"
        Then I should see "name: (none)" in the "#name" element
        When I fill in "Name" with "kevin"
        And I press "save name"
        Then I should see "name: kevin" in the "#name" element
        And I should be on "/nav/morph"
