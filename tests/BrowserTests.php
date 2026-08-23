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
use Psr\Container\ContainerInterface;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\DataCollector\RequestDataCollector;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\VarDumper\VarDumper;
use Zenstruck\Assert;
use Zenstruck\Browser;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Browser\Tests\Fixture\TestComponent1;
use Zenstruck\Browser\Tests\Fixture\TestComponent2;
use Zenstruck\Callback\Exception\UnresolveableArgument;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait BrowserTests
{
    use HasBrowser {
        browser as kernelBrowser;
    }

    /**
     * @test
     */
    public function fails_if_trying_to_manipulate_exception_page(): void
    {
        Assert::that(function() {
            $this->browser()
                ->visit('/exception')
                ->click('foo')
            ;
        })->throws(AssertionFailedError::class, 'The last request threw an exception: Zenstruck\Browser\Tests\Fixture\CustomException - exception thrown');

        Assert::that(function() {
            $this->browser()
                ->visit('/exception')
                ->fillField('foo', 'bar')
            ;
        })->throws(AssertionFailedError::class, 'The last request threw an exception: Zenstruck\Browser\Tests\Fixture\CustomException - exception thrown');

        Assert::that(function() {
            $this->browser()
                ->visit('/exception')
                ->assertSee('foo')
            ;
        })->throws(AssertionFailedError::class, 'The last request threw an exception: Zenstruck\Browser\Tests\Fixture\CustomException - exception thrown');
    }

    /**
     * @test
     */
    public function can_enable_exception_throwing(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('exception thrown');

        $this->browser()
            ->throwExceptions()
            ->visit('/exception')
        ;
    }

    /**
     * @test
     */
    public function can_re_enable_catching_exceptions(): void
    {
        $browser = $this->browser();

        try {
            $browser->throwExceptions()->visit('/exception');
        } catch (\Exception $e) {
            $browser
                ->catchExceptions()
                ->visit('/exception')
                ->assertStatus(500)
            ;

            return;
        }

        $this->fail('Exception was not caught.');
    }

    /**
     * @test
     */
    public function exceptions_are_caught_by_default(): void
    {
        $this->browser()
            ->visit('/exception')
            ->assertStatus(500)
        ;
    }

    /**
     * @test
     */
    public function fails_if_expected_exception_not_thrown(): void
    {
        // visit
        Assert::that(
            function() {
                $this->browser()
                    ->expectException(\RuntimeException::class)
                    ->visit('/page1')
                ;
            },
        )
            ->throws(AssertionFailedError::class, 'No exception thrown. Expected "RuntimeException".')
        ;

        // click link
        Assert::that(
            function() {
                $this->browser()
                    ->visit('/page1')
                    ->expectException(\RuntimeException::class)
                    ->click('a link')
                ;
            },
        )
            ->throws(AssertionFailedError::class, 'No exception thrown. Expected "RuntimeException".')
        ;

        // submit form
        Assert::that(
            function() {
                $this->browser()
                    ->visit('/page1')
                    ->expectException(\RuntimeException::class)
                    ->click('Submit')
                ;
            },
        )
            ->throws(AssertionFailedError::class, 'No exception thrown. Expected "RuntimeException".')
        ;
    }

    /**
     * @test
     */
    public function can_expect_exception_for_form_submit(): void
    {
        $this->browser()
            ->visit('/page1')
            ->expectException(\RuntimeException::class, 'fail!')
            ->click('Submit Exception')
        ;
    }

    /**
     * @test
     */
    public function can_expect_exception_for_link_click(): void
    {
        $this->browser()
            ->visit('/page1')
            // a real browser gets no response for a request the kernel failed to answer, so it
            // stays where it was: the url after the click is not part of the shared behaviour
            ->expectException(\Exception::class, 'exception thrown')
            ->click('exception link')
        ;
    }

    /**
     * @test
     */
    public function multiple_browsers(): void
    {
        $browser1 = $this->browser()
            ->visit('/page1')
            ->assertOn('/page1')
        ;

        $browser2 = $this->browser()
            ->visit('/page2')
            ->assertOn('/page2')
        ;

        // this ensures a different browser is actually used
        $browser1->assertOn('/page1');
    }

    /**
     * @test
     */
    public function assert_on(): void
    {
        $this->browser()
            ->visit('/page1')
            ->assertOn('/page1')
            ->assertOn('http://www.example.com/page1')
            ->assertNotOn('/page2')
            ->assertNotOn('http://www.example.com/page1', ['path', 'host'])
            ->visit('/page1?foo=bar')
            ->assertOn('/page1?foo=bar')
            ->assertOn('/page1', ['path'])
            ->assertOn('/page1', ['path', 'fragment'])
            ->assertNotOn('/page1?foo=baz')
        ;
    }

    /**
     * @test
     *
     * @dataProvider encodedUrlProvider
     */
    public function assert_on_encoded($url, $expected): void
    {
        $this->browser()
            ->visit($url)
            ->assertOn($expected)
        ;
    }

    public static function encodedUrlProvider(): iterable
    {
        yield ['/page1?filter[q]=value', '/page1?filter[q]=value'];
        yield ['/page1?filter%5Bq%5D=value', '/page1?filter[q]=value'];
        yield ['/page1?filter[q]=value', '/page1?filter%5Bq%5D=value'];
        yield ['/page1#foo bar', '/page1#foo bar'];
        yield ['/page1#foo%20bar', '/page1#foo bar'];
        yield ['/page1#foo bar', '/page1#foo%20bar'];
        yield ['/page1#foo+bar', '/page1#foo bar'];
        yield ['/page1#foo bar', '/page1#foo+bar'];
    }

    /**
     * @test
     */
    public function can_use_current_browser(): void
    {
        $browser = $this->browser();

        $browser
            ->use(function(Browser $b) use ($browser) {
                $this->assertSame($b, $browser);

                $browser->visit('/redirect1');
            })
            ->assertOn('/page1')
            ->use(function() {
                $this->assertTrue(true);
            })
        ;
    }

    /**
     * @test
     */
    public function can_use_components(): void
    {
        $this->browser()
            ->use(static function(TestComponent1 $component) {
                $component->assertTitle('h1 title');
            })
            ->assertOn('/page1')
        ;
    }

    /**
     * @test
     */
    public function component_pre_assertions_and_actions_are_called(): void
    {
        $this->browser()
            ->use(function(TestComponent2 $component) {
                $this->assertTrue($component->preActionsCalled);
                $this->assertTrue($component->preAssertionsCalled);
            })
        ;
    }

    /**
     * @test
     */
    public function can_use_crawler(): void
    {
        $this->browser()
            ->visit('/page1')
            ->use(function(Crawler $crawler) {
                $this->assertSame('h1 title', $crawler->filter('h1')->text());
            })
        ;
    }

    /**
     * @test
     */
    public function can_use_cookie_jar(): void
    {
        $this->browser()
            ->visit('/page1?start-session=1')
            ->use(function(CookieJar $jar) {
                $this->assertInstanceOf(Cookie::class, $jar->get('MOCKSESSID'));
            })
        ;
    }

    /**
     * @test
     */
    public function can_manipulate_cookies(): void
    {
        $expires = \time() + 3600;

        $this->browser()
            ->visit('/page1?start-session=1')
            ->use(function(CookieJar $jar) use ($expires) {
                $this->assertNull($jar->get('not-a-cookie'));

                $jar->set(new Cookie('mine', 'a value', (string) $expires, '/', 'localhost', false, true, false, 'lax'));

                $cookie = $jar->get('mine');

                $this->assertNotNull($cookie);
                $this->assertSame('a value', $cookie->getValue());
                $this->assertSame($expires, (int) $cookie->getExpiresTime());
                $this->assertSame('/', $cookie->getPath());
                $this->assertTrue($cookie->isHttpOnly());
                $this->assertSame('lax', $cookie->getSameSite());
                $this->assertNotNull($jar->get('mine', '/', 'localhost'), 'visible for its own domain');
                $this->assertContains('mine', \array_map(static fn(Cookie $c) => $c->getName(), $jar->all()));

                // cookies are scoped to their path
                $jar->set(new Cookie('scoped', 'value', null, '/page1'));

                $this->assertNull($jar->get('scoped'), 'not visible at "/"');
                $this->assertNotNull($jar->get('scoped', '/page1'), 'visible at "/page1"');
                $this->assertNotNull($jar->get('scoped', '/page1/sub'), 'visible below "/page1"');

                $jar->expire('mine');

                $this->assertNull($jar->get('mine'));
                $this->assertNotNull($jar->get('MOCKSESSID'));

                $jar->clear();

                $this->assertNull($jar->get('MOCKSESSID'));
                $this->assertSame([], $jar->all());
            })
        ;
    }

    /**
     * @test
     */
    public function with_can_accept_multiple_browsers_and_components(): void
    {
        $browser = $this->browser();

        $browser
            ->use(function(Browser $browser1, $browser2, TestComponent1 $component1, TestComponent2 $component2, Crawler $crawler, AbstractBrowser $inner) use ($browser) {
                $this->assertInstanceOf(Browser::class, $browser1);
                $this->assertInstanceOf(Browser::class, $browser2);
                $this->assertInstanceOf($browser::class, $browser1);
                $this->assertInstanceOf($browser::class, $browser2);
                $this->assertInstanceOf(TestComponent1::class, $component1);
                $this->assertInstanceOf(TestComponent2::class, $component2);
            })
        ;
    }

    /**
     * @test
     */
    public function invalid_use_callback_parameter_throws_type_error(): void
    {
        $this->expectException(UnresolveableArgument::class);

        $this->browser()->use(static function(string $invalidType) {});
    }

    /**
     * @test
     */
    public function following_redirect_follows_all_by_default(): void
    {
        $this->browser()
            ->interceptRedirects()
            ->visit('/redirect1')
            ->assertOn('/redirect1')
            ->followRedirect()
            ->assertOn('/page1')
            ->assertSuccessful()
        ;
    }

    /**
     * @test
     */
    public function can_re_enable_following_redirects(): void
    {
        $this->browser()
            ->interceptRedirects()
            ->visit('/redirect1')
            ->assertOn('/redirect1')
            ->followRedirects()
            ->visit('/redirect1')
            ->assertOn('/page1')
        ;
    }

    /**
     * @test
     */
    public function calling_follow_redirects_when_the_response_is_a_redirect_follows_the_redirect(): void
    {
        $this->browser()
            ->interceptRedirects()
            ->visit('/redirect1')
            ->followRedirects()
            ->assertOn('/page1')
            ->interceptRedirects()
            ->visit('/page1')
            ->followRedirects()
            ->assertOn('/page1')
        ;
    }

    /**
     * @test
     */
    public function calling_follow_redirects_before_a_request_has_been_made_just_enables_following_redirects(): void
    {
        $this->browser()
            ->followRedirects()
            ->visit('/redirect1')
            ->assertOn('/page1')
        ;
    }

    /**
     * @test
     */
    public function can_limit_redirects_followed(): void
    {
        $this->browser()
            ->interceptRedirects()
            ->visit('/redirect1')
            ->assertOn('/redirect1')
            ->assertRedirected()
            ->followRedirect(1)
            ->assertOn('/redirect2')
            ->assertRedirected()
            ->followRedirect(1)
            ->assertOn('/redirect3')
            ->assertRedirected()
            ->followRedirect(1)
            ->assertOn('/page1')
            ->assertSuccessful()
        ;
    }

    /**
     * @test
     */
    public function assert_redirected_to_follows_all_redirects_by_default(): void
    {
        $this->browser()
            ->interceptRedirects()
            ->visit('/redirect1')
            ->assertRedirectedTo('/page1')
        ;
    }

    /**
     * @test
     */
    public function assert_redirected_to_can_configure_number_of_redirects_to_follow(): void
    {
        $this->browser()
            ->interceptRedirects()
            ->visit('/redirect1')
            ->assertRedirectedTo('/redirect2', 1)
        ;
    }

    /**
     * @test
     */
    public function exception_thrown_if_asserting_redirected_and_not_intercepting_redirects(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot assert redirected if not intercepting redirects. Call ->interceptRedirects() before making the request.');

        $this->browser()
            ->visit('/redirect1')
            ->assertRedirected()
        ;
    }

    /**
     * @test
     */
    public function exception_thrown_if_asserting_redirected_to_and_not_intercepting_redirects(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot assert redirected if not intercepting redirects. Call ->interceptRedirects() before making the request.');

        $this->browser()
            ->visit('/redirect1')
            ->assertRedirectedTo('/page1')
        ;
    }

    /**
     * @test
     */
    public function click_and_intercept(): void
    {
        $this->browser()
            ->visit('/page1')
            ->clickAndIntercept('Submit Redirect')
            ->assertOn('/submit-form')
            ->use(function(RequestDataCollector $collector) {
                $this->assertSame('/submit-form', $collector->getPathInfo());
            })
            ->assertRedirectedTo('/page1')
        ;
    }

    /**
     * @test
     */
    public function redirects_are_followed_by_default(): void
    {
        $this->browser()
            ->visit('/redirect1')
            ->assertOn('/page1')
        ;
    }

    /**
     * @test
     */
    public function response_header_assertions(): void
    {
        $this->browser()
            ->visit('/page1')
            ->assertHeaderEquals('Content-Type', 'text/html; charset=UTF-8')
            ->assertHeaderContains('Content-Type', 'text/html')
            ->assertHeaderEquals('X-Not-Present-Header', null)
        ;
    }

    /**
     * @test
     */
    public function response_status_assertions(): void
    {
        $this->browser()
            ->visit('/page1')
            ->assertStatus(200)
            ->assertSuccessful()
            ->assertContentType('text/html')
        ;
    }

    /**
     * @test
     */
    public function can_use_container_as_typehint(): void
    {
        $this->browser()
            ->use(function(ContainerInterface $container) {
                // a private service: only the test container can resolve it
                $this->assertTrue($container->has('security.token_storage'));
            })
        ;
    }

    /**
     * @test
     */
    public function can_act_as_user(): void
    {
        $this->browser()
            ->actingAs(new InMemoryUser('kevin', 'pass'))
            ->visit('/user')
            ->assertSee('user: kevin/pass')
        ;
    }

    /**
     * @test
     */
    public function can_make_authentication_assertions(): void
    {
        $username = 'kevin';
        $user = new InMemoryUser('kevin', 'pass');

        $this->browser()
            ->assertNotAuthenticated()
            ->actingAs($user)
            ->assertAuthenticated()
            ->assertAuthenticated($username)
            ->assertAuthenticated($user)
            ->visit('/user')
            ->assertAuthenticated()
            ->assertAuthenticated($username)
        ;
    }

    /**
     * @test
     */
    public function can_check_if_not_authenticated_after_request(): void
    {
        $this->browser()
            ->visit('/page1')
            ->assertNotAuthenticated()
            ->assertSeeIn('a', 'a link')
        ;
    }

    /**
     * @test
     */
    public function can_login_with_a_form_and_be_remembered(): void
    {
        $this->browser()
            ->visit('/login')
            ->fillField('Username', 'kevin')
            ->fillField('Password', 'pass')
            ->checkField('Remember me')
            ->click('Log in')
            ->assertOn('/page1')
            ->assertAuthenticated('kevin')
            ->visit('/user')
            ->assertSee('user: kevin/pass/'.UsernamePasswordToken::class)

            // dropping the session leaves only the remember-me cookie to authenticate with
            ->use(function(CookieJar $cookies) {
                $this->assertNotNull($cookies->get('REMEMBERME'));
                $cookies->expire('MOCKSESSID');
            })
            ->withProfiling() // required to trigger a security operation
            ->visit('/user')
            ->assertAuthenticated('kevin')
            ->assertSee('user: kevin/pass/'.RememberMeToken::class)
        ;
    }

    /**
     * @test
     */
    public function can_enable_the_profiler(): void
    {
        $profile = $this->browser()
            ->withProfiling()
            ->visit('/page1')
            ->profile()
        ;

        $this->assertTrue($profile->hasCollector('request'));
    }

    /**
     * @test
     */
    public function can_profile_multiple_requests(): void
    {
        $browser = $this->browser();

        $first = $browser->withProfiling()->visit('/page1')->profile();
        $second = $browser->withProfiling()->visit('/page2')->profile();

        $this->assertStringEndsWith('/page1', $first->getUrl() ?? '');
        $this->assertStringEndsWith('/page2', $second->getUrl() ?? '');
        $this->assertNotSame($first->getToken(), $second->getToken());
    }

    /**
     * @test
     */
    public function can_access_the_profiler(): void
    {
        $profile = $this->browser()
            ->withProfiling()
            ->visit('/page1')
            ->profile()
        ;

        $this->assertTrue($profile->hasCollector('request'));
    }

    /**
     * @test
     */
    public function can_use_data_collector(): void
    {
        $this->browser()
            ->withProfiling()
            ->visit('/page1')
            ->use(function(RequestDataCollector $collector) {
                $this->assertSame('/page1', $collector->getPathInfo());
            })
        ;
    }

    /**
     * @test
     */
    public function content_assertions(): void
    {
        $this->browser()
            ->visit('/page1')
            ->assertContains('h1 title')
            ->assertNotContains('invalid text')
        ;
    }

    /**
     * @test
     */
    public function can_dump_response(): void
    {
        $output = self::catchVarDumperOutput(function() {
            $this->browser()
                ->visit('/page1')
                ->dump()
            ;
        });

        $this->assertStringContainsString('/page1', $output[0]);
        $this->assertStringContainsString('<html', $output[0]);
        $this->assertStringContainsString('<h1>h1 title</h1>', $output[0]);
    }

    /**
     * @test
     */
    public function can_save_source(): void
    {
        $contents = self::catchFileContents(__DIR__.'/../var/browser/source/source.txt', function() {
            $this->browser()
                ->visit('/page1')
                ->saveSource('source.txt')
            ;
        });

        $this->assertStringContainsString('<html', $contents);
        $this->assertStringContainsString('<h1>h1 title</h1>', $contents);
    }

    /**
     * @test
     */
    public function can_save_source_as_zip(): void
    {
        $contents = self::catchFileContents(__DIR__.'/../var/browser/source/attachment.zip', function() {
            $this->browser()
                ->visit('/zip')
                ->saveSource('attachment.zip')
            ;
        });

        $this->assertEquals(
            \file_get_contents(__DIR__.'/../var/browser/source/attachment.zip'),
            $contents,
        );
    }

    /**
     * @test
     */
    public function html_assertions(): void
    {
        $this->browser()
            ->visit('/page1')
            ->assertSee('h1 title')
            ->assertNotSee('invalid text')
            ->assertSeeIn('h1', 'title')
            ->assertNotSeeIn('h1', 'invalid text')
            ->assertSeeElement('h1')
            ->assertNotSeeElement('h2')
            ->assertElementCount('ul li', 2)
        ;
    }

    /**
     * @test
     */
    public function html_head_assertions(): void
    {
        $this->browser()
            ->visit('/page1')
            ->assertSeeIn('title', 'meta title')
            ->assertElementAttributeContains('meta[name="description"]', 'content', 'meta')
            ->assertElementAttributeNotContains('meta[name="description"]', 'content', 'invalid')
            ->assertElementAttributeContains('html', 'lang', 'en')
        ;
    }

    /**
     * @test
     */
    public function form_assertions(): void
    {
        $this->browser()
            ->visit('/page1')
            ->assertFieldEquals('Input 1', 'input 1')
            ->assertFieldEquals('input1', 'input 1')
            ->assertFieldEquals('input_1', 'input 1')
            ->assertFieldNotEquals('Input 1', 'invalid')
            ->assertFieldNotEquals('input1', 'invalid')
            ->assertFieldNotEquals('input_1', 'invalid')
            ->assertChecked('Input 3')
            ->assertChecked('input3')
            ->assertChecked('input_3')
            ->assertNotChecked('Input 2')
            ->assertNotChecked('input2')
            ->assertNotChecked('input_2')
            ->assertSelected('Input 4', 'option 1')
            ->assertSelected('input4', 'option 1')
            ->assertSelected('input_4', 'option 1')
            ->assertSelected('Input 7', 'option 1')
            ->assertSelected('input7', 'option 1')
            ->assertSelected('input_7[]', 'option 1')
            ->assertSelected('Input 7', 'option 3')
            ->assertSelected('input7', 'option 3')
            ->assertSelected('input_7[]', 'option 3')
            ->assertNotSelected('Input 4', 'option 2')
            ->assertNotSelected('input4', 'option 2')
            ->assertNotSelected('input_4', 'option 2')
            ->assertNotSelected('Input 7', 'option 2')
            ->assertNotSelected('input7', 'option 2')
            ->assertNotSelected('input_7[]', 'option 2')
            ->assertNotSelected('input_8', 'option 1')
            ->assertSelected('input_8', 'option 2')
            ->assertNotChecked('Radio 1')
            ->assertNotChecked('radio1')
            ->assertNotChecked('Radio 3')
            ->assertNotChecked('radio3')
            ->assertChecked('Radio 2')
            ->assertChecked('radio2')
        ;
    }

    /**
     * @test
     */
    public function link_action(): void
    {
        $this->browser()
            ->visit('/page1')
            ->click('a link')
            ->assertOn('/page2')
        ;
    }

    /**
     * @test
     */
    public function click_on_element(): void
    {
        $this->browser()
            ->visit('/page1')
            ->click('#link a')
            ->assertOn('/page2')
        ;
    }

    /**
     * @test
     */
    public function form_actions_by_field_label(): void
    {
        $this->browser()
            ->visit('/page1')
            ->fillField('Input 1', 'Kevin')
            ->checkField('Input 2')
            ->uncheckField('Input 3')
            ->selectFieldOption('Input 4', 'option 2')
            ->attachFile('Input 5', new \SplFileInfo(__FILE__))
            ->selectFieldOptions('Input 6', ['option 1', 'option 3'])
            ->selectFieldOptions('Input 7', [])
            ->checkField('Radio 3')
            ->click('Submit')
            ->assertOn('/submit-form')
            ->assertContains('"input_1":"Kevin"')
            ->assertContains('"input_2":"on"')
            ->assertNotContains('"input_3')
            ->assertContains('"input_4":"option 2"')
            ->assertContains(\sprintf('"input_5":"%s"', \pathinfo(__FILE__, \PATHINFO_BASENAME)))
            ->assertContains('"input_6":["option 1","option 3"]')
            ->assertNotContains('"input_7')
            ->assertContains('"input_8":"option 3"')
        ;
    }

    /**
     * @test
     */
    public function form_actions_by_field_id(): void
    {
        $this->browser()
            ->visit('/page1')
            ->fillField('input1', 'Kevin')
            ->checkField('input2')
            ->uncheckField('input3')
            ->selectFieldOption('input4', 'option 2')
            ->attachFile('input5', __FILE__)
            ->selectFieldOptions('input6', ['option 1', 'option 3'])
            ->selectFieldOptions('input7', [])
            ->checkField('radio3')
            ->click('Submit')
            ->assertOn('/submit-form')
            ->assertContains('"input_1":"Kevin"')
            ->assertContains('"input_2":"on"')
            ->assertNotContains('"input_3')
            ->assertContains('"input_4":"option 2"')
            ->assertContains(\sprintf('"input_5":"%s"', \pathinfo(__FILE__, \PATHINFO_BASENAME)))
            ->assertContains('"input_6":["option 1","option 3"]')
            ->assertNotContains('"input_7')
            ->assertContains('"input_8":"option 3"')
        ;
    }

    /**
     * @test
     */
    public function form_actions_by_field_name(): void
    {
        $this->browser()
            ->visit('/page1')
            ->fillField('input_1', 'Kevin')
            ->checkField('input_2')
            ->uncheckField('input_3')
            ->selectFieldOption('input_4', 'option 2')
            ->attachFile('input_5', __FILE__)
            ->selectFieldOptions('input_6[]', ['option 1', 'option 3'])
            ->selectFieldOption('input_8', 'option 3')
            ->click('Submit')
            ->assertOn('/submit-form')
            ->assertContains('"input_1":"Kevin"')
            ->assertContains('"input_2":"on"')
            ->assertNotContains('"input_3')
            ->assertContains('"input_4":"option 2"')
            ->assertContains(\sprintf('"input_5":"%s"', \pathinfo(__FILE__, \PATHINFO_BASENAME)))
            ->assertContains('"input_6":["option 1","option 3"]')
            ->assertContains('"input_8":"option 3"')
        ;
    }

    /**
     * @test
     */
    public function select_field(): void
    {
        $this->browser()
            ->visit('/page1')
            ->selectField('Input 2')
            ->selectField('Input 4', 'option 2')
            ->selectField('Input 6', ['option 1', 'option 3'])
            ->selectField('Radio 3')
            ->click('Submit')
            ->assertOn('/submit-form')
            ->assertContains('"input_2":"on"')
            ->assertContains('"input_4":"option 2"')
            ->assertContains('"input_6":["option 1","option 3"]')
            ->assertContains('"input_8":"option 3"')
        ;
    }

    /**
     * @test
     */
    public function can_submit_form_with_different_submit_buttons(): void
    {
        // Submit and Submit B, have the same field name but different values
        // Submit C has a different field name (and value)

        $this->browser()
            ->visit('/page1')
            ->click('Submit')
            ->assertOn('/submit-form')
            ->assertContains('"submit_1":"a"')
            ->assertNotContains('submit_2')
            ->visit('/page1')
            ->click('Submit B')
            ->assertOn('/submit-form')
            ->assertContains('"submit_1":"b"')
            ->assertNotContains('submit_2')
            ->visit('/page1')
            ->click('Submit C')
            ->assertOn('/submit-form')
            ->assertContains('"submit_2":"c"')
            ->assertNotContains('submit_1')
            ->visit('/page1')
            ->click('Submit D')
            ->assertOn('/submit-form')
            ->assertContains('"submit_2":"d"')
            ->assertNotContains('submit_1')
        ;
    }

    /**
     * @see https://github.com/zenstruck/browser/issues/55
     *
     * @test
     */
    public function can_submit_filled_form_with_different_submit_buttons(): void
    {
        // Submit and Submit B, have the same field name but different values
        // Submit C has a different field name (and value)

        $this->browser()
            ->visit('/page1')
            ->fillField('input_1', 'Kevin')
            ->click('Submit')
            ->assertOn('/submit-form')
            ->assertContains('"input_1":"Kevin"')
            ->assertContains('"submit_1":"a"')
            ->assertNotContains('submit_2')
            ->visit('/page1')
            ->fillField('input_1', 'Kevin')
            ->click('Submit B')
            ->assertOn('/submit-form')
            ->assertContains('"input_1":"Kevin"')
            ->assertContains('"submit_1":"b"')
            ->assertNotContains('submit_2')
            ->visit('/page1')
            ->fillField('input_1', 'Kevin')
            ->click('Submit C')
            ->assertOn('/submit-form')
            ->assertContains('"input_1":"Kevin"')
            ->assertContains('"submit_2":"c"')
            ->assertNotContains('submit_1')
            ->visit('/page1')
            ->fillField('input_1', 'Kevin')
            ->click('Submit D')
            ->assertOn('/submit-form')
            ->assertContains('"input_1":"Kevin"')
            ->assertContains('"submit_2":"d"')
            ->assertNotContains('submit_1')
        ;
    }

    /**
     * @test
     */
    public function cannot_attach_file_that_does_not_exist(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->browser()
            ->visit('/page1')
            ->attachFile('Input 5', '/invalid/file')
        ;
    }

    /**
     * @test
     */
    public function can_attach_multiple_files(): void
    {
        $this->browser()
            ->visit('/page1')
            ->attachFile('Input 9', [__DIR__.'/Fixture/files/attachment.txt', new \SplFileInfo(__DIR__.'/Fixture/files/xml.xml')])
            ->click('Submit')
            ->assertContains('"input_9":["attachment.txt","xml.xml"]')
        ;
    }

    /**
     * @test
     */
    public function cannot_attach_multiple_files_to_a_non_multiple_input(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->browser()
            ->visit('/page1')
            ->attachFile('Input 5', [__DIR__.'/Fixture/files/attachment.txt', __DIR__.'/Fixture/files/xml.xml'])
        ;
    }

    /**
     * @test
     */
    public function can_dump_html_element(): void
    {
        $output = self::catchVarDumperOutput(function() {
            $this->browser()
                ->visit('/page1')
                ->dump('p#link')
            ;
        });

        $this->assertCount(1, $output);
        $this->assertSame('<p id="link"><a href="/page2">a link</a> not a link</p>', $output[0]);
    }

    /**
     * @test
     */
    public function if_dump_selector_matches_multiple_elements_all_are_dumped(): void
    {
        $output = self::catchVarDumperOutput(function() {
            $this->browser()
                ->visit('/page1')
                ->dump('li')
            ;
        });

        $this->assertCount(2, $output);
        $this->assertSame('<li>list 1</li>', $output[0]);
        $this->assertSame('<li>list 2</li>', $output[1]);
    }

    /**
     * @test
     */
    public function can_access_the_html_crawler(): void
    {
        $crawler = $this->browser()
            ->visit('/page1')
            ->crawler()
            ->filter('ul li')
        ;

        $this->assertCount(2, $crawler);
    }

    /**
     * @test
     */
    public function can_get_content(): void
    {
        $content = $this->browser()->visit('/text')->content();

        $this->assertStringContainsString('text content', $content);
    }

    protected static function catchFileContents(string $expectedFile, callable $callback): string
    {
        (new Filesystem())->remove($expectedFile);

        $callback();

        self::assertFileExists($expectedFile);

        return \file_get_contents($expectedFile);
    }

    protected static function catchVarDumperOutput(callable $callback): array
    {
        $output[] = null;

        VarDumper::setHandler(static function($var) use (&$output) {
            $output[] = $var;
        });

        $callback();

        // reset to default handler
        VarDumper::setHandler(null);

        // a null value is added to the beginning
        return \array_values(\array_filter($output));
    }

    abstract protected function browser(): Browser;
}
