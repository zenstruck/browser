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
composer require zenstruck/browser --dev
```

## Security Policy

If you discover a security vulnerability, please do not open a public issue or pull request. Instead, please review this repository's [Security Policy](https://github.com/zenstruck/browser/security) for instructions on how to report it responsibly.

Optionally, enable the provided extension in your `phpunit.xml`:

- PHPUnit 8 or 9 :
```xml
<!-- phpunit.xml -->

<extensions>
    <extension class="Zenstruck\Browser\Test\BrowserExtension" />
</extensions>
```

- PHPUnit 10+ :

```xml
<phpunit>
   ...
   <extensions>
      <bootstrap class="Zenstruck\Browser\Test\BrowserExtension" />
   </extensions>
</phpunit>
```

This extension provides the following features:

1. Intercepts test errors/failures and saves the browser's source (and screenshot/js console log if
   applicable) to the filesystem.
2. After your test suite is finished, list of summary of all saved artifacts (source/screenshots/js
   console logs) in your console.

## Usage

This library provides 2 different "browsers":

1. [KernelBrowser](#kernelbrowser): makes requests using your Symfony Kernel *(fast)*.
2. [PlaywrightBrowser](#playwrightbrowser): drives a real browser with `playwright-php/playwright-symfony` but passes
   its requests to your Symfony Kernel, so no webserver is required *(slow)*.

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
}
```

Both browsers have the following methods:

```php
/** @var \Zenstruck\Browser $browser **/

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
    ->selectField('Notification', []) // "un-select" all multiple options
    ->attachFile('Photo', '/path/to/photo.jpg')
    ->attachFile('Photo', ['/path/to/photo1.jpg', '/path/to/photo2.jpg']) // attach multiple files (if field supports this)
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

    // response assertions
    ->assertStatus(200)
    ->assertSuccessful() // 2xx status code
    ->assertHeaderEquals('Content-Type', 'text/html; charset=UTF-8')
    ->assertHeaderContains('Content-Type', 'html')
    ->assertHeaderEquals('X-Not-Present-Header', null)
    ->assertContentType('zip')

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

    ->use(function(\Symfony\Component\BrowserKit\AbstractBrowser $browser) {
        // access the "inner" browser
    })

    ->use(function(\Symfony\Component\BrowserKit\CookieJar $cookieJar) {
        // access the cookie jar
        $cookieJar->expire('MOCKSESSID');
    })

    ->use(function(\Psr\Container\ContainerInterface $container) {
        // access your app's service container
    })

    ->use(function(\Zenstruck\Browser $browser, \Symfony\Component\DomCrawler\Crawler $crawler) {
        // access the current Browser instance and the current crawler
    })

    ->crawler() // Symfony\Component\DomCrawler\Crawler instance for the current response

    ->content() // string - raw response body

    // save the raw source of the current page
    // by default, saves to "<project-root>/var/browser/source"
    // configure with "BROWSER_SOURCE_DIR" env variable
    ->saveSource('source.txt')

    // the following use symfony/var-dumper's dump() function and continue
    ->dump() // raw response body
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

### Authentication

Both browsers have helpers and assertions for authentication:

```php
/** @var \Zenstruck\Browser $browser **/

$browser
    // authenticate a user for subsequent actions
    ->actingAs($user) // \Symfony\Component\Security\Core\User\UserInterface

    // fail if authenticated
    ->assertNotAuthenticated()

    // fail if NOT authenticated
    ->assertAuthenticated()

    // fails if NOT authenticated as "kbond"
    ->assertAuthenticated('kbond')

    // \Symfony\Component\Security\Core\User\UserInterface
    ->assertAuthenticated($user)
;
```

#### Troubleshooting Authentication

> `LogicException: Cannot create the remember-me cookie; no master request available.`
> exception when calling `->assertAuthenticated()`

This is caused when the _token_ is a `RememberMeToken`, `lazy: true` in your firewall, and the
previous request didn't perform any security-related operations. Possible solutions:

1. Before calling `->assertAuthenticated()`, visit a page you know initiates security
   (ie `is_granted()` in a Twig template).
2. Call `->withProfiling()` before making the previous request. This enables the security
   data collector which performs security operations.
3. Set `framework.profiler.collect: true` in your test environment. This enables the profiler
   for all requests removing the need to ever call `->withProfiling()` but can slow down
   your tests.

### Exceptions

Exceptions thrown while handling a request are caught and converted to a response, as they are in
production. Both browsers can turn this off:

```php
/** @var \Zenstruck\Browser $browser **/

