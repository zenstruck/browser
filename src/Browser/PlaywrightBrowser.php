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
use Playwright\Page\PageInterface;
use Playwright\Symfony\Client\PlaywrightKernelClient;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\Filesystem\Filesystem;
use Zenstruck\Assert;
use Zenstruck\Browser;
use Zenstruck\Browser\Session\Driver\PlaywrightDriver;
use Zenstruck\Browser\Session\Playwright\CookieJar as PlaywrightCookieJar;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @experimental
 *
 * @method PlaywrightKernelClient client()
 */
class PlaywrightBrowser extends Browser
{
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
        parent::__construct(new PlaywrightDriver($client), $options);

        if (!($options['follow_redirects'] ?? true)) {
            $this->interceptRedirects();
        }

        if (!($options['catch_exceptions'] ?? true)) {
            $this->throwExceptions();
        }

        $this->screenshotDir = $options['screenshot_dir'] ?? null;
        $this->consoleLogDir = $options['console_log_dir'] ?? null;

        // subscribe before anything is navigated to, or the messages are already gone
        // @todo also collect uncaught errors once playwright-php exposes the "pageerror" event
        $this->page()->events()->onConsole(function(ConsoleMessage $message): void {
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
        $element = $this->session()->assert()->elementExists('css', $selector);

        Assert::true($element->isVisible(), 'Expected element "%s" to be visible but it isn\'t.', [$selector]);

        return $this;
    }

    /**
     * @return static
     */
    final public function assertNotVisible(string $selector): self
    {
        $element = $this->session()->page()->find('css', $selector);

        if (!$element) {
            Assert::pass();

            return $this;
        }

        Assert::false($element->isVisible(), 'Expected element "%s" to not be visible but it is.', [$selector]);

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
        $this->page()->waitForSelector($selector, ['state' => 'visible']);

        return $this;
    }

    /**
     * @return static
     */
    final public function waitUntilNotVisible(string $selector): self
    {
        $this->page()->waitForSelector($selector, ['state' => 'hidden']);

        return $this;
    }

    /**
     * @return static
     */
    final public function waitUntilSeeIn(string $selector, string $expected): self
    {
        $this->page()->waitForFunction(
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
        $this->page()->waitForFunction(
            '([selector, text]) => { const el = document.querySelector(selector); return null === el || !el.checkVisibility() || !el.textContent.includes(text); }',
            [$selector, $expected],
        );

        return $this;
    }

    /**
     * Opens the Playwright Inspector and pauses execution.
     *
     * @return static
     */
    final public function pause(): self
    {
        $this->page()->pause();

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
        $this->page()->screenshot(\str_starts_with($filename, '/') ? $filename : \getcwd().'/'.$filename);

        return $this;
    }

    final public function saveConsoleLog(string $filename): self
    {
        if ($this->consoleLogDir) {
            $filename = \sprintf('%s/%s', \rtrim($this->consoleLogDir, '/'), \ltrim($filename, '/'));
        }

        $log = \json_encode($this->consoleMessages, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR);

        (new Filesystem())->dumpFile($this->savedConsoleLogs[] = $filename, $log);

        return $this;
    }

    final public function dumpConsoleLog(): self
    {
        Session::varDump($this->consoleMessages);

        return $this;
    }

    final public function ddConsoleLog(): void
    {
        $this->dumpConsoleLog();
        $this->session()->exit();
    }

    final public function ddScreenshot(string $filename = 'screenshot.png'): void
    {
        $this->takeScreenshot($filename);

        echo \sprintf("\n\nScreenshot saved as \"%s\".\n\n", \end($this->savedScreenshots));

        $this->session()->exit();
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

    final public function doubleClick(string $selector): self
    {
        $element = $this->getClickableElement($selector);
        $element->doubleClick();

        return $this;
    }

    final public function rightClick(string $selector): self
    {
        $element = $this->getClickableElement($selector);
        $element->rightClick();

        return $this;
    }

    /**
     * @internal
     */
    protected function cookieJar(): CookieJar
    {
        return new PlaywrightCookieJar($this->page());
    }

    private function page(): PageInterface
    {
        if (!$page = $this->client()->getPage()) {
            throw new \RuntimeException('The Playwright page is not available.');
        }

        return $page;
    }
}
