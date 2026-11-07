# Behat

Browser uses Symfony's kernel for ordinary scenarios and Playwright for scenarios tagged `@javascript`.
Both run the application through the kernel, so you can test without starting a web server.

## Setup

With Browser installed, add Behat, MinkExtension and the Symfony integration.

```console
composer require --dev \
    'behat/behat:^3.22|^4.0' \
    friends-of-behat/symfony-extension \
    'friends-of-behat/mink-extension:^2.7|^3.0@alpha' \
    behat/mink-browserkit-driver
```

Enable `framework.test` in your test environment and add this entry to `config/bundles.php`.

```php
FriendsOfBehat\SymfonyExtension\Bundle\FriendsOfBehatSymfonyExtensionBundle::class => ['test' => true],
```

Create `behat.php` in your project root.

```php
<?php

use Behat\Config\Config;
use Behat\Config\Extension;
use Behat\Config\Profile;
use Behat\Config\Suite;
use Behat\MinkExtension\Context\MinkContext;
use Behat\MinkExtension\ServiceContainer\MinkExtension;
use FriendsOfBehat\SymfonyExtension\ServiceContainer\SymfonyExtension;
use Zenstruck\Browser\Behat\BrowserExtension;
use Zenstruck\Browser\Behat\BrowserSteps;

return (new Config())->withProfile(
    (new Profile('default'))
        ->withExtension(new Extension(SymfonyExtension::class))
        ->withExtension(new Extension(MinkExtension::class, [
            'base_url' => 'http://localhost',
            'sessions' => [
                'kernel' => ['zenstruck_kernel' => null],
                'playwright' => ['zenstruck_playwright' => null],
            ],
        ]))
        ->withExtension(new Extension(BrowserExtension::class))
        ->withSuite((new Suite('default'))
            ->withPaths('features')
            ->withContexts(MinkContext::class, BrowserSteps::class))
);
```

Behat 3.22 and later support this PHP config. If you're migrating from YAML, run
`vendor/bin/behat --convert-config` on Behat 3 before upgrading to Behat 4.

## Running scenarios