$browser
    // stop converting exceptions to responses, so they can be caught
    // use the BROWSER_CATCH_EXCEPTIONS environment variable to change the default
    // allows using TestCase::expectException()
    ->throwExceptions()

    // start catching them again
    ->catchExceptions()

    // exception assertions for the "next request"
    ->expectException(MyException::class, 'the message')
    ->click('link or button') // fails if the above exception is not thrown
;
```

### Redirects

By default, redirects are followed. Both browsers can stop on them instead:

```php
/** @var \Zenstruck\Browser $browser **/

$browser
    // stop on redirect responses instead of following them
    // use the BROWSER_FOLLOW_REDIRECTS environment variable to change the default
    ->interceptRedirects()

    // follow again, and follow the current response if it is a redirect
    ->followRedirects()

    ->assertRedirected() // 3xx status code

    // follow a redirect that was intercepted
    ->followRedirect() // follows all redirects by default
    ->followRedirect(1) // just follow 1 redirect

    // combination of assertRedirected(), followRedirect(), assertOn()
    ->assertRedirectedTo('/some/page') // follows all redirects by default
    ->assertRedirectedTo('/some/page', 1) // just follow 1 redirect

    // combination of interceptRedirects(), withProfiling(), click()
    // useful for submitting forms and making assertions on the "redirect response"
    ->clickAndIntercept('button')
;
```

> [!NOTE]
> While intercepting, the `PlaywrightBrowser` parks the real browser on the url that redirected and
> renders nothing, since no response is delivered to it. The status and `Location` are still
> available for assertions.

### Profiling

Both browsers expose the Symfony profiler:

```php
/** @var \Zenstruck\Browser $browser **/

// enable the profiler for the next request (not required if profiling is globally enabled)
$queryCount = $browser
    ->withProfiling()
    ->visit('/my/page')
    ->profile()->getCollector('db')->getQueryCount()
;

// "use" a specific data collector from the last request
$browser->use(function(\Symfony\Component\HttpKernel\DataCollector\RequestDataCollector $collector) {
    // ...
});
```

### KernelBrowser

This browser has the following methods:

```php
/** @var \Zenstruck\Browser\KernelBrowser $browser **/

$browser
    // helpers for quickly checking the content type
    ->assertJson()
    ->assertXml()
    ->assertHtml()

    // by default, the kernel is rebooted between requests
    // this disables this behaviour
    ->disableReboot()

    // re-enable rebooting between requests if previously disabled
    ->enableReboot()

    // exception assertions also work for the http methods below
    ->expectException(MyException::class, 'the message')
    ->post('/url/that/throws/exception') // fails if above exception not thrown
;
```

#### Session

The _KernelBrowser_ can inject your app's http session into `->use()`, to read what
the app stored or to set something up before making a request:

```php
/** @var \Zenstruck\Browser\KernelBrowser $browser **/

$browser
    ->use(function(\Symfony\Component\HttpFoundation\Session\SessionInterface $session) {
        // seed the session before any request is made
        $session->set('cart', ['product-1']);
    })
    ->visit('/cart')
    ->assertSee('product-1')

    ->use(function(\Symfony\Component\HttpFoundation\Session\SessionInterface $session) {
        // read what the app stored
        $this->assertSame(['product-1', 'product-2'], $session->get('cart'));
    })
