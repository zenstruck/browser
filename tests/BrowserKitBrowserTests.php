<?php

namespace Zenstruck\Browser\Tests;

use Zenstruck\Browser\HttpOptions;
use Zenstruck\Browser\Tests\Fixture\CustomHttpOptions;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait BrowserKitBrowserTests
{
    use BrowserTests;

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
            ->assertContains('"content":"{\u0022foo\u0022:\u0022bar\u0022}"')
            ->assertContains('"ajax":true')
            ->post('/http-method', HttpOptions::jsonAjax(['foo' => 'bar'])->withHeader('X-Foo', 'Bar'))
            ->assertContains('"content-type":["application\/json"]')
            ->assertContains('"x-foo":["Bar"]')
            ->assertContains('"content":"{\u0022foo\u0022:\u0022bar\u0022}"')
            ->assertContains('"ajax":true')
            ->post('/http-method', CustomHttpOptions::api('my-token'))
            ->assertContains('"content-type":["application\/json"]')
            ->assertContains('"x-token":["my-token"]')
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
            ]])
            ->assertJson()
            ->assertJsonMatches('foo.bar.baz', 1)
            ->assertJsonMatches('foo.*.baz', [1, 2, 3])
            ->assertJsonMatches('length(foo)', 3)
        ;
    }

    /**
     * @test
     */
    public function can_access_decoded_json(): void
    {
        $json = $this->browser()
            ->post('/json', ['json' => $expected = ['foo' => 'bar']])
            ->assertSuccessful()
            ->assertJson()
            ->json()
        ;

        $this->assertSame($expected, $json);
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
        $this->assertStringContainsString('    "content": "",', $contents);
    }

    /**
     * @test
     */
    public function can_access_the_profiler(): void
    {
        $profile = $this->browser()
            ->visit('/page1')
            ->profile()
        ;

        $this->assertTrue($profile->hasCollector('request'));
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
    public function can_dump_xml_selector(): void
    {
        $output = self::catchVarDumperOutput(function() {
            $this->browser()
                ->visit('/xml')
                ->dump('url loc')
            ;
        });

        $this->assertCount(2, $output);
        $this->assertSame('https://www.example.com/page1', $output[0]);
        $this->assertSame('https://www.example.com/page2', $output[1]);
    }
}
