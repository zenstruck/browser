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
use Zenstruck\Dom\Inspector;
use Zenstruck\Dom\Node;
use Zenstruck\Dom\Node\Form\Field\Checkbox;
use Zenstruck\Dom\Node\Form\Field\File;
use Zenstruck\Dom\Node\Form\Field\Input;
use Zenstruck\Dom\Node\Form\Field\Radio;
use Zenstruck\Dom\Node\Form\Field\Select\Multiselect;
use Zenstruck\Dom\Node\Form\Field\Select\Option;
use Zenstruck\Dom\Node\Form\Field\Textarea;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
abstract class Session implements Inspector
{
    public function __construct(private AbstractBrowser $client)
    {
    }

    final public function client(): AbstractBrowser
    {
        return $this->client;
    }

    final public function isSuccess(): bool
    {
        return $this->statusCode() >= 200 && $this->statusCode() < 300;
    }

    final public function isRedirect(): bool
    {
        return $this->statusCode() >= 300 && $this->statusCode() < 400;
    }

    abstract public function dom(): Dom;

    abstract public function content(): string;

    /**
     * The response body as the server produced it, which a real browser may render rather than show.
     */
    public function rawContent(): string
    {
        return $this->content();
    }

    abstract public function currentUrl(): string;

    abstract public function statusCode(): int;

    abstract public function isStarted(): bool;

    abstract public function responseHeader(string $header): ?string;

    /**
     * @return array<string,string[]>
     */
    abstract public function responseHeaders(): array;

    abstract public function click(Node $node): void;

    abstract public function select(Checkbox|Radio|Option $node): void;

    abstract public function unselect(Checkbox|Multiselect $node): void;

    /**
     * @param string[] $filenames
     */
    abstract public function attach(File $node, array $filenames): void;

    abstract public function fill(Input|Textarea $node, string $value): void;
}
