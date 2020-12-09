<?php

namespace Zenstruck\Browser\Tests\Extension;

use Zenstruck\Browser;

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
        $this->createJsonBrowser()
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

    abstract protected function createJsonBrowser(): Browser;
}
