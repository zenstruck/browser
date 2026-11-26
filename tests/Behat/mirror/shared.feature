Feature: Shared browser behavior

    Scenario: Read the session written by the application
        Given I am on "/page1?start-session=1"
        When I go to "/read-session"
        Then the response should contain "value"

    Scenario: Exceptions are caught by default
        Given I am on "/exception"
        Then the response status code should be 500

    Scenario: Read the content of an exception page
        Given I am on "/exception"
        Then the response should contain "exception thrown"

    Scenario: Assert the current path
        Given I am on "/page1?foo=bar"
        Then I should be on "/page1"
        And I should not be on "/page2"

    Scenario: Redirects are followed by default
        Given I am on "/redirect1"
        Then I should be on "/page1"
        And I should see "h1 title"

    Scenario: Assert response headers
        Given I am on "/page1"
        Then the "Content-Type" response header should equal "text/html; charset=UTF-8"
        And the "Content-Type" response header should contain "text/html"

    Scenario: Assert the response status
        Given I am on "/page1"
        Then the response status code should be 200
        And the response should be successful

    Scenario: Log in through a form
        Given I am on "/login"
        When I fill in "Username" with "kevin"
        And I fill in "Password" with "pass"
        And I check "Remember me"
        And I press "Log in"
        Then I should be on "/page1"
        When I go to "/user"
        Then I should see "user: kevin/pass"

    Scenario: Assert the response content
        Given I am on "/page1"
        Then the response should contain "h1 title"
        And the response should not contain "invalid text"

    Scenario: Read a plain text response
        Given I am on "/text"
        Then the response should contain "text content"

    Scenario: Assert rendered HTML
        Given I am on "/page1"
        Then I should see "h1 title"
        And I should not see "invalid text"
        And I should see "title" in the "h1" element
        And I should not see "invalid text" in the "h1" element
        And I should see a "h1" element
        And I should not see a "h2" element
        And I should see 2 "ul li" elements

    Scenario: Assert HTML metadata
        Given I am on "/page1"
        Then I should see "meta title" in the "title" element
        And the "meta[name='description']" element's "content" attribute should contain "meta"
        And the "meta[name='description']" element's "content" attribute should not contain "invalid"
        And the "html" element's "lang" attribute should contain "en"

    Scenario Outline: Assert form fields by <locator>
        Given I am on "/page1"
        Then the "<text>" field should contain "input 1"
        And the "<text>" field should not contain "invalid"
        And the "<checked>" checkbox should be checked
        And the "<unchecked>" checkbox should be unchecked
        And "option 1" should be selected in "<select>"
        And "option 2" should not be selected in "<select>"
        And "option 1" should be selected in "<multiple>"
        And "option 3" should be selected in "<multiple>"
        And "option 2" should not be selected in "<multiple>"

        Examples:
            | locator | text    | checked | unchecked | select  | multiple  |
            | label   | Input 1 | Input 3 | Input 2   | Input 4 | Input 7   |
            | id      | input1  | input3  | input2    | input4  | input7    |
            | name    | input_1 | input_3 | input_2   | input_4 | input_7[] |

    Scenario: Assert selected radio buttons
        Given I am on "/page1"
        Then the "Radio 1" checkbox should not be checked
        And the "Radio 2" checkbox should be checked
        And the "Radio 3" checkbox should not be checked
        And "option 2" should be selected in "input_8"
        And "option 1" should not be selected in "input_8"

    Scenario: Follow a link by text
        Given I am on "/page1"
        When I follow "a link"
        Then I should be on "/page2"

    Scenario: Click a link by CSS selector
        Given I am on "/page1"
        When I click the "#link a" element
        Then I should be on "/page2"

    Scenario Outline: Submit fields located by <locator>
        Given I am on "/page1"
        When I fill in "<text>" with "Kevin"
        And I check "<check>"
        And I uncheck "<uncheck>"
        And I select "option 2" from "<select>"
        And I attach the file "attachment.txt" to "<file>"
        And I select "option 1" from "<multiple>"
        And I additionally select "option 3" from "<multiple>"
        And I clear the "<clear>" selection
        And I select "option 3" from "<radio>"
        And I press "Submit"
        Then I should be on "/submit-form"
        And the response should contain "\"input_1\":\"Kevin\""
        And the response should contain "\"input_2\":\"on\""
        And the response should not contain "input_3"
        And the response should contain "\"input_4\":\"option 2\""
        And the response should contain "\"input_5\":\"attachment.txt\""
        And the response should contain "\"input_6\":[\"option 1\",\"option 3\"]"
        And the response should not contain "input_7"
        And the response should contain "\"input_8\":\"option 3\""

        Examples:
            | locator | text    | check   | uncheck | select  | file    | multiple  | clear     | radio   |
            | label   | Input 1 | Input 2 | Input 3 | Input 4 | Input 5 | Input 6   | Input 7   | Radio 3 |
            | id      | input1  | input2  | input3  | input4  | input5  | input6    | input7    | radio3  |
            | name    | input_1 | input_2 | input_3 | input_4 | input_5 | input_6[] | input_7[] | input_8 |

    Scenario Outline: Submit a form with <button>
        Given I am on "/page1"
        When I press "<button>"
        Then I should be on "/submit-form"
        And the response should contain "\"<name>\":\"<value>\""
        And the response should not contain "<absent>"

        Examples:
            | button   | name     | value | absent   |
            | Submit   | submit_1 | a     | submit_2 |
            | Submit B | submit_1 | b     | submit_2 |
            | Submit C | submit_2 | c     | submit_1 |
            | Submit D | submit_2 | d     | submit_1 |

    Scenario Outline: Submit a filled form with <button>
        Given I am on "/page1"
        When I fill in "input_1" with "Kevin"
        And I press "<button>"
        Then I should be on "/submit-form"
        And the response should contain "\"<name>\":\"<value>\""
        And the response should not contain "<absent>"
        And the response should contain "\"input_1\":\"Kevin\""

        Examples:
            | button   | name     | value | absent   |
            | Submit   | submit_1 | a     | submit_2 |
            | Submit B | submit_1 | b     | submit_2 |
            | Submit C | submit_2 | c     | submit_1 |
            | Submit D | submit_2 | d     | submit_1 |

    @pending
    Scenario Outline: <case>
        Then this scenario is not yet implemented because "<reason>"

        Examples:
            | case                                                                                     | reason                                                                                     |
            | Can write to the session before making a request                                         | steps for modifying the Symfony session are missing                                        |
            | Session written by the test is visible to the application and back                       | steps for modifying the Symfony session are missing                                        |
            | Using the session keeps the existing session cookie                                      | steps for modifying the Symfony session are missing                                        |
            | Fails if trying to manipulate exception page                                             | steps for changing exception handling and asserting failed actions are missing             |
            | Can enable exception throwing                                                            | steps for changing exception handling and asserting failed actions are missing             |
            | Can re enable catching exceptions                                                        | steps for changing exception handling and asserting failed actions are missing             |
            | Fails if expected exception not thrown                                                   | steps for changing exception handling and asserting failed actions are missing             |
            | Can expect exception for form submit                                                     | steps for changing exception handling and asserting failed actions are missing             |
            | Can expect exception for link click                                                      | steps for changing exception handling and asserting failed actions are missing             |
            | Fails if acting after an expected exception                                              | steps for changing exception handling and asserting failed actions are missing             |
            | Fails if reading the response when the request threw                                     | steps for changing exception handling and asserting failed actions are missing             |
            | Multiple browsers                                                                        | steps for switching named sessions are missing                                             |
            | Assert on encoded                                                                        | the Mink address assertion ignores the query string                                        |
            | Can manipulate cookies                                                                   | steps for setting, inspecting and expiring cookie attributes are missing                   |
            | Following redirect follows all by default                                                | steps for controlling redirects and inspecting intermediate responses are missing          |
            | Can re enable following redirects                                                        | steps for controlling redirects and inspecting intermediate responses are missing          |
            | Calling follow redirects when the response is a redirect follows the redirect            | steps for controlling redirects and inspecting intermediate responses are missing          |
            | Calling follow redirects before a request has been made just enables following redirects | steps for controlling redirects and inspecting intermediate responses are missing          |
            | Can limit redirects followed                                                             | steps for controlling redirects and inspecting intermediate responses are missing          |
            | Assert redirected to follows all redirects by default                                    | steps for controlling redirects and inspecting intermediate responses are missing          |
            | Assert redirected to can configure number of redirects to follow                         | steps for controlling redirects and inspecting intermediate responses are missing          |
            | Exception thrown if asserting redirected and not intercepting redirects                  | steps for controlling redirects and inspecting intermediate responses are missing          |
            | Exception thrown if asserting redirected to and not intercepting redirects               | steps for controlling redirects and inspecting intermediate responses are missing          |
            | Click and intercept                                                                      | steps for controlling redirects and inspecting intermediate responses are missing          |
            | Assert host query and header absence                                                     | steps for URL components and missing response headers are missing                          |
            | Can act as user                                                                          | steps for setting or inspecting Symfony authentication and remember-me cookies are missing |
            | Can make authentication assertions                                                       | steps for setting or inspecting Symfony authentication and remember-me cookies are missing |
            | Can check if not authenticated after request                                             | steps for setting or inspecting Symfony authentication and remember-me cookies are missing |
            | Can login with a form and be remembered                                                  | steps for setting or inspecting Symfony authentication and remember-me cookies are missing |
            | Can enable the profiler                                                                  | steps for enabling and inspecting Symfony profiles are missing                             |
            | Can profile multiple requests                                                            | steps for enabling and inspecting Symfony profiles are missing                             |
            | Can access the profiler                                                                  | steps for enabling and inspecting Symfony profiles are missing                             |
            | Can use data collector                                                                   | steps for enabling and inspecting Symfony profiles are missing                             |
            | Can dump response                                                                        | there is no step to assert the output of a response dump                                   |
            | Can save source                                                                          | steps for saving and inspecting response artifacts are missing                             |
            | Can save source as zip                                                                   | steps for saving and inspecting response artifacts are missing                             |
            | Cannot attach file that does not exist                                                   | steps for multiple uploads or asserting failed uploads are missing                         |
            | Can attach multiple files                                                                | steps for multiple uploads or asserting failed uploads are missing                         |
            | Cannot attach multiple files to a non multiple input                                     | steps for multiple uploads or asserting failed uploads are missing                         |
            | Can dump html element                                                                    | steps for dumping selected elements and inspecting the output are missing                  |
            | If dump selector matches multiple elements all are dumped                                | steps for dumping selected elements and inspecting the output are missing                  |

    @skip
    Scenario Outline: <case>
        The PHPUnit case stays in PHPUnit because <reason>.

        Examples:
            | case                                                           | reason                                                              |
            | Reading the session before making a request does not start one | it inspects the PHP session and cookie jar before a browser request |
            | Can use current browser                                        | it checks PHP callbacks, component hooks or injected objects        |
            | Can use components                                             | it checks PHP callbacks, component hooks or injected objects        |
            | Component pre assertions and actions are called                | it checks PHP callbacks, component hooks or injected objects        |
            | Can use crawler                                                | it checks PHP callbacks, component hooks or injected objects        |
            | Can use cookie jar                                             | it checks PHP callbacks, component hooks or injected objects        |
            | With can accept multiple browsers and components               | it checks PHP callbacks, component hooks or injected objects        |
            | Invalid use callback parameter throws type error               | it checks PHP callbacks, component hooks or injected objects        |
            | Can use container as typehint                                  | it checks PHP callbacks, component hooks or injected objects        |
            | Extension saves the browser state when a test fails            | it exercises the PHPUnit extension lifecycle                        |
            | Can access the html crawler                                    | it checks the returned PHP crawler object                           |
