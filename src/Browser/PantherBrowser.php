<?php

namespace Zenstruck\Browser;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\DomCrawler\Crawler;
use Symfony\Component\VarDumper\VarDumper;
use Zenstruck\Assert;
use Zenstruck\Browser;
use Zenstruck\Browser\Extension\InteractiveExtension;
use Zenstruck\Browser\Mink\PantherDriver;
use Zenstruck\Browser\Response\PantherResponse;

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
    use InteractiveExtension;

    private ?string $screenshotDir = null;
    private ?string $consoleLogDir = null;

    /** @var string[] */
    private array $savedScreenshots = [];

    /** @var string[] */
    private array $savedConsoleLogs = [];

    final public function __construct(Client $client)
    {
        parent::__construct($client, new PantherDriver($client));
    }

    final public function setScreenshotDir(string $dir): self
    {
        $this->screenshotDir = $dir;

        return $this;
    }

    final public function setConsoleLogDir(string $dir): self
    {
        $this->consoleLogDir = $dir;

        return $this;
    }

    /**
     * @return static
     */
    final public function assertVisible(string $selector): self
    {
        return $this->wrapMinkExpectation(function() use ($selector) {
            $element = $this->webAssert()->elementExists('css', $selector);

            Assert::true($element->isVisible(), 'Expected element "%s" to be visible but it isn\'t.', [$selector]);
        });
    }

    /**
     * @return static
     */
    final public function assertNotVisible(string $selector): self
    {
        $element = $this->documentElement()->find('css', $selector);

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
    final public function inspect(): self
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
        VarDumper::dump($this->client()->manage()->getLog('browser'));

        return $this;
    }

    final public function ddConsoleLog(): void
    {
        $this->dumpConsoleLog();
        $this->die();
    }

    final public function ddScreenshot(string $filename = 'screenshot.png'): void
    {
        $this->takeScreenshot($filename);

        echo \sprintf("\n\nScreenshot saved as \"%s\".\n\n", \end($this->savedScreenshots));

        $this->die();
    }

    /**
     * @internal
     */
    final public function dumpCurrentState(string $filename): void
    {
        parent::dumpCurrentState($filename);

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
            ['Saved Console Logs' => $this->savedConsoleLogs, 'Saved Screenshots' => $this->savedScreenshots]
        );
    }

    final public function response(): PantherResponse
    {
        return new PantherResponse($this->minkSession());
    }

    /**
     * @internal
     */
    final protected function die(): void
    {
        $this->client()->quit();
        parent::die();
    }
}
