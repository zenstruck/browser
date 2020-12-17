<?php

namespace Zenstruck\Browser\Tests;

use Zenstruck\Browser\Extension\Http\HttpOptions;
use Zenstruck\Browser\Tests\Fixture\CustomHttpOptions;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait BrowserKitBrowserTests
{
    /**
     * @test
     */
    public function can_intercept_redirects(): void
    {
        $this->browser()
            ->interceptRedirects()
            ->visit('/redirect1')
            ->assertOn('/redirect1')
            ->assertRedirected()
            ->followRedirect()
            ->assertOn('/redirect2')
            ->assertRedirected()
            ->followRedirect()
            ->assertOn('/redirect3')
            ->assertRedirected()
            ->followRedirect()
            ->assertOn('/page1')
            ->assertSuccessful()
        ;
    }

    /**
     * @test
     */
    public function can_assert_redirected_to(): void
    {
        $this->browser()
            ->interceptRedirects()
            ->visit('/redirect3')
            ->assertRedirectedTo('/page1')
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
            ->assertJsonMatches('foo.bar.baz', 1)
            ->assertJsonMatches('foo.*.baz', [1, 2, 3])
            ->assertJsonMatches('length(foo)', 3)
        ;
    }
}
