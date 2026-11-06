<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Tests;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Playwright\Exception\PlaywrightException;
use Zenstruck\Assert;
use Zenstruck\Browser\PlaywrightBrowser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait PlaywrightWaitingTests
{
    /**
     * @test
     */
    #[Test]
    public function actions_wait_for_elements_that_are_not_there_yet(): void
    {
        $this->browserWithUpdate('', <<<'JS'
            document.body.insertAdjacentHTML('beforeend', `
                <label for="late-input">Late field</label>
                <input id="late-input">
                <button onclick="document.getElementById('late-output').textContent = document.getElementById('late-input').value">Late button</button>
                <p id="late-output"></p>
            `);
            JS)
            ->fillField('Late field', 'typed')
            ->click('Late button')
            ->assertSeeIn('#late-output', 'typed')
        ;
    }

    /**
     * @test
     */
    #[Test]
    public function assertions_wait_for_elements_that_are_not_there_yet(): void
    {
        $this->browserWithUpdate('', "document.body.insertAdjacentHTML('beforeend', '<button id=late-button>late button</button>');")
            ->assertSeeElement('#late-button')
            ->assertSee('late button')
            ->assertElementCount('#late-button', 1)
        ;
    }

    /**
     * @test
     */
    #[Test]
    public function negative_assertions_wait_for_the_element_to_go_away(): void
    {
        $this->browserWithUpdate('<p id="doomed">Contents of doomed box</p>', "document.getElementById('doomed').remove();")
            ->assertNotSeeElement('#doomed')
            ->assertNotSee('Contents of doomed box')
        ;
    }

    /**
     * @test
     */
    #[Test]
    public function clicks_wait_for_a_link_added_later(): void
    {
        $this->browserWithUpdate('', "document.body.insertAdjacentHTML('beforeend', '<a href=/nav/target>Late link</a>');")
            ->click('Late link')
            ->assertOn('/nav/target')
        ;
    }

    /**
     * @test
     *
     * @dataProvider delayedAssertions
     */
    #[Test]
    #[DataProvider('delayedAssertions')]
    public function assertions_wait_for_a_delayed_update(string $initial, string $update, string $assertion, array $arguments): void
    {
        $browser = $this->browserWithUpdate($initial, $update);
        $browser->{$assertion}(...$arguments);
    }

    public static function delayedAssertions(): iterable
    {
        yield 'text appears' => ['<p id="changing">waiting</p>', "document.getElementById('changing').textContent = 'Ready';", 'assertSeeIn', ['#changing', 'ready']];
        yield 'page text appears' => ['', "document.body.insertAdjacentHTML('beforeend', '<p>Ready now</p>');", 'assertSee', ['ready now']];
        yield 'text disappears' => ['<p id="changing">waiting</p>', "document.getElementById('changing').textContent = 'ready';", 'assertNotSeeIn', ['#changing', 'waiting']];
        yield 'page text disappears' => ['<p id="changing">Contents of doomed box</p>', "document.getElementById('changing').remove();", 'assertNotSee', ['Contents of doomed box']];
        yield 'element count changes' => ['<p class="counted">first</p>', "document.body.insertAdjacentHTML('beforeend', '<p class=counted>second</p>');", 'assertElementCount', ['.counted', 2]];
        yield 'attribute changes' => ['<p id="changing" data-state="waiting"></p>', "document.getElementById('changing').dataset.state = 'READY';", 'assertElementAttributeContains', ['#changing', 'data-state', 'ready']];
        yield 'field value changes' => ['<input id="changing" value="waiting">', "document.getElementById('changing').value = 'READY';", 'assertFieldEquals', ['changing', 'ready']];
        yield 'field value stops matching' => ['<input id="changing" value="waiting">', "document.getElementById('changing').value = 'ready';", 'assertFieldNotEquals', ['changing', 'waiting']];
        yield 'selection changes' => ['<select id="changing"><option>waiting</option><option>ready</option></select>', "document.getElementById('changing').value = 'ready';", 'assertSelected', ['changing', 'ready']];
        yield 'checkbox changes' => ['<input id="changing" type="checkbox">', "document.getElementById('changing').checked = true;", 'assertChecked', ['changing']];
        yield 'element becomes visible' => ['<p id="changing" hidden>ready</p>', "document.getElementById('changing').hidden = false;", 'assertVisible', ['#changing']];
        yield 'element becomes hidden' => ['<p id="changing">ready</p>', "document.getElementById('changing').hidden = true;", 'assertNotVisible', ['#changing']];
        yield 'url changes' => ['', "history.pushState({}, '', '/changed');", 'assertOn', ['/changed']];
        yield 'url stops matching' => ['', "history.pushState({}, '', '/changed');", 'assertNotOn', ['/nav']];
        yield 'encoded url changes' => ['', "history.pushState({}, '', '/changed?currency=%E2%82%AC&progress=100%');", 'assertOn', ['/changed?currency=€&progress=100%']];
    }

    /**
     * @test
     */
    #[Test]
    public function negative_assertions_that_already_hold_return_immediately(): void
    {
        $this->withDefaultTimeout(5000, function(): void {
            $browser = $this->browser()->visit('/nav');
            $start = \hrtime(true);

            $browser
                ->assertNotSeeElement('#never-exists')
                ->assertElementCount('#never-exists', 0)
                ->assertNotVisible('#never-exists')
            ;

            $this->assertLessThan(1.0, (\hrtime(true) - $start) / 1e9);
        });
    }

    /**
     * @test
     */
    #[Test]
    public function the_timeout_can_be_changed_at_runtime(): void
    {
        $browser = $this->browserWithUpdate('', "document.body.insertAdjacentHTML('beforeend', '<button id=late-button>late button</button>');", 500);
        $browser->timeout(100);

        Assert::that(static fn() => $browser->assertSeeElement('#late-button'))->throws(AssertionFailedError::class);

        $browser->timeout(5000)->assertSeeElement('#late-button');
    }

    /**
     * @test
     */
    #[Test]
    public function the_auto_wait_timeout_is_configurable(): void
    {
        $this->withDefaultTimeout(250, function(): void {
            $browser = $this->browserWithUpdate('', "document.body.insertAdjacentHTML('beforeend', '<button id=late-button>late button</button>');", 1000);
            $start = \hrtime(true);

            Assert::that(static fn() => $browser->assertSeeElement('#late-button'))->throws(AssertionFailedError::class);

            $this->assertLessThan(0.75, (\hrtime(true) - $start) / 1e9);
        });
    }

    /**
     * @test
     */
    #[Test]
    public function an_expired_wait_preserves_the_assertion_message(): void
    {
        $browser = $this->browser()->visit('/nav')->timeout(50);
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('The text "never appears" was not found in the text of the element matching css "h1".');

        $browser->assertSeeIn('h1', 'never appears');
    }

    /**
     * @test
     *
     * @dataProvider invalidTimeouts
     */
    #[Test]
    #[DataProvider('invalidTimeouts')]
    public function the_timeout_cannot_be_zero_or_negative(int $timeout): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('Timeout must be a positive number of milliseconds, "%d" given.', $timeout));

        $this->browser()->timeout($timeout);
    }

    public static function invalidTimeouts(): iterable
    {
        yield 'zero' => [0];
        yield 'negative' => [-1];
    }

    /**
     * @test
     *
     * @dataProvider actionsWithTimeout
     */
    #[Test]
    #[DataProvider('actionsWithTimeout')]
    public function the_actionability_wait_uses_the_configured_timeout(string $action, array $arguments): void
    {
        $browser = $this->browserWithUpdate('<button disabled>Disabled button</button><input id="disabled-field" disabled>', '');
        $browser->timeout(50);
        $start = \hrtime(true);

        Assert::that(static fn() => $browser->{$action}(...$arguments))->throws(PlaywrightException::class, 'Timeout 50ms exceeded');

        $this->assertLessThan(1.0, (\hrtime(true) - $start) / 1e9);
    }

    public static function actionsWithTimeout(): iterable
    {
        yield 'click' => ['click', ['Disabled button']];
        yield 'fill' => ['fillField', ['disabled-field', 'typed']];
    }

    /**
     * @test
     *
     * @dataProvider navigationActions
     */
    #[Test]
    #[DataProvider('navigationActions')]
    public function full_page_navigation_keeps_its_own_timeout(string $action, string $event): void
    {
        $browser = $this->browser()->visit('/nav')->timeout(250);
        $page = $browser->client()->getPage();
        $page->evaluate('() => { document.querySelector("a").href = "/nav/target?delay=750"; }');

        if ('click' !== $action) {
            $page->evaluate(<<<'JS'
                event => {
                    const link = document.querySelector('a');
                    link.onclick = event => event.preventDefault();
                    link['on' + event] = () => { location.href = link.href; };
                }
                JS, $event);
        }

        $browser->{$action}('advance link');
        $browser->timeout(2000)->assertOn('/nav/target?delay=750')->assertSee('navigation target');
    }

    public static function navigationActions(): iterable
    {
        yield 'click' => ['click', 'click'];
        yield 'double click' => ['doubleClick', 'dblclick'];
        yield 'right click' => ['rightClick', 'contextmenu'];
    }

    private function browserWithUpdate(string $initial, string $update, int $delay = 200): PlaywrightBrowser
    {
        $browser = $this->browser()->visit('/nav');
        $page = $browser->client()->getPage();
        $page->evaluate('html => document.body.insertAdjacentHTML("beforeend", html)', $initial);
        $page->evaluate("() => { setTimeout(() => { {$update} }, {$delay}); }");

        return $browser;
    }

    private function withDefaultTimeout(int $timeout, callable $test): void
    {
        $previous = $_SERVER['BROWSER_DEFAULT_TIMEOUT'] ?? null;
        $_SERVER['BROWSER_DEFAULT_TIMEOUT'] = (string) $timeout;

        try {
            $test();
        } finally {
            unset($_SERVER['BROWSER_DEFAULT_TIMEOUT']);

            if (null !== $previous) {
                $_SERVER['BROWSER_DEFAULT_TIMEOUT'] = $previous;
            }
        }
    }
}
