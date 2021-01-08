<?php

namespace Zenstruck\Browser\Tests;

use PHPUnit\Framework\TestCase;
use Zenstruck\Browser\HttpOptions;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class HttpOptionsTest extends TestCase
{
    /**
     * @test
     */
    public function defaults(): void
    {
        $options = new HttpOptions();

        $this->assertEmpty($options->query());
        $this->assertEmpty($options->files());
        $this->assertEmpty($options->server());
        $this->assertNull($options->body());
    }

    /**
     * @test
     */
    public function can_configure_with_constructor_array(): void
    {
        $options = new HttpOptions([
            'headers' => ['header' => 'header value'],
            'query' => $expectedQuery = ['param' => 'param value'],
            'files' => $expectedFiles = ['file' => 'file value'],
            'server' => ['server' => 'server value'],
            'body' => $expectedBody = 'body value',
            'json' => null,
            'ajax' => false,
        ]);

        $this->assertSame($expectedQuery, $options->query());
        $this->assertSame($expectedFiles, $options->files());
        $this->assertSame(['server' => 'server value', 'HTTP_HEADER' => 'header value'], $options->server());
        $this->assertSame($expectedBody, $options->body());
    }

    /**
     * @test
     */
    public function can_configure_via_withers(): void
    {
        $options = (new HttpOptions())
            ->withBody($expectedBody = 'body value')
            ->withHeaders(['header1' => 'header1 value'])
            ->withHeader('header2', 'header2 value')
            ->withFiles($expectedFiles = ['file' => 'file value'])
            ->withServer(['server' => 'server value'])
            ->withQuery($expectedQuery = ['param' => 'param value'])
        ;

        $this->assertSame($expectedQuery, $options->query());
        $this->assertSame($expectedFiles, $options->files());
        $this->assertSame($expectedBody, $options->body());
        $this->assertSame(
            [
                'server' => 'server value',
                'HTTP_HEADER1' => 'header1 value',
                'HTTP_HEADER2' => 'header2 value',
            ],
            $options->server()
        );
    }

    /**
     * @test
     */
    public function can_configure_json_and_ajax_with_constructor_array(): void
    {
        $options = new HttpOptions([
            'headers' => ['header' => 'header value'],
            'server' => ['server' => 'server value'],
            'body' => 'not used',
            'json' => $json = ['json' => 'body'],
            'ajax' => true,
        ]);

        $expectedServer = [
            'server' => 'server value',
            'HTTP_HEADER' => 'header value',
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ];

        $this->assertSame(\json_encode($json), $options->body());
        $this->assertSame($expectedServer, $options->server());
    }

    /**
     * @test
     */
    public function ajax_constructor(): void
    {
        $options = HttpOptions::ajax();

        $this->assertNull($options->body());
        $this->assertSame(
            [
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            ],
            $options->server()
        );
    }

    /**
     * @test
     */
    public function json_constructor_with_value(): void
    {
        $options = HttpOptions::json('value');

        $this->assertSame('"value"', $options->body());
        $this->assertSame(
            [
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
            ],
            $options->server()
        );
    }

    /**
     * @test
     */
    public function json_constructor_with_no_value(): void
    {
        $options = HttpOptions::json();

        $this->assertNull($options->body());
        $this->assertSame(
            [
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
            ],
            $options->server()
        );
    }

    /**
     * @test
     */
    public function json_ajax_constructor_with_value(): void
    {
        $options = HttpOptions::jsonAjax('value');

        $this->assertSame('"value"', $options->body());
        $this->assertSame(
            [
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            ],
            $options->server()
        );
    }

    /**
     * @test
     */
    public function json_ajax_constructor_with_no_value(): void
    {
        $options = HttpOptions::jsonAjax();

        $this->assertNull($options->body());
        $this->assertSame(
            [
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            ],
            $options->server()
        );
    }

    /**
     * @test
     */
    public function create_with_self(): void
    {
        $options = new class() extends HttpOptions {};

        $this->assertSame($options, HttpOptions::create($options));
    }

    /**
     * @test
     */
    public function can_merge_with_array(): void
    {
        $options = HttpOptions::create([
            'headers' => ['header1' => 'header1 value'],
            'query' => $expectedQuery = ['param' => 'param value'],
            'files' => $expectedFiles = ['file' => 'file value'],
            'server' => ['server' => 'server value'],
            'body' => null,
            'json' => null,
            'ajax' => true,
        ]);
        $options = $options->merge([
            'headers' => ['header2' => 'header2 value'],
            'json' => $json = ['json' => 'body'],
        ]);

        $this->assertSame($expectedQuery, $options->query());
        $this->assertSame($expectedFiles, $options->files());
        $this->assertSame(\json_encode($json), $options->body());
        $this->assertSame(
            [
                'server' => 'server value',
                'HTTP_HEADER1' => 'header1 value',
                'HTTP_HEADER2' => 'header2 value',
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            ],
            $options->server()
        );
    }

    /**
     * @test
     */
    public function can_merge_with_http_options_object(): void
    {
        $options = HttpOptions::create([
            'headers' => ['header1' => 'header1 value'],
            'query' => $expectedQuery = ['param' => 'param value'],
            'files' => $expectedFiles = ['file' => 'file value'],
            'server' => ['server' => 'server value'],
            'body' => null,
            'json' => null,
            'ajax' => true,
        ]);
        $options = $options->merge(new class(['headers' => ['header2' => 'header2 value']]) extends HttpOptions {});

        $this->assertSame($expectedQuery, $options->query());
        $this->assertSame($expectedFiles, $options->files());
        $this->assertNull($options->body());
        $this->assertSame(
            [
                'server' => 'server value',
                'HTTP_HEADER1' => 'header1 value',
                'HTTP_HEADER2' => 'header2 value',
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            ],
            $options->server()
        );
    }
}
