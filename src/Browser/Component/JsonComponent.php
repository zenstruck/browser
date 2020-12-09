<?php

namespace Zenstruck\Browser\Component;

use PHPUnit\Framework\Assert as PHPUnit;
use Zenstruck\Browser\Component;
use function JmesPath\search;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class JsonComponent extends Component
{
    /**
     * @param string $expression JMESPath expression
     * @param mixed  $expected
     */
    public function assertMatches(string $expression, $expected): self
    {
        if (!\function_exists('JmesPath\search')) {
            throw new \RuntimeException('mtdowling/jmespath.php requires (composer require --dev mtdowling/jmespath.php).');
        }

        $data = \json_decode($this->browser()->inner()->getInternalResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        PHPUnit::assertSame($expected, search($expression, $data));

        return $this;
    }

    protected function preAssertions(): void
    {
        $this->browser()->assertHeaderContains('Content-Type', 'application/json');
    }
}
