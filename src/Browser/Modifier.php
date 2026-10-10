<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser;

/**
 * A key held down during a click or key press.
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
enum Modifier: string
{
    case Alt = 'Alt';
    case Control = 'Control';
    case Meta = 'Meta';
    case Shift = 'Shift';

    /**
     * Meta on macOS, Control elsewhere: the platform's own multi-select key.
     */
    case ControlOrMeta = 'ControlOrMeta';

    /**
     * @param self|string $modifier a case or its value, in any letter case ("shift")
     */
    public static function normalize(self|string $modifier): self
    {
        if ($modifier instanceof self) {
            return $modifier;
        }

        $values = \array_map(static fn(self $case) => $case->value, self::cases());
        $cases = \array_combine(\array_map('strtolower', $values), self::cases());

        return $cases[\mb_strtolower($modifier)] ?? throw new \InvalidArgumentException(\sprintf('Invalid modifier "%s", expected one of "%s".', $modifier, \implode('", "', $values)));
    }

    /**
     * A key combination for {@see PlaywrightBrowser::press()}, e.g. "Shift+Enter".
     */
    public function plus(self|string $key, self|string ...$keys): string
    {
        $keys = \array_map(static fn(self|string $key) => $key instanceof self ? $key->value : $key, [$key, ...$keys]);

        return \implode('+', [$this->value, ...$keys]);
    }
}