Run `vendor/bin/behat`. MinkExtension selects `KernelBrowser` by default. Tag a scenario, feature
or Examples table with `@javascript` to use `PlaywrightBrowser`. JavaScript scenarios require
the [Playwright installation](../README.md#playwrightbrowser). They share a browser process, with
isolated cookies and storage for each scenario.

```gherkin
Feature: Greeting
    Scenario: Read the greeting
        Given I am on "/"
        Then I should see "Hello"

    @javascript
    Scenario: Update the greeting
        Given I am on "/"
        When I press "Update"
        Then I should see "Rendered by JavaScript"
```

On Playwright, literal text and HTML checks, element presence and counts, form assertions, and
URL path and fragment comparisons wait for the page to update. Regular expression and HTTP status
checks run immediately.

## Step reference

The setup above registers stock steps from `MinkContext` and the additional steps marked
**BrowserSteps**. Use the steps with either driver unless a table says otherwise. **n/a** means
there is no equivalent step in either context.

| Shared Browser API | Behat step |
| --- | --- |
| `visit()` | `Given I am on "/path"` or `When I go to "/path"` |
| `click()` | `When I press "button"` or `When I follow "link"`. **BrowserSteps** adds `When I click the "#save" element` for CSS selectors. BrowserKit supports links and submit or reset buttons. |
| `fillField()` | `When I fill in "field" with "value"` |
| `checkField()`, `uncheckField()` | `When I check "field"` / `When I uncheck "field"` for checkboxes |
| `selectField()`, `selectFieldOption()` | `When I select "value" from "field"` for a select or radio field. For a checkbox, use `When I check "field"`. |
| `selectFieldOptions()` | Repeat `When I additionally select "value" from "field"`. **BrowserSteps** adds `When I clear the "field" selection` for multi-selects. |
| `attachFile()` | `When I attach the file "path" to "field"` for one file |
| `assertOn()` | `Then I should be on "/path"`. Mink ignores the query string. |
| `assertNotOn()` | **BrowserSteps** `Then I should not be on "/path"`. Like the stock URL step, this ignores the query string. |
| `assertContains()`, `assertNotContains()` | `Then the response should contain "text"` / `Then the response should not contain "text"` |
| `assertSee()`, `assertNotSee()` | `Then I should see "text"` / `Then I should not see "text"` |
| `assertSeeIn()`, `assertNotSeeIn()` | `Then I should see "text" in the "#selector" element` / `Then I should not see "text" in the "#selector" element` |
| `assertSeeElement()`, `assertNotSeeElement()` | `Then I should see a "#selector" element` / `Then I should not see a "#selector" element` |
| `assertElementCount()` | `Then I should see 3 ".item" elements` |
| `assertElementAttributeContains()`, `assertElementAttributeNotContains()` | **BrowserSteps** `Then the "#save" element's "class" attribute should contain "active"` / `Then the "#save" element's "class" attribute should not contain "active"` |
| `assertFieldEquals()`, `assertFieldNotEquals()` | `Then the "field" field should contain "value"` / `Then the "field" field should not contain "value"`. These compare the whole value. |
| `assertSelected()`, `assertNotSelected()` | **BrowserSteps** `Then "admin" should be selected in "Roles"` / `Then "admin" should not be selected in "Roles"`. Use the option value. |
| `assertChecked()`, `assertNotChecked()` | `Then the "field" checkbox should be checked` / `Then the "field" checkbox should be unchecked` |
| `assertStatus()` | `Then the response status code should be 200` |
| `assertSuccessful()` | **BrowserSteps** `Then the response should be successful` (any `2xx` status) |
| `assertHeaderEquals()`, `assertHeaderContains()` | **BrowserSteps** `Then the "Content-Type" response header should equal "application/json"` / `Then the "Content-Type" response header should contain "json"` |
| `assertContentType()` | **BrowserSteps** `Then the "Content-Type" response header should contain "json"` |
| `actingAs()`, `assertAuthenticated()`, `assertNotAuthenticated()` | n/a |
| `interceptRedirects()`, `followRedirects()`, `followRedirect()`, `clickAndIntercept()`, `assertRedirected()`, `assertRedirectedTo()` | n/a |
| `throwExceptions()`, `catchExceptions()`, `expectException()`, `withProfiling()` | n/a |
| `dump()` | `Then print last response` for a full response dump |
| `saveSource()`, `saveCurrentState()`, `dd()` | n/a |

| BrowserKit only (`KernelBrowser`) | Behat step |
| --- | --- |
| `get()`, `request('GET', ...)` | `Given I am on "/path"` for a plain GET. Request options are n/a. |
| `post()`, `put()`, `patch()`, `delete()`, `request()` with other methods | n/a for direct HTTP requests |
| `setDefaultHttpOptions()`, `disableReboot()`, `enableReboot()` | n/a |
| `assertJson()`, `assertXml()`, `assertHtml()` | **BrowserSteps** `Then the "Content-Type" response header should contain "json"` (or `"xml"` or `"html"`) |
| `assertJsonMatches()` | n/a |

| Playwright only (`PlaywrightBrowser`) | Behat step |
| --- | --- |
| `waitUntilSeeIn()`, `waitUntilNotSeeIn()` | The scoped text assertions above auto-wait and are the closest match. The negative step requires the element to exist. |
| `assertVisible()`, `assertNotVisible()`, `waitUntilVisible()`, `waitUntilNotVisible()` | **BrowserSteps** `Then the "#toast" element should be visible` / `Then the "#toast" element should not be visible`. Both auto-wait. |
| `doubleClick()`, `rightClick()` | **BrowserSteps** `When I double-click the "#row" element` / `When I right-click the "#row" element` |
| `timeout()`, `wait()`, `pause()` | n/a |
| `takeScreenshot()`, `ddScreenshot()`, `saveConsoleLog()`, `dumpConsoleLog()`, `ddConsoleLog()` | n/a |

## Custom steps

Create your own context class by extending `BrowserContext`, as shown in the `FeatureContext`
example below. Use `$this->browser()` for `KernelBrowser` in ordinary scenarios
and `$this->playwrightBrowser()` for `PlaywrightBrowser` in `@javascript` scenarios. Both use
the active Mink session, so custom and stock Mink steps share the same page and state. Calling
a helper with a different active driver throws a `LogicException`.

```php
<?php

use Behat\Step\Then;
use Zenstruck\Browser\Behat\BrowserContext;

final class FeatureContext extends BrowserContext
{
    #[Then('the cart should contain :count items')]
    public function assertCartItems(int $count): void
    {
        $browser = $this->browser();
        $browser->assertElementCount('.cart-item', $count);
    }

    #[Then('the cart should be visible')]
    public function assertCartVisible(): void
    {
        $browser = $this->playwrightBrowser();
        $browser->assertVisible('#cart');
    }
}
```

Register your custom context class in the suite's `withContexts()` call alongside
`MinkContext::class` and `BrowserSteps::class`. For the example above, add `FeatureContext::class`.
SymfonyExtension also supports registering contexts as Symfony services for constructor dependency
injection.