;
```

The session is loaded from the session cookie if the browser already has one, so the
id is preserved and nothing the app stored is lost. It is saved, and its cookie
written, when the callback returns.

> [!NOTE]
> This requires the session to be enabled in your app, and a session storage that the
> test process can write to, typically `session.storage.factory.mock_file` in the test
> environment.

#### HTTP Requests

The _KernelBrowser_ can be used for testing API endpoints. The following http methods are available:

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

> [!NOTE]
> `mtdowling/jmespath.php` is required: `composer require --dev mtdowling/jmespath.php`.

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
$json->assertHas('foo.bar.baz');
$json->assertMissing('foo.bar.boo');
$json->search('foo.bar.baz'); // mixed (the found value at "JMESPath expression")
$json->decoded(); // the decoded json
(string) $json; // the json string pretty-printed

// "use" the json crawler
$json = $browser
    ->get('/api/endpoint')
    ->use(function(\Zenstruck\Browser\Json $json) {
        // Json acts like a proxy of zenstruck/assert Expectation class
        $json->hasCount(5);
        $json->contains('foo');
        // assert on children: the closure gets Json object contextualized on given selector
        // {"foo": "bar"}
        $json->assertThat('foo', fn(Json $json) => $json->equals('bar'))
        // assert on each element of an array
        // {"foo": [1, 2, 3]}
        $json->assertThatEach('foo', fn(Json $json) => $json->isGreaterThan(0));
        // assert json matches given json schema
        $json->assertMatchesSchema(file_get_contents('/path/to/json-schema.json'));
    })
;
```

