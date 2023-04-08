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

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\DomCrawler\Crawler;
use Zenstruck\Assert;
use Zenstruck\Browser;
use Zenstruck\Browser\Dom\Selector;
use Zenstruck\Browser\Session\PantherSession;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @experimental in 1.0
 *
 * @phpstan-import-type SelectorType from Selector
 *
 * @method Client  client()
 * @method Crawler crawler()
 */
class PantherBrowser extends Browser
{
    private PantherSession $session;
    private ?string $screenshotDir;
    private ?string $consoleLogDir;

    /** @var string[] */
    private array $savedScreenshots = [];

    /** @var string[] */
    private array $savedConsoleLogs = [];

    /**
     * @internal
     */
    final public function __construct(Client $client, array $options = [])
    {
        parent::__construct($this->session = new PantherSession($client), $options);

        $this->screenshotDir = $options['screenshot_dir'] ?? null;
        $this->consoleLogDir = $options['console_log_dir'] ?? null;
    }

    /**
     * @param SelectorType $selector
     */
    public function assertSeeIn(Selector|string|callable $selector, string $expected): self
    {
        if ('title' === $selector) {
            // hack to get the text of the title html element
            // for this element, WebDriverElement::getText() returns an empty string
            // the only way to get the value is to get title from the client
            Assert::that($this->client()->getTitle())->contains($expected);

            return $this;
        }

        return parent::assertSeeIn($selector, $expected);
    }

    /**
     * @param SelectorType $selector
     */
    public function assertNotSeeIn(Selector|string|callable $selector, string $expected): self
    {
        if ('title' === $selector) {
            // hack to get the text of the title html element
            // for this element, WebDriverElement::getText() returns an empty string
            // the only way to get the value is to get title from the client
            Assert::that($this->client()->getTitle())->doesNotContain($expected);

            return $this;
        }

        return parent::assertNotSeeIn($selector, $expected);
    }

    /**
     * @param SelectorType $selector
     *
     * @return static
     */
    final public function assertVisible(Selector|string|callable $selector): self
    {
        $this->dom()->expect()->elementIsVisible($selector);

        return $this;
    }

    /**
     * @param SelectorType $selector
     *
     * @return static
     */
    final public function assertNotVisible(Selector|string|callable $selector): self
    {
        $this->dom()->expect()->elementIsNotVisible($selector);

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
        $this->client()->waitForVisibility($selector);

        return $this;
    }

    /**
     * @return static
     */
    final public function waitUntilNotVisible(string $selector): self
    {
        $this->client()->waitForInvisibility($selector);

        return $this;
    }

    /**
     * @return static
     */
    final public function waitUntilSeeIn(string $selector, string $expected): self
    {
        $this->client()->waitForElementToContain($selector, $expected);

        return $this;
    }

    /**
     * @return static
     */
    final public function waitUntilNotSeeIn(string $selector, string $expected): self
    {
        $this->client()->waitForElementToNotContain($selector, $expected);

        return $this;
    }

    /**
     * @return static
     */
    final public function pause(): self
    {
        if (!($_SERVER['PANTHER_NO_HEADLESS'] ?? false)) {
            throw new \RuntimeException('The "PANTHER_NO_HEADLESS" env variable must be set to inspect.');
        }

        \fwrite(\STDIN, "\n\nInspecting the browser.\n\nPress enter to continue...");
        \fgets(\STDIN);

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

        $this->client()->takeScreenshot($this->savedScreenshots[] = $filename);

        return $this;
    }

    final public function saveConsoleLog(string $filename): self
    {
        if ($this->consoleLogDir) {
            $filename = \sprintf('%s/%s', \rtrim($this->consoleLogDir, '/'), \ltrim($filename, '/'));
        }

        $log = $this->client()->manage()->getLog('browser');
        $log = \json_encode($log, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR);

        (new Filesystem())->dumpFile($this->savedConsoleLogs[] = $filename, $log);

        return $this;
    }

    final public function dumpConsoleLog(): self
    {
        Dumper::dump($this->client()->manage()->getLog('browser'));

        return $this;
    }

    final public function ddConsoleLog(): void
    {
        $this->dumpConsoleLog();
        $this->exit();
    }

    final public function ddScreenshot(string $filename = 'screenshot.png'): void
    {
        $this->takeScreenshot($filename);

        echo \sprintf("\n\nScreenshot saved as \"%s\".\n\n", \end($this->savedScreenshots));

        $this->exit();
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
     * @param SelectorType $selector
     *
     * @return static
     */
    final public function doubleClick(Selector|string|callable $selector): self
    {
        $this->session->doubleClick($this->dom()->findOrFail($selector));

        return $this;
    }

    /**
     * @param SelectorType $selector
     *
     * @return static
     */
    final public function rightClick(Selector|string|callable $selector): self
    {
        $this->session->rightClick($this->dom()->findOrFail($selector));

        return $this;
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

    /**
     * @internal
     */
    final protected function source(bool $debug): string
    {
        $ret = '';

        if ($debug) {
            $ret .= "<!--\n";
            $ret .= "URL: {$this->client()->getCurrentURL()}\n";
            $ret .= "-->\n";
        }

        return $ret.$this->client()->getPageSource();
    }

    /**
     * @internal
     */
    final protected function exit(): void
    {
        $this->client()->quit();

        parent::exit();
    }
}
