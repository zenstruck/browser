<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Session\Driver;

use Behat\Mink\Exception\DriverException;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Playwright\Exception\PlaywrightException;
use Playwright\Locator\LocatorInterface;
use Playwright\Page\PageInterface;
use Playwright\Symfony\Client\PlaywrightKernelClient;
use Zenstruck\Browser\HttpOptions;
use Zenstruck\Browser\Session\Driver;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 *
 * @method PlaywrightKernelClient client()
 */
final class PlaywrightDriver extends Driver
{
    /** @var array<string,string[]> */
    private array $attachedFiles = [];

    private ?string $downloadedContent = null;

    public function __construct(PlaywrightKernelClient $client)
    {
        parent::__construct($client); // @phpstan-ignore argument.type
    }

    public function request(string $method, string $url, HttpOptions $options): void
    {
        if ('GET' !== $method) {
            throw new UnsupportedDriverActionException('%s only supports "GET" requests.', $this);
        }

        $this->downloadedContent = null;

        $this->wrapRequest(function() use ($url): void {
            try {
                $this->client()->visit($this->toPath($url));
            } catch (PlaywrightException $e) {
                if (!\str_contains($e->getMessage(), 'Download is starting')) {
                    throw $e;
                }

                // The browser downloads attachments instead of rendering them, leaving the page
                // where it was. The kernel already built the body on this side of the bridge, so
                // serve that.
                $this->downloadedContent = $this->lastResponseBody();
            }
        });
    }

    public function getCurrentUrl(): string
    {
        return $this->page()->url();
    }

    public function getContent(): string
    {
        return $this->downloadedContent ?? (string) $this->page()->content();
    }

    public function rawContent(): string
    {
        $response = $this->client()->getLastSymfonyResponse();

        if (!$response || false === $body = $response->getContent()) {
            return $this->getContent();
        }

        return $body;
    }

    public function getStatusCode(): int
    {
        if (!$response = $this->client()->getLastSymfonyResponse()) {
            throw new DriverException('No response available.');
        }

        return $response->getStatusCode();
    }

    public function getResponseHeaders(): array
    {
        if (!$response = $this->client()->getLastSymfonyResponse()) {
            throw new DriverException('No response available.');
        }

        return $response->headers->all();
    }

    public function getText($xpath): string
    {
        // innerText falls back to textContent for elements that aren't rendered, but Mink expects
        // visible text only - <title> is the exception, it's never rendered yet always has text
        return (string) $this->locator($xpath)->evaluate(
            "el => 'TITLE' === el.tagName ? el.textContent : (el.checkVisibility() ? el.innerText : '')",
        );
    }

    public function getHtml($xpath): string
    {
        return $this->locator($xpath)->innerHTML();
    }

    public function getOuterHtml($xpath): string
    {
        return (string) $this->locator($xpath)->evaluate('el => el.outerHTML');
    }

    public function getTagName($xpath): string
    {
        return (string) $this->locator($xpath)->evaluate('el => el.tagName.toLowerCase()');
    }

    public function getAttribute($xpath, $name): ?string
    {
        return $this->locator($xpath)->getAttribute($name);
    }

    public function getValue($xpath): string|bool|array|null
    {
        $locator = $this->locator($xpath);

        if ('select' === $this->getTagName($xpath)) {
            if (null !== $locator->getAttribute('multiple')) {
                return $locator->evaluate('el => Array.from(el.selectedOptions).map(o => o.value)');
            }

            return $locator->inputValue();
        }

        if ('checkbox' === $this->typeOf($locator)) {
            return $locator->isChecked() ? ($locator->getAttribute('value') ?? 'on') : null;
        }

        if ('radio' === $this->typeOf($locator)) {
            return $locator->evaluate('el => { const c = el.getRootNode().querySelector(`input[type=radio][name="${el.name}"]:checked`); return c ? c.value : null; }');
        }

        // el.value covers inputs, textareas and options (whose value falls back to their text)
        return $locator->evaluate('el => el.value ?? null');
    }

    public function setValue($xpath, $value): void
    {
        $locator = $this->locator($xpath);
        $type = $this->typeOf($locator);

        if ('checkbox' === $type) {
            $value ? $locator->check() : $locator->uncheck();

            return;
        }

        if ('radio' === $type) {
            $this->selectOption($xpath, (string) $value);

            return;
        }

        if ('file' === $type) {
            $locator->setInputFiles((string) $value);

            return;
        }

        if ('select' === $this->getTagName($xpath)) {
            if (\is_array($value) && !$value) {
                $locator->evaluate('el => { el.selectedIndex = -1; el.dispatchEvent(new Event("change", {bubbles: true})); }');

                return;
            }

            $locator->selectOption($value);

            return;
        }

        $locator->fill((string) $value);
    }

