<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zenstruck\Browser\Modifier;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[CoversClass(Modifier::class)]
final class ModifierTest extends TestCase
{
    #[Test]
    #[DataProvider('modifiers')]
    public function normalize(Modifier|string $modifier, Modifier $expected): void
    {
        $this->assertSame($expected, Modifier::normalize($modifier));
    }

    /**
     * @return iterable<string, array{Modifier|string, Modifier}>
     */
    public static function modifiers(): iterable
    {
        yield 'case' => [Modifier::Shift, Modifier::Shift];
        yield 'value' => ['Shift', Modifier::Shift];
        yield 'lowercase' => ['shift', Modifier::Shift];
        yield 'uppercase' => ['SHIFT', Modifier::Shift];
        yield 'mixed case' => ['controlOrMeta', Modifier::ControlOrMeta];
        yield 'alt' => ['alt', Modifier::Alt];
        yield 'control' => ['control', Modifier::Control];
        yield 'meta' => ['meta', Modifier::Meta];
    }

    #[Test]
    public function normalize_throws_for_an_invalid_modifier(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid modifier "ctrl", expected one of "Alt", "Control", "Meta", "Shift", "ControlOrMeta".');

        Modifier::normalize('ctrl');
    }

    #[Test]
    public function plus_a_key(): void
    {
        $this->assertSame('Shift+Enter', Modifier::Shift->plus('Enter'));
    }

    #[Test]
    public function plus_another_modifier_and_a_key(): void
    {
        $this->assertSame('Control+Shift+Tab', Modifier::Control->plus(Modifier::Shift, 'Tab'));
    }
}
