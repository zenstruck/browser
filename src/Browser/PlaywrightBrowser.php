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

use Playwright\Console\ConsoleMessage;
use Playwright\Symfony\Client\PlaywrightKernelClient;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\Filesystem\Filesystem;
use Zenstruck\Assert;
use Zenstruck\Browser;
use Zenstruck\Browser\Session\Playwright\CookieJar as PlaywrightCookieJar;
use Zenstruck\Browser\Session\PlaywrightSession;
use Zenstruck\Dom\Node;
use Zenstruck\Dom\Selector;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @experimental
 *
 * @phpstan-import-type SelectorType from Selector
 *
 * @method PlaywrightKernelClient client()
 */
class PlaywrightBrowser extends Browser
{
    private PlaywrightSession $session;
    private ?string $screenshotDir;
    private ?string $consoleLogDir;

    /** @var string[] */
    private array $savedScreenshots = [];

    /** @var string[] */
    private array $savedConsoleLogs = [];

    /** @var array<array{type:string,text:string,location:array<string,mixed>}> */
    private array $consoleMessages = [];

    /**
     * @internal
     */
    final public function __construct(PlaywrightKernelClient $client, array $options = [])
    {
        parent::__construct($this->session = new PlaywrightSession($client), $options);

        $this->screenshotDir = $options['screenshot_dir'] ?? null;
        $this->consoleLogDir = $options['console_log_dir'] ?? null;

        // subscribe before anything is navigated to, or the messages are already gone
        // @todo also collect uncaught errors once playwright-php exposes the "pageerror" event
        $this->session->page()->events()->onConsole(function(ConsoleMessage $message): void {
            $this->consoleMessages[] = [
                'type' => $message->type(),
                'text' => $message->text(),
                'location' => $message->location(),
            ];
        });
    }

    /**
     * @return static
     */
    final public function assertVisible(string $selector): self
    {
        Assert::true($this->session->isVisible($selector), 'Expected element "%s" to be visible but it isn\'t.', [$selector]);

        return $this;
    }

    /**
     * @return static
     */
    final public function assertNotVisible(string $selector): self
    {
        Assert::false($this->session->isVisible($selector), 'Expected element "%s" to not be visible but it is.', [$selector]);

        return $this;
    }

    /**
     * @return static
     */
    final public function wait(int $milliseconds): self
    {
        \usleep($milliseconds * 1000);

        return $this;
    }

    /**
     * @return static
     */
    final public function waitUntilVisible(string $selector): self
    {
        $this->session->page()->waitForSelector($selector, ['state' => 'visible']);

        return $this;
    }

    /**
     * @return static
     */
    final public function waitUntilNotVisible(string $selector): self
    {
        $this->session->page()->waitForSelector($selector, ['state' => 'hidden']);

        return $this;
    }

    /**
     * @return static
     */
    final public function waitUntilSeeIn(string $selector, string $expected): self
    {
        $this->session->page()->waitForFunction(
            '([selector, text]) => { const el = document.querySelector(selector); return null !== el && el.checkVisibility() && el.textContent.includes(text); }',
            [$selector, $expected],
        );

        return $this;
    }

    /**
     * @return static
     */
    final public function waitUntilNotSeeIn(string $selector, string $expected): self
    {
        $this->session->page()->waitForFunction(
            '([selector, text]) => { const el = document.querySelector(selector); return null === el || !el.checkVisibility() || !el.textContent.includes(text); }',
            [$selector, $expected],
        );

        return $this;
    }

    /**
     * @param SelectorType $selector
     *
     * @return static
     */
    final public function doubleClick(Selector|string|callable $selector): self
    {
        $this->session->doubleClick($this->clickable($selector));

        return $this;
    }

    /**
     * @param SelectorType $selector
     *
     * @return static
     */
    final public function rightClick(Selector|string|callable $selector): self
    {
        $this->session->rightClick($this->clickable($selector));

        return $this;
    }

    /**
     * Opens the Playwright Inspector and pauses execution.
     *
     * @return static
     */
    final public function pause(): self
    {
        $this->session->page()->pause();

        return $this;
    }

    /**
     * @return static
     */
    final public function takeScreenshot(string $filename): self
    {
        if ($this->screenshotDir) {
            $filename = \sprintf('%s/%s', \rtrim($this->screenshotDir, '/'), \ltrim($filename, '/'));
        }

        $this->savedScreenshots[] = $filename;

        // @todo drop once the node server resolves relative paths against PHP's cwd
        $this->session->page()->screenshot(\str_starts_with($filename, '/') ? $filename : \getcwd().'/'.$filename);

        return $this;
    }

    /**
     * @return static
     */
    final public function saveConsoleLog(string $filename): self
    {
        if ($this->consoleLogDir) {
            $filename = \sprintf('%s/%s', \rtrim($this->consoleLogDir, '/'), \ltrim($filename, '/'));
        }

        $log = \json_encode($this->consoleMessages, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR);

        (new Filesystem())->dumpFile($this->savedConsoleLogs[] = $filename, $log);

        return $this;
    }

    /**
     * @return static
     */
    final public function dumpConsoleLog(): self
    {
        Dumper::dump($this->consoleMessages);

        return $this;
    }

    final public function ddConsoleLog(): void
    {
        $this->dumpConsoleLog()->exit();
    }

    final public function ddScreenshot(string $filename = 'screenshot.png'): void
    {
        $this->takeScreenshot($filename);

        echo \sprintf("\n\nScreenshot saved as \"%s\".\n\n", \end($this->savedScreenshots));

        $this->exit();
    }

    final public function dump(Selector|string|callable|null $selector = null): self
    {
        if (!$selector) {
            Dumper::dump($this->source(true));

            return $this;
        }

        $this->dom()->dump($selector);

        return $this;
    }

    final public function saveCurrentState(string $filename): void
    {
        parent::saveCurrentState($filename);

        $this->takeScreenshot("{$filename}.png");
        $this->saveConsoleLog("{$filename}.log");
    }

    /**
     * @internal
     */
    final public function savedArtifacts(): array
    {
        return \array_merge(
            parent::savedArtifacts(),
            ['Saved Console Logs' => $this->savedConsoleLogs, 'Saved Screenshots' => $this->savedScreenshots],
        );
    }

    /**
     * @internal
     */
    final protected function doVisit(string $uri): void
    {
        $this->session->visit($uri);
    }

    /**
     * @internal
     */
    final protected function cookieJar(): CookieJar
    {
        return new PlaywrightCookieJar($this->session->page(), $this->client()->getBaseUrl());
    }

    /**
     * @internal
     */
    final protected function source(bool $debug): string
    {
        $ret = '';

        if ($debug) {
            $ret .= "<!--\n";
            $ret .= "URL: {$this->session->currentUrl()} ({$this->session->statusCode()})\n\n";

            foreach ($this->session->responseHeaders() as $header => $values) {
                foreach ((array) $values as $value) {
                    $ret .= "{$header}: {$value}\n";
                }
            }

            $ret .= "-->\n";
        }

        return $ret.$this->content();
    }

    /**
     * @param SelectorType $selector
     */
    private function clickable(Selector|string|callable $selector): Node
    {
        $node = $this->dom()->findOrFail(Selector::clickable($selector));

        Assert::true($node->isVisible(), 'Clickable element "%s" is not visible.', [$selector]);

        return $node;
    }
}