    public function check($xpath): void
    {
        $this->locator($xpath)->check();
    }

    public function uncheck($xpath): void
    {
        $this->locator($xpath)->uncheck();
    }

    public function isChecked($xpath): bool
    {
        return $this->locator($xpath)->isChecked();
    }

    public function selectOption($xpath, $value, $multiple = false): void
    {
        $locator = $this->locator($xpath);

        if ('radio' === $this->typeOf($locator)) {
            $locator->evaluate('(el, value) => { const t = el.getRootNode().querySelector(`input[type=radio][name="${el.name}"][value="${value}"]`); if (t) { t.click(); } }', $value);

            return;
        }

        $values = $value;

        if ($multiple) {
            $current = \array_filter((array) $this->getValue($xpath), 'is_string');
            $values = \array_values(\array_unique([...$current, $value]));
        }

        try {
            $locator->selectOption($values);
        } catch (\Throwable) {
            // try selecting by visible text
            $locator->selectOption(['label' => $value]);
        }
    }

    /**
     * @param string $path
     */
    public function attachFile($xpath, $path): void
    {
        $locator = $this->locator($xpath);
        $existing = $this->attachedFiles[$xpath] ?? [];

        if ($existing && null === $locator->getAttribute('multiple')) {
            throw new \InvalidArgumentException('Cannot attach multiple files to a non-multiple file field.');
        }

        // cast: an SplFileInfo is a valid Browser::attachFile() argument but Playwright needs a path
        $this->attachedFiles[$xpath] = $files = [...$existing, (string) $path];

        $locator->setInputFiles($files);
    }

    public function click($xpath): void
    {
        $this->downloadedContent = null;

        $this->wrapRequest(function() use ($xpath): void {
            $this->locator($xpath)->click();
            $this->page()->waitForLoadState();
        });
    }

    public function doubleClick($xpath): void
    {
        $this->downloadedContent = null;

        $before = $this->page()->url();

        $this->locator($xpath)->dblclick();

        $this->awaitPossibleNavigation($before);
    }

    public function rightClick($xpath): void
    {
        $this->downloadedContent = null;

        $before = $this->page()->url();

        $this->locator($xpath)->click(['button' => 'right']);

        $this->awaitPossibleNavigation($before);
    }

    public function isVisible($xpath): bool
    {
        return $this->locator($xpath)->isVisible();
    }

    public function executeScript($script): void
    {
        $this->page()->evaluate(\sprintf('() => { %s }', \rtrim(\trim($script), ';').';'));
    }

    public function evaluateScript($script): mixed
    {
        $script = \trim($script);

        if (0 === \mb_strpos($script, 'return ')) {
            $script = \mb_substr($script, 7);
        }

        return $this->page()->evaluate(\rtrim($script, ';'));
    }

    protected function findElementXpaths($xpath): array
    {
        $count = $this->page()->locator('xpath='.$xpath)->count();

        $elements = [];

        for ($i = 1; $i <= $count; ++$i) {
            $elements[] = \sprintf('(%s)[%d]', $xpath, $i);
        }

        return $elements;
    }

    protected function clientCatchExceptions(bool $catch): void
    {
        $this->client()->catchExceptions($catch);
    }

    /**
     * A dblclick/contextmenu handler may navigate by assigning location, which Playwright does not
     * wait for. Give that a brief chance to happen before reporting the page as settled.
     */
    private function awaitPossibleNavigation(string $before): void
    {
        try {
            $this->page()->waitForFunction('url => location.href !== url', $before, ['timeout' => 1000]);
        } catch (PlaywrightException) {
            // nothing navigated
        }

        $this->page()->waitForLoadState();
    }

    private function locator(string $xpath): LocatorInterface
    {
        return $this->page()->locator('xpath='.$xpath);
    }

    private function typeOf(LocatorInterface $locator): string
    {
        return \mb_strtolower((string) $locator->getAttribute('type'));
    }

    private function lastResponseBody(): string
    {
        if (!$response = $this->client()->getLastSymfonyResponse()) {
            throw new DriverException('No response available.');
        }

        if (false === $body = $response->getContent()) {
            throw new DriverException('The response does not expose its content.');
        }

        return $body;
    }

    private function page(): PageInterface
    {
        if (!$page = $this->client()->getPage()) {
            throw new DriverException('Unable to access the page before visiting a url.');
        }

        return $page;
    }

    private function toPath(string $url): string
    {
        if (!\preg_match('#^https?://#', $url)) {
            return $url;
        }

        return (string) \preg_replace('#^https?://[^/]+#', '', $url) ?: '/';
    }
}
