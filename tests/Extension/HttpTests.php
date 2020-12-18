<?php

namespace Zenstruck\Browser\Tests\Extension;

use Zenstruck\Browser\Extension\Http\HttpOptions;
use Zenstruck\Browser\Tests\Fixture\CustomHttpOptions;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait HttpTests
{
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
}
