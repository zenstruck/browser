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

use Symfony\Component\BrowserKit\AbstractBrowser;
use Zenstruck\Dom;
use Zenstruck\Dom\Session as DomSession;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
abstract class Session implements DomSession
{
    public function __construct(private AbstractBrowser $client)
    {
    }

    final public function client(): AbstractBrowser
    {
        return $this->client;
    }

    abstract public function dom(): Dom;

    abstract public function content(): string;

    abstract public function currentUrl(): string;
}
