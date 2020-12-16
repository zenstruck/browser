<?php

namespace Zenstruck\Browser\Tests;

use Zenstruck\Browser\HttpOptions;
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
            ->assertResponseContains('"method":"GET"')
            ->post('/http-method')
            ->assertSuccessful()
            ->assertResponseContains('"method":"POST"')
            ->delete('/http-method')
            ->assertSuccessful()
            ->assertResponseContains('"method":"DELETE"')
            ->put('/http-method')
            ->assertSuccessful()
            ->assertResponseContains('"method":"PUT"')
            ->assertResponseContains('"ajax":false')
            ->post('/http-method', [
                'json' => ['foo' => 'bar'],
                'headers' => ['X-Foo' => 'Bar'],
                'ajax' => true,
            ])
            ->assertResponseContains('"content-type":["application\/json"]')
            ->assertResponseContains('"x-foo":["Bar"]')
            ->assertResponseContains('"content":"{\u0022foo\u0022:\u0022bar\u0022}"')
            ->assertResponseContains('"ajax":true')
            ->post('/http-method', HttpOptions::jsonAjax(['foo' => 'bar'])->withHeader('X-Foo', 'Bar'))
            ->assertResponseContains('"content-type":["application\/json"]')
            ->assertResponseContains('"x-foo":["Bar"]')
            ->assertResponseContains('"content":"{\u0022foo\u0022:\u0022bar\u0022}"')
            ->assertResponseContains('"ajax":true')
            ->post('/http-method', CustomHttpOptions::api('my-token'))
            ->assertResponseContains('"content-type":["application\/json"]')
            ->assertResponseContains('"x-token":["my-token"]')
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