> [!NOTE]
> See the [full `zenstruck/assert` expectation API documentation](https://github.com/zenstruck/assert#expectation-api)
> to see all the methods available on `Zenstruck\Browser\Json`.

### PlaywrightBrowser

> [!NOTE]
> The `PlaywrightBrowser` is experimental and may be subject to BC Breaks.

This drives a real browser so you can test javascript, but it does not start a webserver: requests
made by the browser are intercepted and passed to *your* booted kernel. The browser therefore talks
to the same application instance (and container) as your test, so mocked services, an in-memory
database and the profiler behave as they do with the [`KernelBrowser`](#kernelbrowser).

It requires [playwright-php/playwright-symfony](https://github.com/playwright-php/playwright-symfony)
(PHP 8.2+/Symfony 6.4+), Node.js 20+, and the Playwright browsers:

```
composer require --dev playwright-php/playwright-symfony
vendor/bin/playwright-install --browsers
```

> [!NOTE]
> If `PlaywrightSymfonyBundle` is registered and enabled, its configuration (base url, intercepted
> hosts, asset server) is used.

It requires your test to extend `KernelTestCase` (or `WebTestCase`):

```php
namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Browser\Test\HasBrowser;

class MyTest extends KernelTestCase
{
    use HasBrowser;

    public function test_using_playwright_browser(): void
    {
        $this->playwrightBrowser()
            ->visit('/my/page')
            ->assertSee('some text')
        ;
    }
}
```

> [!WARNING]
> Do not extend `Playwright\Symfony\Test\PlaywrightTestCase`. It manages its own browser and
> sessions, which would conflict with the ones managed here - a `LogicException` is thrown if you
> try.

Choose the engine with the `PLAYWRIGHT_BROWSER` env variable (`chromium` _(default)_, `firefox` or
`webkit`), and set `PLAYWRIGHT_HEADLESS=false` to watch the browser as it runs.

This browser has the following extra methods:

```php
/** @var \Zenstruck\Browser\PlaywrightBrowser $browser **/

$browser
    // open the Playwright Inspector and pause the test
    ->pause()

    // take a screenshot of the current browser state
    // by default, saves to "<project-root>/var/browser/screenshots"
    // configure with "BROWSER_SCREENSHOT_DIR" env variable
    ->takeScreenshot('screenshot.png')

    // save the browser's javascript console log
    // by default, saves to "<project-root>/var/browser/console-logs"
    // configure with "BROWSER_CONSOLE_LOG_DIR" env variable
    ->saveConsoleLog('console.log')

    // check if element is visible in the browser
    ->assertVisible('.selector')
    ->assertNotVisible('.selector')

    // wait x milliseconds
    ->wait(1000) // 1 second

    // these return as soon as the condition is met
    ->waitUntilVisible('.selector')
    ->waitUntilNotVisible('.selector')
    ->waitUntilSeeIn('.selector', 'some text')
    ->waitUntilNotSeeIn('.selector', 'some text')

    ->doubleClick('Link')
    ->rightClick('Link')

    // dump() the browser's console log
    ->dumpConsoleLog()

    // dd() the browser's console log
    ->ddConsoleLog()

    // dd() and take screenshot (default filename is "screenshot.png")
    ->ddScreenshot()
;
```

> [!NOTE]
> Uncaught javascript errors are not included in the console log: Playwright reports these as a
> separate `pageerror` event which `playwright-php` does not yet expose.

### Multiple Browser Instances

Within your test, you can call `->xBrowser()` methods multiple times to get
different browser instances. This could be useful for testing an app with
real-time capabilities (ie websockets):

```php
namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Browser\Test\HasBrowser;

class MyTest extends KernelTestCase
{
    use HasBrowser;

    public function testDemo(): void
    {
        $browser1 = $this->playwrightBrowser()
            ->visit('/my/page')
            // ...
        ;

        $browser2 = $this->playwrightBrowser()
            ->visit('/my/page')
            // ...
        ;
    }
}
```

Each `playwrightBrowser()` call gets its own browser context, isolated from the others in cookies
and storage, while sharing one browser process. They also share the kernel booted for the test, so
they see the same application state, just as separate browsers hitting one webserver would.

### Parallel Testing

Both browsers work with [ParaTest](https://github.com/paratestphp/paratest). Each worker is a
separate PHP process with its own browser, launched only if that worker runs a test needing one.

Saved artifacts work as usual: each worker writes to the configured directories, and a failure
still saves the source, screenshot and console log. The summary printed at the end of a serial run
is not shown, as ParaTest does not surface worker output by then.

> [!NOTE]
> Expect one browser per worker: `--processes 8` means up to eight browsers, each with its own Node
> process, so pick a number your machine and CI runner can carry.

## Configuration

There are several environment variables available to configure:

| Variable                   | Description                                                                                     | Default                               |
|----------------------------|-------------------------------------------------------------------------------------------------|---------------------------------------|
| `BROWSER_SOURCE_DIR`       | Directory to save source files to.                                                              | `./var/browser/source`                |
| `BROWSER_SCREENSHOT_DIR`   | Directory to save screenshots to (only applies to `PlaywrightBrowser`).                         | `./var/browser/screenshots`           |
| `BROWSER_CONSOLE_LOG_DIR`  | Directory to save javascript console logs to (only applies to `PlaywrightBrowser`).             | `./var/browser/console-logs`          |
| `BROWSER_FOLLOW_REDIRECTS` | Whether to follow redirects by default.                                                         | `1` _(true)_                          |
| `BROWSER_CATCH_EXCEPTIONS` | Whether to catch exceptions by default.                                                         | `1` _(true)_                          |
| `BROWSER_SOURCE_DEBUG`     | Whether to add request metadata to written source files (only applies to `KernelBrowser`).      | `0` _(false)_                         |
| `KERNEL_BROWSER_CLASS`     | `KernelBrowser` class to use.                                                                   | `Zenstruck\Browser\KernelBrowser`     |
| `PLAYWRIGHT_BROWSER_CLASS` | `PlaywrightBrowser` class to use.                                                               | `Zenstruck\Browser\PlaywrightBrowser` |
| `PLAYWRIGHT_BROWSER`       | Browser engine to use: `chromium`, `firefox` or `webkit` (only applies to `PlaywrightBrowser`). | `chromium`                            |
| `PLAYWRIGHT_HEADLESS`      | Set to `false` to watch the browser (only applies to `PlaywrightBrowser`).                      | `true`                                |
| `BROWSER_FILE_LINK_FORMAT` | Turns file paths seen in `Saved Browser Artifacts` into links that open in your editor.         | `file://%f#L%l`                       |

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

### Components

Components are objects that wrap common tasks into a _component_ object. These
extend `Zenstruck\Browser\Component` and can be injected into a browser's `->use()`
callable:

```php
/** @var \Zenstruck\Browser $browser **/

$browser
    ->use(function(MyComponent $component) {
        $component->method();
    })
;
```

#### Mailer Component

See https://github.com/zenstruck/mailer-test#zenstruckbrowser-integration.

#### Custom Components

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

2. Use `->setDefaultHttpOptions()` in your test case's [default browser configuration](#test-browser-configuration):
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
* `PlaywrightBrowser`: `PLAYWRIGHT_BROWSER_CLASS`

For the example above, you would set `KERNEL_BROWSER_CLASS=App\Tests\AppBrowser`.

> [!TIP]
> Create a base functional test case so all your tests can use your
> custom browser and use the `@method` annotation to ensure your tests can
> autocomplete your custom methods:

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

These are traits that can be added to a [Custom Browser](#custom-browser).

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
    use AuthenticationExtension;
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
