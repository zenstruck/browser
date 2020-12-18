<?php

namespace Zenstruck\Browser\Tests\Extension\Http;

use PHPUnit\Framework\TestCase;
use Zenstruck\Browser\Extension\Http\HttpOptions;

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

        $this->assertEmpty($options->parameters());
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
            'parameters' => $expectedParameters = ['param' => 'param value'],
            'files' => $expectedFiles = ['file' => 'file value'],
            'server' => ['server' => 'server value'],
            'body' => $expectedBody = 'body value',
            'json' => null,
            'ajax' => false,
        ]);

        $this->assertSame($expectedParameters, $options->parameters());
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
            ->withHeaders(['header' => 'header value'])
            ->withFiles($expectedFiles = ['file' => 'file value'])
            ->withServer(['server' => 'server value'])
            ->withParameters($expectedParameters = ['param' => 'param value'])
        ;

        $this->assertSame($expectedParameters, $options->parameters());
        $this->assertSame($expectedFiles, $options->files());
        $this->assertSame(['server' => 'server value', 'HTTP_HEADER' => 'header value'], $options->server());
        $this->assertSame($expectedBody, $options->body());
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
            'CONTENT_TYPE' => ['application/json'],
            'HTTP_ACCEPT' => ['application/json'],
            'HTTP_X_REQUESTED_WITH' => ['XMLHttpRequest'],
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
                'HTTP_X_REQUESTED_WITH' => ['XMLHttpRequest'],
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
                'CONTENT_TYPE' => ['application/json'],
                'HTTP_ACCEPT' => ['application/json'],
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
                'CONTENT_TYPE' => ['application/json'],
                'HTTP_ACCEPT' => ['application/json'],
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
                'CONTENT_TYPE' => ['application/json'],
                'HTTP_ACCEPT' => ['application/json'],
                'HTTP_X_REQUESTED_WITH' => ['XMLHttpRequest'],
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
                'CONTENT_TYPE' => ['application/json'],
                'HTTP_ACCEPT' => ['application/json'],
                'HTTP_X_REQUESTED_WITH' => ['XMLHttpRequest'],
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
}
