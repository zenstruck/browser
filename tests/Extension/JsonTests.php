<?php

namespace Zenstruck\Browser\Tests\Extension;

use Symfony\Component\VarDumper\VarDumper;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait JsonTests
{
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

    /**
     * @test
     */
    public function can_dump_json_response_as_array(): void
    {
        $dumpedValues[] = null;

        VarDumper::setHandler(function($var) use (&$dumpedValues) {
            $dumpedValues[] = $var;
        });

        $this->browser()
            ->post('/json', ['json' => ['foo' => 'bar']])
            ->dump()
        ;

        VarDumper::setHandler();

        // a null value is added to the beginning
        $dumped = \array_values(\array_filter($dumpedValues))[0];

        $this->assertStringContainsString('    "foo": "bar"', $dumped);
    }

    /**
     * @test
     */
    public function can_dump_json_array_key(): void
    {
        $dumpedValues[] = null;

        VarDumper::setHandler(function($var) use (&$dumpedValues) {
            $dumpedValues[] = $var;
        });

        $this->browser()
            ->post('/json', ['json' => ['foo' => 'bar']])
            ->dump('foo')
        ;

        VarDumper::setHandler();

        // a null value is added to the beginning
        $dumped = \array_values(\array_filter($dumpedValues))[0];

        $this->assertSame('bar', $dumped);
    }

    /**
     * @test
     */
    public function can_dump_json_path_expression(): void
    {
        $dumpedValues[] = null;

        VarDumper::setHandler(function($var) use (&$dumpedValues) {
            $dumpedValues[] = $var;
        });

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

        VarDumper::setHandler();

        // a null value is added to the beginning
        $dumped = \array_values(\array_filter($dumpedValues))[0];

        $this->assertSame([1, 2, 3], $dumped);
    }

    /**
     * @test
     */
    public function can_save_formatted_json_source(): void
    {
        $file = __DIR__.'/../../var/browser/source/source.txt';

        if (\file_exists($file)) {
            \unlink($file);
        }

        $this->browser()
            ->visit('/http-method')
            ->saveSource('/source.txt')
        ;

        $this->assertFileExists($file);

        $contents = \file_get_contents($file);

        $this->assertStringContainsString('/http-method', $contents);
        $this->assertStringContainsString('    "content": "",', $contents);

        \unlink($file);
    }
}
