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
use Symfony\Component\HttpKernel\DataCollector\RequestDataCollector;
use Zenstruck\Assert;
use Zenstruck\Browser\HttpOptions;
use Zenstruck\Browser\Json;
use Zenstruck\Browser\KernelBrowser;
use Zenstruck\Browser\Tests\Fixture\CustomHttpOptions;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait KernelBrowserTests
{
    use BrowserTests;

    /**
     * @test
     */
    public function can_use_kernel_browser_as_typehint(): void
    {
        $this->browser()
            ->use(function(KernelBrowser $browser) {
                $browser->visit('/redirect1');
            })
            ->assertOn('/page1')
        ;
    }

    /**
     * @test
     */
    public function can_use_container_as_typehint(): void
    {
        $browser = $this->browser();
        $c = $browser->client()->getContainer();

        $browser
            ->use(function(ContainerInterface $container) use ($c) {
                $this->assertSame($c, $container);
            })
        ;
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
    public function http_method_actions(): void
    {
        $this->browser()
            ->get('/http-method')
            ->assertSuccessful()
            ->assertContains('"method":"GET"')
            ->post('/http-method')
            ->assertSuccessful()
            ->assertContains('"method":"POST"')
            ->patch('/http-method')
            ->assertSuccessful()
            ->assertContains('"method":"PATCH"')
            ->delete('/http-method')
            ->assertSuccessful()
            ->assertContains('"method":"DELETE"')
            ->put('/http-method')
            ->assertSuccessful()
            ->assertContains('"method":"PUT"')
            ->assertContains('"ajax":false')
            ->post('/http-method', [
                'json' => ['foo' => 'bar'],
                'headers' => ['X-Foo' => 'Bar'],
                'ajax' => true,
            ])
            ->assertContains('"content-type":["application\/json"]')
            ->assertContains('"x-foo":["Bar"]')
            ->assertContains('"content":{"foo":"bar"}')
            ->assertContains('"ajax":true')
            ->post('/http-method', HttpOptions::jsonAjax(['foo' => 'bar'])->withHeader('X-Foo', 'Bar'))
            ->assertContains('"content-type":["application\/json"]')
            ->assertContains('"x-foo":["Bar"]')
            ->assertContains('"content":{"foo":"bar"}')
            ->assertContains('"ajax":true')
            ->post('/http-method', CustomHttpOptions::api('my-token'))
            ->assertContains('"content-type":["application\/json"]')
            ->assertContains('"x-token":["my-token"]')
            ->post('/http-method', HttpOptions::json()->withHeader('content-type', 'application/ld+json'))
            ->assertContains('"content-type":["application\/ld+json"]')
            ->post('/http-method?q1=qv1')
            ->assertContains('"query":{"q1":"qv1"}')
            ->post('/http-method', ['query' => ['q1' => 'qv1']])
            ->assertContains('"query":{"q1":"qv1"}')
            ->post('/http-method?q1=qv1', ['query' => ['q2' => 'qv2']])
            ->assertContains('"query":{"q1":"qv1","q2":"qv2"}')
            ->post('/http-method', ['body' => ['b1' => 'bv1']])
            ->assertContains('"request":{"b1":"bv1"}')
        ;
    }

    /**
     * @test
     */
    public function can_set_default_http_options(): void
    {
        $this->browser()
            ->setDefaultHttpOptions(['headers' => ['x-foo' => 'bar']])
            ->post('/http-method')
            ->assertContains('"x-foo":["Bar"]')
            ->post('/http-method', ['headers' => ['x-bar' => 'foo']])
            ->assertContains('"x-bar":["Foo"]')
            ->assertContains('"x-foo":["Bar"]')
        ;
    }

    /**
     * @test
     */
    public function can_handle_any_content_type(): void
    {
        $this->browser()
            ->get('/text')
            ->assertHeaderContains('content-type', 'text/plain')
            ->assertSuccessful()
            ->assertStatus(200)
            ->assertContains('text content')
        ;
    }

    /**
     * @test
     */
    public function can_assert_json_matches(): void
    {
        $this->browser()
            ->post('/json', ['json' => [
                'foo' => [
                    'bar' => ['baz' => 1],
                    'bam' => ['baz' => 2],
                    'boo' => ['baz' => 3],
                ],
                '@some:count' => 6,
            ]])
            ->assertJson()
            ->assertJsonMatches('foo.bar.baz', 1)
            ->assertJsonMatches('foo.*.baz', [1, 2, 3])
            ->assertJsonMatches('length(foo)', 3)
            ->assertJsonMatches('"@some:count"', 6)
        ;
    }

    /**
     * @test
     */
    public function assert_content_types(): void
    {
        $this->browser()
            ->get('/json')
            ->assertSuccessful()
            ->assertJson()
            ->get('/xml')
            ->assertXml()
            ->get('/page1')
            ->assertHtml()
            ->get('/zip')
            ->assertContentType('zip')
        ;
    }

    /**
     * @test
     */
    public function can_dump_empty_json_request(): void
    {
        $output = self::catchVarDumperOutput(function() {
            $this->browser()
                ->post('/json')
                ->dump()
            ;
        });

        $this->assertStringContainsString('content-type: application/json', $output[0]);
    }

    /**
     * @test
     */
    public function can_dump_json_response_as_array(): void
    {
        $output = self::catchVarDumperOutput(function() {
            $this->browser()
                ->post('/json', ['json' => ['foo' => 'bar']])
                ->dump()
            ;
        });

        $this->assertStringContainsString('    "foo": "bar"', $output[0]);
    }

    /**
     * @test
     */
    public function dump_includes_headers_and_status(): void
    {
        $output = self::catchVarDumperOutput(function() {
            $this->browser()
                ->visit('/page1')
                ->dump()
            ;
        });

        $this->assertStringContainsString('(200)', $output[0]);
        $this->assertStringContainsString('content-type: text/html;', $output[0]);
    }

    /**
     * @test
     */
    public function can_dump_json_array_key(): void
    {
        $output = self::catchVarDumperOutput(function() {
            $this->browser()
                ->post('/json', ['json' => ['foo' => 'bar']])
                ->dump('foo')
            ;
        });

        $this->assertSame('bar', $output[0]);
    }

    /**
     * @test
     */
    public function can_dump_json_path_expression(): void
    {
        $output = self::catchVarDumperOutput(function() {
            $this->browser()
                ->post('/json', ['json' => [
                    'foo' => [
                        'bar' => ['baz' => 1],
                        'bam' => ['baz' => 2],
                        'boo' => ['baz' => 3],
                    ],
                ]])
                ->dump('foo.*.baz')
            ;
        });

        $this->assertSame([1, 2, 3], $output[0]);
    }

    /**
     * @test
     */
    public function can_save_formatted_json_source(): void
    {
        $contents = self::catchFileContents(__DIR__.'/../var/browser/source/source.txt', function() {
            $this->browser()
                ->visit('/http-method')
                ->saveSource('/source.txt')
            ;
        });

        $this->assertStringContainsString('/http-method', $contents);
        $this->assertStringContainsString('    "content": null,', $contents);
    }

    /**
     * @test
     */
    public function can_save_source_when_exception(): void
    {
        $contents = self::catchFileContents(__DIR__.'/../var/browser/source/source.txt', function() {
            $this->browser()
                ->visit('/invalid-page')
                ->assertStatus(404)
                ->saveSource('/source.txt')
            ;
        });

        $this->assertStringContainsString('No route found for', $contents);
    }

    /**
     * @test
     */
    public function can_access_json_object(): void
    {
        $json = $this->browser()
            ->post('/json', ['json' => $expected = ['foo' => 'bar']])
            ->assertSuccessful()
            ->json()
        ;

        $this->assertSame($expected, $json->decoded());
    }

    /**
     * @test
     */
    public function can_use_json_object(): void
    {
        $this->browser()
            ->post('/json', ['json' => ['foo' => 'bar']])
            ->assertSuccessful()
            ->use(function(Json $json) {
                $json->assertMatches('foo', 'bar');
            })
        ;
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
    public function can_dump_xml_selector(): void
    {
        $output = self::catchVarDumperOutput(function() {
            $this->browser()
                ->visit('/xml')
                ->dump('url loc')
            ;
        });

        $this->assertCount(2, $output);
        $this->assertSame('<loc>https://www.example.com/page1</loc>', $output[0]);
        $this->assertSame('<loc attr="attribute">https://www.example.com/page2</loc>', $output[1]);
    }

    /**
     * @test
     */
    public function can_access_the_xml_crawler(): void
    {
        $crawler = $this->browser()
            ->visit('/xml')
            ->crawler()
            ->filter('url loc')
        ;

        $this->assertCount(2, $crawler);
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
    public function can_expect_exception_for_http_request(): void
    {
        $this->browser()
            ->expectException(\Exception::class)
            ->visit('/exception')
            ->visit('/page1')
            ->assertSuccessful()
            ->expectException(\Exception::class, 'exception thrown')
            ->post('/exception')
            ->expectException(function(\Throwable $e) {
                $this->assertSame('exception thrown', $e->getMessage());
            })
            ->put('/exception')
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
            ->expectException(\Exception::class, 'exception thrown')
            ->click('exception link')
            ->assertOn('/exception')
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
    public function fails_if_expected_exception_not_thrown(): void
    {
        // http request
        Assert::that(
            function() {
                $this->browser()
                    ->expectException(\RuntimeException::class)
                    ->get('/page1')
                ;
            }
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
            }
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
            }
        )
            ->throws(AssertionFailedError::class, 'No exception thrown. Expected "RuntimeException".')
        ;
    }

    protected function browser(): KernelBrowser
    {
        return $this->kernelBrowser();
    }
}
