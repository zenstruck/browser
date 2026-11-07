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

use Behat\Mink\Element\NodeElement;
use Playwright\Console\ConsoleMessage;
use Playwright\Page\PageInterface;
use Playwright\Symfony\Client\PlaywrightKernelClient;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\Filesystem\Filesystem;
use Zenstruck\Browser;
use Zenstruck\Browser\Session\Driver\PlaywrightDriver;
use Zenstruck\Browser\Session\Playwright\CookieJar as PlaywrightCookieJar;
use Zenstruck\Browser\Session\Playwright\Selectors;

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
        $this->timeout((int) ($options['default_timeout'] ?? 2000));

        // subscribe before anything is navigated to, or the messages are already gone
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
        $this->session()->assert()->elementVisible($selector);

        return $this;
    }

    /**
     * @return static
     */
    final public function assertNotVisible(string $selector): self
    {
        $this->session()->assert()->elementVisible($selector, true);

        return $this;
    }

    /**
     * @return static
     */
    final public function timeout(int $milliseconds): self
    {
        $this->driver()->timeout($milliseconds);

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

        $log = \json_encode($this->consoleLog(), \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR);

        (new Filesystem())->dumpFile($this->savedConsoleLogs[] = $filename, $log);

        return $this;
    }

    final public function dumpConsoleLog(): self
    {
        Session::varDump($this->consoleLog());

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
        return new PlaywrightCookieJar($this->page(), $this->client()->getBaseUrl());
    }

    protected function getClickableElement(string $selector): NodeElement
    {
        $this->session()->page();
        $xpath = (new Selectors($this->session()->getSelectorsHandler()))->clickable($selector);

        if (!$this->driver()->nativeWait()->element($xpath, 'visible')) {
            return parent::getClickableElement($selector);
        }

        return new NodeElement($xpath, $this->session());
    }

    protected function getField(string $selector): NodeElement
    {
        $this->session()->page();
        $xpath = (new Selectors($this->session()->getSelectorsHandler()))->field($selector);

        if (!$this->driver()->nativeWait()->element($xpath, 'attached')) {
            return parent::getField($selector);
        }

        return new NodeElement($xpath, $this->session());
    }

    protected function waitForUrl(string $expected, array $parts, bool $negated = false): void
    {
        $this->driver()->nativeWait()->url($expected, $parts, $negated);
    }

    private function driver(): PlaywrightDriver
    {
        $driver = $this->session()->getDriver();
        \assert($driver instanceof PlaywrightDriver);

        return $driver;
    }

    /**
     * @return array<array{type:string,text:string,location:array<string,mixed>}>
     */
    private function consoleLog(): array
    {
        $client = $this->client();
        $page = $client->getPage();

        if (null === $page || $page->isClosed()) {
            return $this->consoleMessages;
        }

        $errors = $page->pageErrors(['filter' => 'all']);
        $log = $this->consoleMessages;

        foreach ($errors as $error) {
            $log[] = [
                'type' => 'pageerror',
                'text' => $error->stack() ?? \sprintf(
                    '%s: %s',
                    $error->name(),
                    $error->message(),
                ),
                'location' => [],
            ];
        }

        return $log;
    }

    private function page(): PageInterface
    {
        if (!$page = $this->client()->getPage()) {
            throw new \RuntimeException('The Playwright page is not available.');
        }

        return $page;
    }
}
