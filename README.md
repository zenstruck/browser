# zenstruck/browser

[![CI Status](https://github.com/zenstruck/browser/workflows/CI/badge.svg)](https://github.com/zenstruck/browser/actions?query=workflow%3ACI)
[![Code Coverage](https://codecov.io/gh/zenstruck/browser/branch/1.x/graph/badge.svg?token=R7OHYYGPKM)](https://codecov.io/gh/zenstruck/browser)

Functional testing with Symfony can be verbose. This library provides an expressive,
auto-completable, fluent wrapper around Symfony's native functional testing features:

```php
public function testViewPostAndAddComment()
{
    // assumes a "Post" is in the database with an id of 3

    $this->browser()
        ->visit('/posts/3')
        ->assertSuccessful()
        ->assertSeeIn('title', 'My First Post')
        ->assertSeeIn('h1', 'My First Post')
        ->assertNotSeeElement('#comments')
        ->fillField('Comment', 'My First Comment')
        ->click('Submit')
        ->assertOn('/posts/3')
        ->assertSeeIn('#comments', 'My First Comment')
    ;
}
```

Combine this library with [zenstruck/foundry](https://github.com/zenstruck/foundry)
to make your tests even more succinct and expressive:

```php
public function testViewPostAndAddComment()
{
    $post = PostFactory::new()->create(['title' => 'My First Post']);

    $this->browser()
        ->visit("/posts/{$post->getId()}")
        ->assertSuccessful()
        ->assertSeeIn('title', 'My First Post')
        ->assertSeeIn('h1', 'My First Post')
        ->assertNotSeeElement('#comments')
        ->fillField('Comment', 'My First Comment')
        ->click('Submit')
        ->assertOn("/posts/{$post->getId()}")
        ->assertSeeIn('#comments', 'My First Comment')
    ;
}
```

## Installation

```
$ composer require zenstruck/browser --dev
```

Optionally, enable the provided extension in your `phpunit.xml`:

```xml
<!-- phpunit.xml -->

<extensions>
    <extension class="Zenstruck\Browser\Test\BrowserExtension" />
</extensions>
```

This extension provides the following features:

1. Intercepts test errors/failures and saves the browser's source (and screenshot/js console log if
   applicable) to the filesystem.
2. After your test suite is finished, list of summary of all saved artifacts (source/screenshots/js
   console logs) in your console.

## Configuration

There are several environment variables available to configure:

| Variable                   | Description                                                                      | Default                            |
|----------------------------|----------------------------------------------------------------------------------|------------------------------------|
| `BROWSER_SOURCE_DIR`       | Directory to save source files to.                                               | `./var/browser/source`             |
| `BROWSER_SCREENSHOT_DIR`   | Directory to save screenshots to (only applies to `PantherBrowser`).             | `./var/browser/screenshots`        |
| `BROWSER_CONSOLE_LOG_DIR`  | Directory to save javascript console logs to (only applies to `PantherBrowser`). | `./var/browser/console-logs`       |
| `BROWSER_FOLLOW_REDIRECTS` | Whether to follow redirects by default (only applies to `KernelBrowser`).        | `1` _(true)_                       |
| `BROWSER_CATCH_EXCEPTIONS` | Whether to catch exceptions by default (only applies to `KernelBrowser`).        | `1` _(true)_                       |
| `KERNEL_BROWSER_CLASS`     | `KernelBrowser` class to use.                                                    | `Zenstruck\Browser\KernelBrowser`  |
| `PANTHER_BROWSER_CLASS`    | `PantherBrowser` class to use.                                                   | `Zenstruck\Browser\PantherBrowser` |
| `PANTHER_NO_HEADLESS`      | Disable headless-mode and allow usage of `PantherBrowser::pause()`.              | `0` _(false)_                      |


## Usage

This library provides 3 different "browsers":

1. [KernelBrowser](#kernelbrowser): makes requests using your Symfony Kernel *(this is the fastest browser)*.
3. [PantherBrowser](#pantherbrowser): makes requests to a webserver with a real browser using `symfony/panther` which
   allows testing javascript *(this is the slowest browser)*.

You can use these Browsers in your tests by having your test class use the `HasBrowser` trait:

```php
namespace App\Tests;

use PHPUnit\Framework\TestCase;
use Zenstruck\Browser\Test\HasBrowser;

class MyTest extends TestCase
{
    use HasBrowser;

    /**
     * Requires this test extends Symfony\Bundle\FrameworkBundle\Test\KernelTestCase
     * or Symfony\Bundle\FrameworkBundle\Test\WebTestCase.
     */
    public function test_using_kernel_browser(): void
    {
        $this->browser()
            ->visit('/my/page')
            ->assertSeeIn('h1', 'Page Title')
        ;
    }

    /**
     * Requires this test extends Symfony\Component\Panther\PantherTestCase.
     */
    public function test_using_panther_browser(): void
    {
        $this->pantherBrowser()
            ->visit('/my/page')
            ->assertSeeIn('h1', 'Page Title')
        ;
    }
}
```

All browsers have the following methods:

```php
/** @var \Zenstruck\Browser\KernelBrowser|\Zenstruck\Browser\PantherBrowser $browser **/

$browser
    // ACTIONS
    ->visit('/my/page')
    ->click('A link')
    ->fillField('Name', 'Kevin')
    ->checkField('Accept Terms')
    ->uncheckField('Accept Terms')
    ->selectField('Canada') // "radio" select
    ->selectField('Type', 'Employee') // "select" single option
    ->selectField('Notification', ['Email', 'SMS']) // "select" multiple options
    ->attachFile('Photo', '/path/to/photo.jpg')
    ->attachFile('Photo', ['/path/to/photo1.jpg', '/path/to/photo2.jpg') // attach multiple files (if field supports this)
    ->click('Submit')

    // ASSERTIONS
    ->assertOn('/my/page') // by default checks "path", "query" and "fragment"
    ->assertOn('/a/page', ['path']) // check just the "path"

    // these look in the entire response body (useful for non-html pages)
    ->assertContains('some text')
    ->assertNotContains('some text')

    // these look in the html only
    ->assertSee('some text')
    ->assertNotSee('some text')
    ->assertSeeIn('h1', 'some text')
    ->assertNotSeeIn('h1', 'some text')
    ->assertSeeElement('h1')
    ->assertNotSeeElement('h1')
    ->assertElementCount('ul li', 2)
    ->assertElementAttributeContains('head meta[name=description]', 'content', 'my description')
    ->assertElementAttributeNotContains('head meta[name=description]', 'content', 'my description')

    // form field assertions
    ->assertFieldEquals('Username', 'kevin')
    ->assertFieldNotEquals('Username', 'john')

    // form checkbox assertions
    ->assertChecked('Accept Terms')
    ->assertNotChecked('Accept Terms')

    // form select assertions
    ->assertSelected('Type', 'Employee')
    ->assertNotSelected('Type', 'Admin')

    // form multi-select assertions
    ->assertSelected('Roles', 'Content Editor')
    ->assertSelected('Roles', 'Human Resources')
    ->assertNotSelected('Roles', 'Owner')

    // CONVENIENCE METHODS
    ->use(function() {
        // do something without breaking
    })

    ->use(function(\Zenstruck\Browser $browser) {
        // access the current Browser instance
    })

    ->use(function(\Symfony\Component\BrowserKit\AbstractBrowser $browser)) {
        // access the "inner" browser
    })

    ->use(function(\Symfony\Component\BrowserKit\CookieJar $cookieJar)) {
        // access the cookie jar
        $cookieJar->expire('MOCKSESSID');
    })

    ->use(function(\Zenstruck\Browser $browser, \Symfony\Component\DomCrawler\Crawler $crawler) {
        // access the current Browser instance and the current crawler
    })

    // save the raw source of the current page
    // by default, saves to "<project-root>/var/browser/source"
    // configure with "BROWSER_SOURCE_DIR" env variable
    ->saveSource('source.txt')

    // the following use symfony/var-dumper's dump() function and continue
    ->dump() // raw response body or array if json
    ->dump('h1') // html element
    ->dump('foo') // if json response, array key
    ->dump('foo.*.baz') // if json response, JMESPath notation can be used

    // the following use symfony/var-dumper's dd() function ("dump & die")
    ->dd() // raw response body or array if json
    ->dd('h1') // html element
    ->dd('foo') // if json response, array key
    ->dd('foo.*.baz') // if json response, JMESPath notation can be used
;
```

### KernelBrowser

These browsers have the following methods:

```php
/** @var \Zenstruck\Browser\KernelBrowser $browser **/

$browser
    // response assertions
    ->assertStatus(200)
    ->assertSuccessful() // 2xx status code
    ->assertRedirected() // 3xx status code
    ->assertHeaderEquals('Content-Type', 'text/html; charset=UTF-8')
    ->assertHeaderContains('Content-Type', 'html')

    // helpers for quickly checking the content type
    ->assertJson()
    ->assertXml()
    ->assertHtml()

    // authenticate a user for subsequent actions
    ->actingAs($user) // \Symfony\Component\Security\Core\User\UserInterface

    // If using zenstruck/foundry, you can pass a factory/proxy
    ->actingAs(UserFactory::new())

    // by default, exceptions are caught and converted to a response
    // use the BROWSER_CATCH_EXCEPTIONS environment variable to change default
    // this disables that behaviour allowing you to use TestCase::expectException()
    ->throwExceptions()

    // enable catching exceptions
    ->catchExceptions()

    // by default, the kernel is rebooted between requests
    // this disables this behaviour
    ->disableReboot()

    // re-enable rebooting between requests if previously disabled
    ->enableReboot()

    // enable the profiler for the next request (if not globally enabled)
    ->withProfiling()

    // by default, redirects are followed, this disables that behaviour
    // use the BROWSER_FOLLOW_REDIRECTS environment variable to change default
    ->interceptRedirects()

    // enable following redirects
    // if currently on a redirect response, follows
    ->followRedirects()

    // Follows a redirect if ->interceptRedirects() has been turned on
    ->followRedirect() // follows all redirects by default
    ->followRedirect(1) // just follow 1 redirect

    // combination of assertRedirected(), followRedirect(), assertOn()
    ->assertRedirectedTo('/some/page') // follows all redirects by default
    ->assertRedirectedTo('/some/page', 1) // just follow 1 redirect

    // combination of interceptRedirects(), withProfiling(), click()
    // useful for submitting forms and making assertions on the "redirect response"
    ->clickAndIntercept('button')

    // exception assertions for the "next request"
    ->expectException(MyException::class, 'the message')
    ->post('/url/that/throws/exception') // fails if above exception not thrown

    ->expectException(MyException::class, 'the message')
    ->click('link or button') // fails if above exception not thrown
;

// Access the Symfony Profiler for the last request
$queryCount = $browser
    // If profiling not not globally enabled for tests, ->withProfiling()
    // must be called before the request.
    ->profile()->getCollector('db')->getQueryCount()
;

// "use" a specific data collector
$browser->use(function(\Symfony\Component\HttpKernel\DataCollector\RequestDataCollector $collector) {
    // ...
})
```

#### HTTP Requests

```php
use Zenstruck\Browser\HttpOptions;

/** @var \Zenstruck\Browser\KernelBrowser $browser **/

$browser
    // http methods
    ->get('/api/endpoint')
    ->put('/api/endpoint')
    ->post('/api/endpoint')
    ->delete('/api/endpoint')

    // second parameter can be an array of request options
    ->post('/api/endpoint', [
        // request headers
        'headers' => ['X-Token' => 'my-token'],

        // request body
        'body' => 'request body',
    ])
    ->post('/api/endpoint', [
        // json_encode request body and set Content-Type/Accept headers to application/json
        'json' => ['request' => 'body'],

        // simulates an AJAX request (sets the X-Requested-With to XMLHttpRequest)
        'ajax' => true,
    ])

    // optionally use the provided Zenstruck\Browser\HttpOptions object
    ->post('/api/endpoint',
        HttpOptions::create()->withHeader('X-Token', 'my-token')->withBody('request body')
    )

    // sets the Content-Type/Accept headers to application/json
    ->post('/api/endpoint', HttpOptions::json())

    // json encodes value and sets as body
    ->post('/api/endpoint', HttpOptions::json(['request' => 'body']))

    // simulates an AJAX request (sets the X-Requested-With to XMLHttpRequest)
    ->post('/api/endpoint', HttpOptions::ajax())

    // simulates a JSON AJAX request
    ->post('/api/endpoint', HttpOptions::jsonAjax())
;
```

#### Json Assertions

Make assertions about json responses using [JMESPath expressions](https://jmespath.org/)
See the [JMESPath Tutorials](https://jmespath.org/tutorial.html) to learn more.

**NOTE:** `mtdowling/jmespath.php` is required: `composer require --dev mtdowling/jmespath.php`.

```php
/** @var \Zenstruck\Browser\KernelBrowser $browser **/
$browser
    ->get('/api/endpoint')
    ->assertJson() // ensures the content-type is application/json
    ->assertJsonMatches('foo.bar.baz', 1) // automatically calls ->assertJson()
    ->assertJsonMatches('foo.*.baz', [1, 2, 3])
    ->assertJsonMatches('length(foo)', 3)
    ->assertJsonMatches('"@some:thing"', 6) // note: special characters like : and @ need to be wrapped in quotes
;

// access the json "crawler"
$json = $browser
    ->get('/api/endpoint')
    ->json()
;

$json->assertMatches('foo.bar.baz', 1);
$json->search('foo.bar.baz'); // mixed (the found value at "JMESPath expression")
$json->decoded(); // the decoded json
(string) $json; // the json string pretty-printed

// "use" the json crawler
$json = $browser
    ->get('/api/endpoint')
    ->use(function(\Zenstruck\Browser\Json $json) {
        $json->assertMatches('foo.bar.baz', 1);
    })
;
```

### PantherBrowser

*The `PantherBrowser` is experimental in 1.0 and may be subject to BC Breaks.*

This browser has the following extra methods:

```php
/** @var \Zenstruck\Browser\PantherBrowser $browser **/

$browser
    // pauses the tests and enters "interactive mode" which
    // allows you to investigate the current state in the browser
    // (requires the env variable PANTHER_NO_HEADLESS=1)
    ->pause()

    // take a screenshot of the current browser state
    // by default, saves to "<project-root>/var/browser/screenshots"
    // configure with "BROWSER_SCREENSHOT_DIR" env variable
    ->takeScreenshot('screenshot.png')

    // save the browser's javascript console error log
    // by default, saves to "<project-root>/var/browser/console-log"
    // configure with "BROWSER_CONSOLE_LOG_DIR" env variable
    ->saveConsoleLog('console.log')

    // check if element is visible in the browser
    ->assertVisible('.selector')
    ->assertNotVisible('.selector')

    // wait x milliseconds
    ->wait(1000) // 1 second

    ->waitUntilVisible('.selector')
    ->waitUntilNotVisible('.selector')
    ->waitUntilSeeIn('.selector', 'some text')
    ->waitUntilNotSeeIn('.selector', 'some text')

    // dump() the browser's console error log
    ->dumpConsoleLog()

    // dd() the browser's console error log
    ->ddConsoleLog()

    // take screenshot (default filename is "screenshot.png")
    ->ddScreenshot()
;
```

### Multiple Browser Instances

Within your test, you can call `->xBrowser()` methods multiple times to get
different browser instances. This could be useful for testing an app with
real-time capabilities (ie websockets):

```php
namespace App\Tests;

use Symfony\Component\Panther\PantherTestCase;
use Zenstruck\Browser\Test\HasBrowser;

class MyTest extends PantherTestCase
{
    use HasBrowser;

    public function testDemo(): void
    {
        $browser1 = $this->pantherBrowser()
            ->visit('/my/page')
            // ...
        ;

        $browser2 = $this->pantherBrowser()
            ->visit('/my/page')
            // ...
        ;
    }
}
```

### Mailer Component

See https://github.com/zenstruck/mailer-test#zenstruckbrowser-integration.

## Extending

### Test Browser Configuration

You can configure default options or a starting state for your browser in your tests by
overriding the `xBrowser()` method from the `HasBrowser` trait:

```php
namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Browser\KernelBrowser;
use Zenstruck\Browser\Test\HasBrowser;

class MyTest extends KernelTestCase
{
    use HasBrowser {
        browser as baseKernelBrowser;
    }

    public function testDemo(): void
    {
        $this->browser()
            ->assertOn('/') // browser always starts on the homepage (as defined below)
        ;
    }

    protected function browser(): KernelBrowser
    {
        return $this->baseKernelBrowser()
            ->interceptRedirects() // always intercept redirects
            ->throwExceptions() // always throw exceptions
            ->visit('/') // always start on the homepage
        ;
    }
}
```

### Custom Components

You may have pages or page parts that have specific actions/assertions you use
quite regularly in your tests. You can wrap these up into a *Component*. Let's create
a `CommentComponent` as an example to demonstrate this feature:

```php
namespace App\Tests;

use Zenstruck\Browser\Component;
use Zenstruck\Browser\KernelBrowser;

/**
 * If only using this component with a specific browser, this type hint can help your IDE.
 *
 * @method KernelBrowser browser()
 */
class CommentComponent extends Component
{
    public function assertHasNoComments(): self
    {
        $this->browser()->assertElementCount('#comments li', 0);

        return $this; // optionally make methods fluent
    }

    public function assertHasComment(string $body, string $author): self
    {
        $this->browser()
            ->assertSeeIn('#comments li span.body', $body)
            ->assertSeeIn('#comments li span.author', $author)
        ;

        return $this;
    }

    public function addComment(string $body, string $author): self
    {
        $this->browser()
            ->fillField('Name', $author)
            ->fillField('Comment', $body)
            ->click('Add Comment')
        ;

        return $this;
    }

    protected function preAssertions(): void
    {
        // this is called as soon as the component is loaded
        $this->browser()->assertSeeElement('#comments');
    }

    protected function preActions(): void
    {
        // this is called when the component is loaded but before
        // preAssertions(). Useful for page components where you
        // need to navigate to the page:
        // $this->browser()->visit('/contact');
    }
}
```

Access and use this new component in your tests:

```php
/** @var \Zenstruck\Browser $browser **/

$browser
    ->visit('/post/1')
    ->use(function(CommentComponent $component) {
        // the function typehint triggers the component to be loaded,
        // preActions() run and preAssertions() run

        $component
            ->assertHasNoComments()
            ->addComment('comment body', 'Kevin')
            ->assertHasComment('comment body')
        ;
    })
;

// you can optionally inject multiple components into the ->use() callback
$browser->use(function(Component1 $component1, Component2 $component2) {
    $component1->doSomething();
    $component2->doSomethingElse();
});
```

### Custom HttpOptions

If you find yourself creating a lot of [http requests](#http-requests) with the same options
(ie an `X-Token` header) there are a couple ways to reduce this duplication:

1. Use `->setDefaultHttpOptions()` for the current browser:
   ```php
   /** @var \Zenstruck\Browser\KernelBrowser $browser **/

   $browser
       ->setDefaultHttpOptions(['headers' => ['X-Token' => 'my-token']])

       // now all http requests will have the X-Token header
       ->get('/endpoint')

       // "per-request" options will be merged with the default
       ->get('/endpoint', ['headers' => ['Another' => 'Header']])
   ;
   ```

2. Use `->setDefaultHttpOptions()` in your test case's [`configureBrowser()`](#test-browser-configuration) method:
   ```php
   namespace App\Tests;

   use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
   use Zenstruck\Browser\KernelBrowser;
   use Zenstruck\Browser\Test\HasBrowser;

   class MyTest extends KernelTestCase
   {
       use HasBrowser {
           browser as baseKernelBrowser;
       }

       public function testDemo(): void
       {
           $this->browser()
               // all http requests in this test class will have the X-Token header
               ->get('/endpoint')

               // "per-request" options will be merged with the default
               ->get('/endpoint', ['headers' => ['Another' => 'Header']])
           ;
       }

       protected function browser(): KernelBrowser
       {
           return $this->baseKernelBrowser()
               ->setDefaultHttpOptions(['headers' => ['X-Token' => 'my-token']])
           ;
       }
   }
   ```

3. Create a custom `HttpOptions` object:
   ```php
   namespace App\Tests;

   use Zenstruck\Browser\HttpOptions;

   class AppHttpOptions extends HttpOptions
   {
       public static function api(string $token, $json = null): self
       {
           return self::json($json)
               ->withHeader('X-Token', $token)
           ;
       }
   }
   ```

   Then, in your tests:

   ```php
   use Zenstruck\Browser\HttpOptions;

   /** @var \Zenstruck\Browser\KernelBrowser $browser **/

   $browser
       // instead of
       ->post('/api/endpoint', HttpOptions::json()->withHeader('X-Token', 'my-token'))

       // use your ApiHttpOptions object
       ->post('/api/endpoint', AppHttpOptions::api('my-token'))
   ;
   ```

4. Create a [custom browser](#custom-browser) with your own request method (ie `->apiRequest()`).

### Custom Browser

It is likely you will want to add your own actions and assertions. You can do this
by creating your own *Browser* that extends one of the implementations. You can then
add your own actions/assertions by using the base browser methods.

```php
namespace App\Tests;

use Zenstruck\Browser\KernelBrowser;

class AppBrowser extends KernelBrowser
{
    public function assertHasToolbar(): self
    {
        return $this->assertSeeElement('#toolbar');
    }
}
```

Then, depending on the implementation you extended from, set the appropriate env variable:

* `KernelBrowser`: `KERNEL_BROWSER_CLASS`
* `PantherBrowser`: `PANTHER_BROWSER_CLASS`

For the example above, you would set `KERNEL_BROWSER_CLASS=App\Tests\AppBrowser`.

**TIP**: Create a base functional test case so all your tests can use your
custom browser and use the `@method` annotation to ensure your tests can
autocomplete your custom methods:

```php
namespace App\Tests;

use App\Tests\AppBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

/**
 * @method AppBrowser browser()
 */
abstract class MyTest extends WebTestCase
{
    use HasBrowser;
}
```

### Extensions

There are several packaged extensions. These are traits that can be added to a
[Custom Browser](#custom-browser).

#### Mailer Extension

See https://github.com/zenstruck/mailer-test#zenstruckbrowser-integration.

#### Custom Extension

You can create your own extensions for repetitive tasks. The example below is for
an `AuthenticationExtension` to login/logout users and make assertions about
a users authenticated status:

```php
namespace App\Tests\Browser;

trait AuthenticationExtension
{
    public function loginAs(string $username, string $password): self
    {
        return $this
            ->visit('/login')
            ->fillField('email', $username)
            ->fillField('password', $password)
            ->click('Login')
        ;
    }

    public function logout(): self
    {
        return $this->visit('/logout');
    }

    public function assertLoggedIn(): self
    {
        $this->assertSee('Logout');

        return $this;
    }

    public function assertLoggedInAs(string $user): self
    {
        $this->assertSee($user);

        return $this;
    }

    public function assertNotLoggedIn(): self
    {
        $this->assertSee('Login');

        return $this;
    }
}
```

Add to your [Custom Browser](#custom-browser):

```php
namespace App\Tests;

use App\Tests\Browser\AuthenticationExtension;
use Zenstruck\Browser\KernelBrowser;

class AppBrowser extends KernelBrowser
{
    use Authentication;
}
```

Use in your tests:

```php
public function testDemo(): void
{
    $this->browser()
        // goes to the /login page, fills email/password fields,
        // and presses the Login button
        ->loginAs('kevin@example.com', 'password')

        // asserts text "Logout" exists (assumes you have a logout link when users are logged in)
        ->assertLoggedIn()

        // asserts email exists as text (assumes you display the user's email when they are logged in)
        ->assertLoggedInAs('kevin@example.com')

        // goes to the /logout page
        ->logout()

        // asserts text "Login" exists (assumes you have a login link when users not logged in)
        ->assertNotLoggedIn()
    ;
}
```
