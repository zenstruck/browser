<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Internal;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Zenstruck\Browser\ApiBrowser;
use Zenstruck\Browser\KernelBrowser;
use Zenstruck\Browser\Session;

/**
 * @internal
 */
final class BrowserState
{
    /** @var string[] */
    public array $savedSources = [];

    public ?SessionInterface $httpSession = null;
    public ?ApiBrowser $apiBrowser = null;
    public ?KernelBrowser $htmlBrowser = null;

    /**
     * @param array<string,mixed> $options
     */
    public function __construct(public readonly Session $session, public readonly array $options)
    {
    }
}
