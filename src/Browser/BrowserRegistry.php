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

use Zenstruck\Browser;

/**
 * Tracks {@see Browser} instances created during a single test or scenario so
 * that the lifecycle hooks ({@see Artifact\ArtifactCollector}) can dump their
 * state on failure and collect their saved artifacts on finish.
 *
 * @author Hugo Hamon <hello@kodero.fr>
 */
final class BrowserRegistry
{
    private static ?self $default = null;

    /** @var list<Browser> */
    private array $browsers = [];
    private bool $started = false;

    public static function default(): self
    {
        return self::$default ??= new self();
    }

    /**
     * @internal
     */
    public static function resetDefault(): void
    {
        self::$default = null;
    }

    public function start(): void
    {
        $this->started = true;
    }

    public function stop(): void
    {
        $this->started = false;
        $this->browsers = [];
    }

    public function isStarted(): bool
    {
        return $this->started;
    }

    public function register(Browser $browser): void
    {
        if (!$this->started) {
            return;
        }

        $this->browsers[] = $browser;
    }

    /**
     * @return list<Browser>
     */
    public function all(): array
    {
        return $this->browsers;
    }

    public function clear(): void
    {
        $this->browsers = [];
    }

    public function isEmpty(): bool
    {
        return $this->browsers === [];
    }
}
