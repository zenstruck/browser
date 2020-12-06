# zenstruck/browser

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
        ->press('Submit')
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
        ->press('Submit')
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
    ->press('Submit')

    // Follows a redirect if ->interceptRedirects() has been turned on
    // (NOTE: Not available for PantherBrowser)
    ->followRedirect()
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

    // form assertions
    ->assertFieldEquals('Username', 'kevin')
    ->assertChecked('Accept Terms')
    ->assertNotChecked('Accept Terms')

    // response assertions (NOTE: these are not available for PantherBrowser)
    ->assertStatus(200)
    ->assertSuccessful() // 2xx status code
    ->assertRedirected() // 3xx status code
    ->assertHeaderContains('Content-Type', 'text/html; charset=UTF-8')

    // combination of assertRedirected(), followRedirect(), assertOn()
    ->assertRedirectedTo('/some/page')
;
```

### Convenience Methods

```php
/** @var \Zenstruck\Browser $browser **/

// convenience methods
$browser->container(); // the test service container (all services are public)

$browser
    // by default, redirects are followed, this disables that behaviour
    // (NOTE: not available for PantherBrowser)
    ->interceptRedirects()

    ->with(function() {
        // do something without breaking
    })

    ->with(function(\Zenstruck\Browser $browser) {
        // access the current Browser instance
    })

    ->dump() // dump() the html on the page (then continue)
    ->dump('h1') // dump() the h1 tag (then continue)
    ->dd() // dd() the html on the page
    ->dd('h1') // dd() the h1 tag
;

// KernelBrowser/HttpBrowser has access to the profiler
// HttpBrowser requires profiling have collect globally enabled
$queryCount = $browser->profile()->getCollector('db')->getQueryCount();

// KernelBrowser specific methods
$browser
    // by default, exceptions are caught and converted to a response
    // this disables that behaviour allowing you to use TestCase::expectException()
    ->throwExceptions()

    // enable the profiler for the next request (if not globally enabled)
    ->withProfiling()
;
```

### Http Actions

```php
/** @var \Zenstruck\Browser $browser **/

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

```php
/** @var \Zenstruck\Browser\PantherBrowser $browser **/

$browser
    // pauses the tests and enters "interactive mode" which
    // allows you to investigate the current state in the browser
    // (requires the env variable PANTHER_NO_HEADLESS=1)
    ->inspect()
;
```

### Email Component

You can make assertions about emails sent in the last request:

```php
use Zenstruck\Browser\Component\EmailComponent;
use Zenstruck\Browser\Component\Email\TestEmail;

/** @var \Zenstruck\Browser $browser **/
$browser
    ->visit('/page/that/does/not/send/email')
    ->with(function(EmailComponent $component) {
        $component->assertNoEmailSent();
    })

    ->visit('/page/that/sends/email')
    
    // just check that an email was sent to an address with a subject
    ->with(function(EmailComponent $component) {
        $component->assertEmailSentTo('kevin@example.com', 'Email Subject');
    })
    
    // advanced assertions
    ->with(function(EmailComponent $component) {
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
            ->press('Add Comment')
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
    ->with(function(CommentComponent $component) {
        // the function typehint triggers the component to be loaded,
        // preActions() run and preAssertions() run

        $component
            ->assertHasNoComments()
            ->addComment('comment body', 'Kevin')
            ->assertHasComment('comment body')
        ;
    })
;

// you can optionally inject multiple components into the ->with() callback
$browser->with(function(Component1 $component1, Component2 $component2) {
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
    protected static function kernelBrowserClass() : string
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
