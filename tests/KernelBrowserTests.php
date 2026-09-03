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

use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerInterface;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
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
    #[Test]
    public function can_write_to_the_session_before_making_a_request(): void
    {
        $this->browser()
            ->use(function(SessionInterface $session) {
                $session->set('key', 'set by the test');
            })
            ->visit('/read-session')
            ->assertContains('set by the test')
        ;
    }

    /**
     * @test
     */
    #[Test]
    public function can_read_the_session_written_by_the_application(): void
    {
        $this->browser()
            ->visit('/page1?start-session=1')
            ->use(function(SessionInterface $session) {
                $this->assertSame('value', $session->get('key'));
            })
        ;
    }

    /**
     * @test
     */
    #[Test]
    public function session_written_by_the_test_is_visible_to_the_application_and_back(): void
    {
        $this->browser()
            ->visit('/page1?start-session=1')
            ->use(function(SessionInterface $session) {
                $this->assertSame('value', $session->get('key'));

                $session->set('key', 'overwritten');
            })
            ->visit('/read-session')
            ->assertContains('overwritten')
            ->use(function(SessionInterface $session) {
                $this->assertSame('overwritten', $session->get('key'));
            })
        ;
    }

    /**
     * The session id must not change when the test only reads or updates it, otherwise
     * anything the application had stored under the previous id would be dropped.
     *
     * @test
     */
    #[Test]
    public function using_the_session_keeps_the_existing_session_cookie(): void
    {
        $id = null;

        $this->browser()
            ->visit('/page1?start-session=1')
            ->use(function(CookieJar $jar) use (&$id) {
                $id = $jar->get('MOCKSESSID')?->getValue();

                $this->assertNotNull($id);
            })
            ->use(function(SessionInterface $session) use (&$id) {
                $this->assertSame($id, $session->getId());

                $session->set('key', 'still the same session');
            })
            ->use(function(CookieJar $jar) use (&$id) {
                $this->assertSame($id, $jar->get('MOCKSESSID')?->getValue());
            })
            ->visit('/read-session')
            ->assertContains('still the same session')
        ;
    }

    /**
     * @test
     */
    #[Test]
    public function session_started_by_the_test_is_available_on_any_host(): void
    {
        $this->browser()
            ->use(function(SessionInterface $session) {
                $session->set('key', 'no domain on the cookie');
            })
            ->visit('http://example.test/read-session')
            ->assertContains('no domain on the cookie')
        ;
    }

    /**
     * @test
     */
    #[Test]
    public function can_use_kernel_browser_as_typehint(): void
    {
        $this->browser()
            ->use(static function(KernelBrowser $browser) {
                $browser->visit('/redirect1');
            })
            ->assertOn('/page1')
        ;
    }

    /**
     * @test
     */
    #[Test]
    public function reboots_the_kernel_between_requests_by_default(): void
    {
        $containers = [];
        $collect = static function(ContainerInterface $container) use (&$containers): void {
            $containers[] = $container;
        };

        $this->browser()
            ->visit('/page1')->use($collect)
            ->visit('/page2')->use($collect)
        ;

        // a reboot rebuilds the container, so two requests cannot share one
        $this->assertNotSame($containers[0], $containers[1]);
    }

    /**
     * @test
     */
    #[Test]
    public function can_disable_reboot(): void
    {
        $containers = [];
        $collect = static function(ContainerInterface $container) use (&$containers): void {
            $containers[] = $container;
        };

        $this->browser()
            ->disableReboot()
            ->visit('/page1')->use($collect)
            ->visit('/page2')->use($collect)
        ;

        $this->assertSame($containers[0], $containers[1]);
    }

    /**
     * @test
     */
    #[Test]
    public function can_re_enable_reboot(): void
    {
        $containers = [];
        $collect = static function(ContainerInterface $container) use (&$containers): void {
            $containers[] = $container;
        };

        $this->browser()
            ->disableReboot()
            ->visit('/page1')->use($collect)
            ->enableReboot()
            ->visit('/page2')->use($collect)
        ;

        $this->assertNotSame($containers[0], $containers[1]);
    }

    /**
     * @test
     */
    #[Test]
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
    #[Test]
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
    #[Test]
    public function default_http_options_are_not_mutated_by_request_options(): void
    {
        $this->browser()
            ->setDefaultHttpOptions(['headers' => ['x-foo' => 'bar']])
            ->post('/http-method', [
                'headers' => ['x-foo' => 'baz'],
                'query' => ['q1' => 'qv1'],
                'json' => ['b1' => 'bv1'],
                'ajax' => true,
            ])
            ->assertContains('"x-foo":["Baz"]')
            ->assertContains('"query":{"q1":"qv1"}')
            ->assertContains('"content":{"b1":"bv1"}')
            ->assertContains('"ajax":true')
            ->post('/http-method')
            ->assertContains('"x-foo":["Bar"]')
            ->assertContains('"query":[]')
            ->assertContains('"content":null')
            ->assertContains('"ajax":false')
        ;
    }

    /**
     * @test
     */
    #[Test]
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
    #[Test]
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
    #[Test]
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
    #[Test]
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
    #[Test]
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
    #[Test]
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
    #[Test]
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
    #[Test]
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
    #[Test]
    public function can_save_formatted_json_source(): void
    {
        $file = self::uniqueFilename('source.txt');
        $contents = self::catchFileContents(__DIR__.'/../var/browser/source/'.$file, function() use ($file) {
            $this->browser()
                ->visit('/http-method')
                ->saveSource('/'.$file)
            ;
        });

        $this->assertStringContainsString('/http-method', $contents);
        $this->assertStringContainsString('    "content": null,', $contents);
    }

    /**
     * @test
     */
    #[Test]
    public function can_save_source_when_exception(): void
    {
        $file = self::uniqueFilename('source.txt');
        $contents = self::catchFileContents(__DIR__.'/../var/browser/source/'.$file, function() use ($file) {
            $this->browser()
                ->visit('/invalid-page')
                ->assertStatus(404)
                ->saveSource('/'.$file)
            ;
        });

        $this->assertStringContainsString('No route found for', $contents);
    }

    /**
     * @test
     */
    #[Test]
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
    #[Test]
    public function can_use_json_object(): void
    {
        $this->browser()
            ->post('/json', ['json' => ['foo' => 'bar']])
            ->assertSuccessful()
            ->use(static function(Json $json) {
                $json->assertMatches('foo', 'bar');
            })
        ;
    }

    /**
     * @test
     */
    #[Test]
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
    #[Test]
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
    #[Test]
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

    protected function browser(): KernelBrowser
    {
        return $this->kernelBrowser();
    }
}
