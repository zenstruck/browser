<?php

namespace Zenstruck\Browser\Tests\Fixture;

use PHPUnit\Framework\Assert as PHPUnit;
use Zenstruck\Browser\Component\Email\TestEmail;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CustomTestEmail extends TestEmail
{
    public function assertHasPostmarkTag(string $expected): self
    {
        PHPUnit::assertTrue($this->getHeaders()->has('X-PM-Tag'));
        PHPUnit::assertSame($expected, $this->getHeaders()->get('X-PM-Tag')->getBodyAsString());

        return $this;
    }
}
