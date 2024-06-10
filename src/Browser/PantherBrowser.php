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
use Zenstruck\Browser\Session\Driver\PantherDriver;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @experimental in 1.0
 *
 * @method Client  client()
 * @method Crawler crawler()
 */
class PantherBrowser extends Browser
{
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
        parent::__construct(new PantherDriver($client), $options);

        $this->screenshotDir = $options['screenshot_dir'] ?? null;
        $this->consoleLogDir = $options['console_log_dir'] ?? null;
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
        Session::varDump($this->client()->manage()->getLog('browser'));

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

    public function getScreenshotDir(): ?string
    {
        return $this->screenshotDir;
    }

    public function getConsoleLogDir(): ?string
    {
        return $this->consoleLogDir;
    }

    public function getSavedScreenshots(): array
    {
        return $this->savedScreenshots;
    }

    public function getSavedConsoleLogs(): array
    {
        return $this->savedConsoleLogs;
    }
}
