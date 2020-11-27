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

## Usage

Have your functional test case use the `Browser` trait and call `->browser()` in
your tests:

```php
namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Browser\Test\Browser;

class MyTest extends KernelTestCase
{
    use Browser;

    public function testDemo(): void
    {
        $this->browser()
            ->visit('/my/page')
            ->assertSuccessful()
        ;
    }
}
```

`->browser()` returns an instance of `Zenstruck\Browser` with has the following
actions/assertions:

```php
/** @var \Zenstruck\Browser $browser **/

// actions
$browser
    ->visit('/my/page')
    ->follow('A link')
    ->fillField('Name', 'Kevin')
    ->checkField('Accept Terms')
    ->uncheckField('Accept Terms')
    ->selectFieldOption('Type', 'Employee')
    ->attachFile('Photo', '/path/to/photo.jpg')
    ->press('Submit')
    ->followRedirect()
;

// assertions
$browser
    ->assertStatus(200)
    ->assertSuccessful() // 2xx status code
    ->assertRedirected() // 3xx status code
    ->assertOn('/my/page')
    ->assertHeader('Content-Type', 'text/html; charset=UTF-8')

    // combination of assertRedirected(), followRedirect(), assertOn()
    ->assertRedirectedTo('/some/page')

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
    ->assertFieldContains('Username', 'kevin')
    ->assertChecked('Accept Terms')
    ->assertNotChecked('Accept Terms')
;

// http request actions
$browser
    ->get('/api/endpoint', $parameters, $files, $server)
    ->post('/api/endpoint', $parameters, $files, $server)
    ->delete('/api/endpoint', $parameters)
;

// convenience methods
$browser
    // by default, redirects are followed, this disables that behaviour
    ->interceptRedirects() 

    // by default, exceptions are caught and converted to a response
    // this disables that behaviour allowing you to use TestCase::expectException()
    ->throwExceptions()
 
    ->with(function(\Zenstruck\Browser $browser) {
        // do something without breaking
    })

    ->dump() // dump() the html on the page (then continues)
    ->dump('h1') // dump() the h1 tag (then continues)
    ->dd() // dd() the html on the page
    ->dd('h1') // dd() the h1 tag
;
```

## Extending

### Custom Browser

It is likely you will want to add your own actions and assertions. You can do this
by creating your own "Browser" that extends `Zenstruck\Browser`. You can then add
your own actions/assertions by using the base browser methods.

```php
namespace App\Tests;

use Zenstruck\Browser;

class AppBrowser extends Browser
{
    public function assertHasToolbar(): self
    {
        return $this->assertSeeElement('#toolbar');
    }

    public function addComment(string $name, string $content): self
    {
        return $this
            ->fillField('Name', $name)
            ->fillField('Comment', $content)
            ->press('Add Comment')
        ;
    }
}
```

Then in your test case, overwrite the `->createBrowser()` method:

```php
namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Browser\Test\Browser;

/**
 * @method AppBrowser browser() This will help your IDE with typehints
 */
class MyTest extends KernelTestCase
{
    use Browser;

    protected function createBrowser() : AppBrowser
    {
        return new AppBrowser(static::$container->get('test.client'));
    }

    public function testDemo(): void
    {
        $this->browser()
            ->visit('/my/page')
            ->assertHasToolbar()
            ->addComment('Kevin', 'My Comment')
        ;
    }
}
```

**TIP**: Create a base functional test case so all your tests can use your
custom browser.

### Extensions

There are several packaged extensions. These are traits that can be added to a
[Custom Browser](#custom-browser).

#### Authentication

This extension is more of an example. Each Symfony application handles authentication
differently but this is a good starting point. You can either override the methods
provided by the extension or write your own.

Add to your [Custom Browser](#custom-browser):

```php
namespace App\Tests;

use Zenstruck\Browser;
use Zenstruck\Browser\Extension\Authentication;

class AppBrowser extends Browser
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

#### Profiler

Provides access to Symfony's `Profiler`. Allows you to enable profiling for the next
request via `->withProfiling()` and access the profiler for the last request with
`->profile()`.

Add to your [Custom Browser](#custom-browser):

```php
namespace App\Tests;

use Zenstruck\Browser;
use Zenstruck\Browser\Extension\Profiler;

class AppBrowser extends Browser
{
    use Profiler;
}
```

Use in your tests:

```php
public function testDemo(): void
{
    $this->browser()
        ->withProfiling() // enable profiling for the next request
        ->visit('/my/page')
        ->with(function(Browser $browser) {
            // access the profiler
            $this->assertSame(10, $browser->profile()->getCollector('db')->getQueryCount());
        })
    ;
}
```

#### Email

Provides useful email assertions for a request (requires the [Profiler Extension](#profiler)).

Add to your [Custom Browser](#custom-browser):

```php
namespace App\Tests;

use Zenstruck\Browser;
use Zenstruck\Browser\Extension\Email;
use Zenstruck\Browser\Extension\Profiler;

class AppBrowser extends Browser
{
    use Profiler, Email;
}
```

Use in your tests:

```php
public function testDemo(): void
{
    $this->browser()
        ->withProfiling() // enable profiling for the next request
        ->visit('/page/that/does/not/send/email')
        ->assertNoEmailSent()

        ->visit('/page/that/sends/email')

        // just check that an email was sent to an address with a subject
        ->assertEmailSentTo('kevin@example.com', 'Email Subject')

        // advanced assertions
        ->assertEmailSentTo('kevin@example.com', function(\Zenstruck\Browser\Extension\Email\TestEmail $email) {
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
        })
    ;
}
```
