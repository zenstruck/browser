Feature: BrowserKit responses and requests

    Scenario: Handle a plain text response
        Given I am on "/text"
        Then the response should be successful
        And the response status code should be 200
        And the "Content-Type" response header should contain "text/plain"
        And the response should contain "text content"

    Scenario Outline: Read a <type> response
        Given I am on "<path>"
        Then the response should be successful
        And the "Content-Type" response header should contain "<type>"

        Examples:
            | path   | type |
            | /json  | json |
            | /xml   | xml  |
            | /page1 | html |
            | /zip   | zip  |

    @pending
    Scenario Outline: <case>
        Then this scenario is not yet implemented because "<reason>"

        Examples:
            | case                                                    | reason                                                                         |
            | Session started by the test is available on any host    | steps for session setup and cookie scope are missing                           |
            | Reboots the kernel between requests by default          | steps for controlling kernel reboots and observing their effect are missing    |
            | Can disable reboot                                      | steps for controlling kernel reboots and observing their effect are missing    |
            | Can re enable reboot                                    | steps for controlling kernel reboots and observing their effect are missing    |
            | Http method actions                                     | steps for raw requests, HTTP options and expected request failures are missing |
            | Can set default http options                            | steps for raw requests, HTTP options and expected request failures are missing |
            | Default http options are not mutated by request options | steps for raw requests, HTTP options and expected request failures are missing |
            | Can expect exception for http request                   | steps for raw requests, HTTP options and expected request failures are missing |
            | Can assert json matches                                 | steps for posting JSON and comparing JMESPath results are missing              |
            | Can dump empty json request                             | steps for inspecting structured dump output are missing                        |
            | Can dump json response as array                         | steps for inspecting structured dump output are missing                        |
            | Dump includes headers and status                        | steps for inspecting structured dump output are missing                        |
            | Can dump json array key                                 | steps for inspecting structured dump output are missing                        |
            | Can dump json path expression                           | steps for inspecting structured dump output are missing                        |
            | Can dump xml selector                                   | steps for inspecting structured dump output are missing                        |
            | Can save formatted json source                          | steps for saving and inspecting response artifacts are missing                 |
            | Can save source when exception                          | steps for saving and inspecting response artifacts are missing                 |

    @skip
    Scenario Outline: <case>
        The PHPUnit case stays in PHPUnit because <reason>.

        Examples:
            | case                                       | reason                                          |
            | Can use kernel browser as typehint         | it checks PHP objects and callback injection    |
            | Can access json object                     | it checks PHP objects and callback injection    |
            | Can use json object                        | it checks PHP objects and callback injection    |
            | Can access the xml crawler                 | it checks PHP objects and callback injection    |
            | Calling browser ensures kernel is shutdown | it exercises the PHPUnit test case or extension |
            | Can use native web test case assertions    | it exercises the PHPUnit test case or extension |
            | Browser has been configured                | it exercises the PHPUnit test case or extension |
            | Cannot create browser                      | it exercises the PHPUnit test case or extension |

    @skip
    Scenario Outline: <class> stays in PHPUnit
        This unit test class stays in PHPUnit because <reason>.

        Examples:
            | class             | reason                                                                |
            | HttpOptionsTest   | it exercises the PHP HTTP options builder                             |
            | JsonTest          | it exercises the PHP JSON selector and assertion helpers directly     |
            | NormalizationTest | it exercises the PHP artifact filename normalizer                     |
            | SessionTest       | it exercises the PHP exception-page detector with synthetic responses |
            | CookieJarTest     | it exercises unsupported methods on the PHP cookie adapter            |
