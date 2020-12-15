# zenstruck/browser

[![CI Status](https://github.com/zenstruck/browser/workflows/CI/badge.svg)](https://github.com/zenstruck/browser/actions?query=workflow%3ACI)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/zenstruck/browser/badges/quality-score.png?b=1.x)](https://scrutinizer-ci.com/g/zenstruck/browser/?branch=1.x)
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

This library provides `Zenstruck\Browser`, which is a fluent wrapper around
`Symfony\Component\BrowserKit\AbstractBrowser` (previously `Symfony\Component\BrowserKit\Client`
before Symfony 5). While this class is usable for any `AbstractBrowser` there are three
implementations provided by this library:

### 1. KernelBrowser

This browser is a wrapper for `Symfony\Bundle\FrameworkBundle\KernelBrowser`. To use in your functional
tests, have your standard Symfony `WebTestCase` or `KernelTestCase` use the `HasKernelBrowser` trait:

```php
namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasKernelBrowser;

class MyTest extends WebTestCase
{
    use HasKernelBrowser;

    public function testDemo(): void
    {
        $this->browser()
            ->visit('/my/page')
            ->assertSuccessful()
        ;
    }
}
```

### 2. HttpBrowser

This browser is a wrapper for `Symfony\Component\BrowserKit\HttpBrowser` (requires `symfony/http-client`).
To use in your functional tests, have a `PantherTestCase` test use the `HasHttpBrowser` trait.
`symfony/panther` is required as it needs a real webserver that Panther can create:

```php
namespace App\Tests;

use Symfony\Component\Panther\PantherTestCase;
use Zenstruck\Browser\Test\HasHttpBrowser;

class MyTest extends PantherTestCase
{
    use HasHttpBrowser;

    public function testDemo(): void
    {
        $this->browser()
            ->visit('/my/page')
            ->assertSuccessful()
        ;
    }
}
```

### 3. PantherBrowser

This browser is a wrapper for `Symfony\Component\Panther\Client` (requires `symfony/panther`).
To use in your functional tests, have a `PantherTestCase` test use the `HasPantherBrowser` trait:

```php
namespace App\Tests;

use Symfony\Component\Panther\PantherTestCase;
use Zenstruck\Browser\Test\HasPantherBrowser;

class MyTest extends PantherTestCase
{
    use HasPantherBrowser;

    public function testDemo(): void
    {
        $this->browser()
            ->visit('/my/page')
            ->assertSee('My Title')
        ;
    }
}
```

## Usage

`HasKernelBrowser`, `HasHttpBrowser` and `HasPantherBrowser` all provide a `->browser()`
method that returns an instance of `Zenstruck\Browser` which has the following
actions/assertions:

### Actions

```php
/** @var \Zenstruck\Browser $browser **/

$browser
    ->visit('/my/page')
    ->follow('A link')
    ->fillField('Name', 'Kevin')
    ->checkField('Accept Terms')
    ->uncheckField('Accept Terms')
    ->selectFieldOption('Type', 'Employee') // single option select
    ->selectFieldOptions('Notification', ['Email', 'SMS']) // multi-option select
    ->attachFile('Photo', '/path/to/photo.jpg')
    ->click('Submit')
;
```

### Assertions

```php
/** @var \Zenstruck\Browser $browser **/

$browser
    ->assertOn('/my/page')

    // these look in the entire response body (useful for non-html pages)
    ->assertResponseContains('some text')
    ->assertResponseNotContains('some text')

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

    // response assertions (NOTE: these are not available for PantherBrowser)
    ->assertStatus(200)
    ->assertSuccessful() // 2xx status code
    ->assertRedirected() // 3xx status code
    ->assertHeaderContains('Content-Type', 'text/html; charset=UTF-8')
;
```

### Convenience Methods

```php
/** @var \Zenstruck\Browser $browser **/

$browser
    ->use(function() {
        // do something without breaking
    })

    ->use(function(\Zenstruck\Browser $browser) {
        // access the current Browser instance
    })

    // save the raw source of the current page
    ->saveSource('/path/to/source.txt')

    // the following use symfony/var-dumper's dump() function and continue
    ->dump() // raw response body or array if json
    ->dump('h1') // html element
    ->dump('foo') // if json response, array key
    ->dump('foo.*.baz') // if json response and mtdowling/jmespath.php installed, can use jmes path notation

    // the following use symfony/var-dumper's dd() function ("dump & die")
    ->dd() // raw response body or array if json
    ->dd('h1') // html element
    ->dd('foo') // if json response, array key
    ->dd('foo.*.baz') // if json response and mtdowling/jmespath.php installed, can use jmes path notation
;
```

### Profiler Access

*Only available on `KernelBrowser`/`HttpBrowser`.*

```php
/** @var \Zenstruck\Browser\KernelBrowser $browser **/

$queryCount = $browser
    // enable the profiler for the next request (if not globally enabled)
    // (NOTE: Not available for HttpBrowser)
    ->withProfiling()
    ->visit('/page')
    
    // HttpBrowser requires profiling have collect globally enabled.
    // If not globally enabled and using KernelBrowser, ->withProfiling()
    // must be called before the request.
    ->profile()->getCollector('db')->getQueryCount()
;
````

### Redirect Handling

*Only available on `KernelBrowser`/`HttpBrowser`.*

```php
/** @var \Zenstruck\Browser\KernelBrowser $browser **/

$browser
    // by default, redirects are followed, this disables that behaviour
    ->interceptRedirects()

    // Follows a redirect if ->interceptRedirects() has been turned on
    ->followRedirect()

    // combination of assertRedirected(), followRedirect(), assertOn()
    ->assertRedirectedTo('/some/page')
;
````

### Exception Handling

*Only available on `KernelBrowser`.*

```php
/** @var \Zenstruck\Browser\KernelBrowser $browser **/

$browser
    // by default, exceptions are caught and converted to a response
    // this disables that behaviour allowing you to use TestCase::expectException()
    ->throwExceptions()
    ->visit('/page')
;
````

### Http Actions

*Only available on `KernelBrowser`/`HttpBrowser`.*

```php
/** @var \Zenstruck\Browser\KernelBrowser $browser **/

// http request actions (NOTE: these are not available for PantherBrowser)
use Zenstruck\Browser\HttpOptions;

$browser
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

### PantherBrowser Actions

*The `PantherBrowser` is experimental in 1.0 and may be subject to BC Breaks.*

```php
/** @var \Zenstruck\Browser\PantherBrowser $browser **/

$browser
    // pauses the tests and enters "interactive mode" which
    // allows you to investigate the current state in the browser
    // (requires the env variable PANTHER_NO_HEADLESS=1)
    ->inspect()

    // take a screenshot of the current browser state
    ->takeScreenshot('/path/to/image.png')

    // save the browser's console error log
    ->saveConsoleLog('/path/to/console.log')

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
;
```

### Multiple Browser Instances

Within your test, you can call `->browser()` multiple times to get different
browser instances. This could be useful for testing an app with real-time
capabilities (ie websockets):

```php
namespace App\Tests;

use Symfony\Component\Panther\PantherTestCase;
use Zenstruck\Browser\Test\HasPantherBrowser;

class MyTest extends PantherTestCase
{
    use HasPantherBrowser;

    public function testDemo(): void
    {
        $browser1 = $this->browser()
            ->visit('/my/page')
            // ...
        ;
        
        $browser2 = $this->browser()
            ->visit('/my/page')
            // ...
        ;
    }
}
```

### Json Component

Make assertions about json responses using [JMESPath expressions](https://jmespath.org/)
Requires [mtdowling/jmespath.php](https://github.com/jmespath/jmespath.php) (`composer require
--dev mtdowling/jmespath.php`).

```php
use Zenstruck\Browser\Component\JsonComponent;

/** @var \Zenstruck\Browser $browser **/
$browser
    ->get('/api/endpoing')
    ->use(function(JsonComponent $component) {
        // automatically asserts the response's content-type is application/json
        $component
            ->assertMatches('foo.bar.baz', 1)
            ->assertMatches('foo.*.baz', [1, 2, 3])
            ->assertMatches('length(foo)', 3)
        ;
    })
;
```

**NOTE**: There is an [Json Extension](#json-extension) that adds the `assertJsonMatches()`
method right onto your custom browser.

### Email Component

You can make assertions about emails sent in the last request:

```php
use Zenstruck\Browser\Component\EmailComponent;
use Zenstruck\Browser\Component\Email\TestEmail;

/** @var \Zenstruck\Browser $browser **/
$browser
    ->visit('/page/that/does/not/send/email')
    ->use(function(EmailComponent $component) {
        $component->assertNoEmailSent();
    })

    ->visit('/page/that/sends/email')
    
    // just check that an email was sent to an address with a subject
    ->use(function(EmailComponent $component) {
        $component->assertEmailSentTo('kevin@example.com', 'Email Subject');
    })
    
    // advanced assertions
    ->use(function(EmailComponent $component) {
        $component->assertEmailSentTo('kevin@example.com', function(TestEmail $email) {
            $email
                ->assertSubject('Email Subject')
                ->assertFrom('from@example.com')
                ->assertReplyTo('reply@example.com')
                ->assertCc('cc1@example.com')
                ->assertCc('cc2@example.com')
                ->assertBcc('bcc@example.com')
                ->assertTextContains('some text')
                ->assertHtmlContains('some text')
                ->assertContains('some text') // asserts text and html both contain a value
                ->assertHasFile('file.txt', 'text/plain', 'Hello there!')
            ;
        });
    })
;
```

**NOTE**: There is an [Email Extension](#email-extension) that adds the `assertNoEmailSent()`
and `assertEmailSentTo()` methods right onto your custom browser.

## Extending

### Test Browser Configuration

You can configure default options or a starting state for your browser in your tests by
overriding the `configureBrowser`:

```php
namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser;
use Zenstruck\Browser\KernelBrowser;
use Zenstruck\Browser\Test\HasKernelBrowser;

class MyTest extends WebTestCase
{
    use HasKernelBrowser;

    public function testDemo(): void
    {
        $this->browser()
            ->assertOn('/') // browser always starts on the homepage (as defined below)
        ;
    }

    /**
     * @param Browser|KernelBrowser $browser
     */
    protected function configureBrowser(Browser $browser): void
    {
        $browser
            ->interceptRedirects() // always intercept redirects
            ->throwExceptions() // always throw exceptions
            ->visit('/') // always start on the homepage
        ;
    }
}
```

### Custom Components

You may have pages or page parts that have specific actions/assertions you use
quite regularly in your tests. You can wrap these up into a "Component". Let's create
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

If you find yourself creating a lot of [http requests](#http-actions) with the same options
(ie an `X-Token` header) there are a couple ways to reduce this duplication. You can either
create a [custom browser](#custom-browser) with a custom method (ie `->apiRequest()`) or
create and use a custom `HttpOptions` object:

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

/** @var \Zenstruck\Browser $browser **/

$browser
    // instead of
    ->post('/api/endpoint', HttpOptions::json()->withHeader('X-Token', 'my-token'))

    // use your ApiHttpOptions object
    ->post('/api/endpoint', AppHttpOptions::api('my-token'))
;
```

### Custom Browser

It is likely you will want to add your own actions and assertions. You can do this
by creating your own "Browser" that extends `Zenstruck\Browser` (or one of the implementations).
You can then add your own actions/assertions by using the base browser methods.

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

Then in your test case, (depending on the base browser), override the `xBrowserClass()`
method and return your custom class:

```php
namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasKernelBrowser;

/**
 * @method AppBrowser browser() This will help your IDE with typehints
 */
class MyTest extends WebTestCase
{
    use HasKernelBrowser;

    /**
     * Alternatively, set the appropriate env variable: KERNEL_BROWSER_CLASS=App\Tests\AppBrowser
     */
    protected static function kernelBrowserClass(): string
    {
        return AppBrowser::class;
    }

    public function testDemo(): void
    {
        $this->browser()
            ->visit('/my/page')
            ->assertHasToolbar()
        ;
    }
}
```

**TIP**: Create a base functional test case so all your tests can use your
custom browser.

Each Browser type, and their corresponding test trait have their own class method and
env variable:

1. `KernelBrowser`/`HasKernelBrowser`: `kernelBrowserClass()`/`KERNEL_BROWSER_CLASS`
2. `HttpBrowser`/`HasHttpBrowser`: `httpBrowserClass()`/`HTTP_BROWSER_CLASS`
3. `PantherBrowser`/`HasPantherBrowser`: `pantherBrowserClass()`/`PANTHER_BROWSER_CLASS`

### Extensions

There are several packaged extensions. These are traits that can be added to a
[Custom Browser](#custom-browser).

#### Json Extension

Wraps the [Json Component](#json-component) into methods directly on your browser.

Add to your [Custom Browser](#custom-browser):

```php
namespace App\Tests;

use Zenstruck\Browser\KernelBrowser;
use Zenstruck\Browser\Extension\Json;

class AppBrowser extends KernelBrowser
{
    use Json;
}
```

Use in your tests:

```php
public function testDemo(): void
{
    $this->browser()
        ->assertJsonMatches('foo.bar.baz', 1)
        ->assertJsonMatches('foo.*.baz', [1, 2, 3])
        ->assertJsonMatches('length(foo)', 3)
    ;
}
```

#### Email Extension

Wraps the [Email Component](#email-component) into methods directly on your browser.

Add to your [Custom Browser](#custom-browser):

```php
namespace App\Tests;

use Zenstruck\Browser\KernelBrowser;
use Zenstruck\Browser\Extension\Email;

class AppBrowser extends KernelBrowser
{
    use Email;
}
```

Use in your tests:

```php
public function testDemo(): void
{
    $this->browser()
        ->visit('/page/that/does/not/send/email')
        ->assertNoEmailSent()

        ->visit('/page/that/sends/email')

        // just check that an email was sent to an address with a subject
        ->assertEmailSentTo('kevin@example.com', 'Email Subject')
    ;
}
```

#### Authentication Extension

This extension is more of an example. Each Symfony application handles authentication
differently but this is a good starting point. You can either override the methods
provided by the extension or write your own.

Add to your [Custom Browser](#custom-browser):

```php
namespace App\Tests;

use Zenstruck\Browser\KernelBrowser;
use Zenstruck\Browser\Extension\Authentication;

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
