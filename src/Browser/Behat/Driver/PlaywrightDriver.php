<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Behat\Driver;

use Behat\Mink\Driver\CoreDriver;
use Behat\Mink\Exception\DriverException;
use Playwright\Exception\PlaywrightException;
use Playwright\Exception\TimeoutException;
use Playwright\Locator\LocatorInterface;
use Playwright\Page\PageInterface;
use Playwright\Symfony\Client\BrowserRegistry;
use Playwright\Symfony\Client\Interception\AssetServer;
use Playwright\Symfony\Client\PlaywrightKernelClient;
use Playwright\Symfony\Client\RequestConverter;
use Playwright\Symfony\Client\ResponseConverter;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Browser\BrowserConfiguration;
use Zenstruck\Browser\PlaywrightBrowser;
use Zenstruck\Browser\Session\Playwright\Wait;

/**
 * Mink 1.10 declares scalar parameters without types.
 *
 * @internal
 */
final class PlaywrightDriver extends CoreDriver
{
    private ?BrowserRegistry $registry = null;
    private ?PlaywrightBrowser $browser = null;

    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly ?string $baseUrl = null,
        private readonly array $options = [],
    ) {
    }

    public function start(): void
    {
        if (!\class_exists(PlaywrightKernelClient::class)) {
            throw new DriverException('Install "playwright-php/playwright-symfony" to run @javascript scenarios.');
        }

        $this->registry ??= BrowserRegistry::fromEnvironment();
    }

    public function isStarted(): bool
    {
        return null !== $this->registry;
    }

    public function reset(): void
    {
        $this->registry?->resetSessions();
        $this->browser = null;
    }

    public function stop(): void
    {
        $this->reset();
        $this->registry?->stop();
        $this->registry = null;
    }

    public function browser(): PlaywrightBrowser
    {
        if (!$this->isStarted()) {
            $this->start();
        }

        return $this->browser ??= $this->createBrowser();
    }

    public function visit($url): void
    {
        $browser = $this->browser();
        $browser->visit($url);
    }

    public function getCurrentUrl(): string
    {
        return $this->page()->url();
    }

    public function getContent(): string
    {
        return $this->page()->content();
    }

    public function getStatusCode(): int
    {
        return $this->response()->getStatusCode();
    }

    public function getResponseHeaders(): array
    {
        return $this->response()->headers->all();
    }

    public function setCookie($name, $value = null): void
    {
        $browser = $this->browser();
        $browser->use(static function(CookieJar $cookies) use ($name, $value): void {
            if (null === $value) {
                $cookies->expire($name);

                return;
            }

            $cookies->set(new Cookie($name, $value));
        });
    }

    public function getCookie($name): ?string
    {
        $value = null;
        $browser = $this->browser();
        $browser->use(static function(CookieJar $cookies) use ($name, &$value): void {
            $cookie = $cookies->get($name);
            $value = $cookie?->getValue();
        });

        return $value;
    }

    public function getTagName($xpath): string
    {
        return $this->locator($xpath)->evaluate('el => el.tagName.toLowerCase()');
    }

    public function getText($xpath): string
    {
        return $this->locator($xpath)->evaluate(
            "el => 'TITLE' === el.tagName ? el.textContent : (el.checkVisibility() ? el.innerText : '')",
        );
    }

    public function getHtml($xpath): string
    {
        return $this->locator($xpath)->innerHTML();
    }

    public function getOuterHtml($xpath): string
    {
        return $this->locator($xpath)->evaluate('el => el.outerHTML');
    }

    public function getAttribute($xpath, $name): ?string
    {
        return $this->locator($xpath)->getAttribute($name);
    }

    public function getValue($xpath): string|bool|array|null
    {
        $locator = $this->locator($xpath);
        $type = $locator->getAttribute('type');

        if ('checkbox' === $type) {
            return $locator->isChecked() ? ($locator->getAttribute('value') ?? 'on') : null;
        }

        if ('radio' === $type) {
            return $locator->evaluate('el => Array.from(el.getRootNode().querySelectorAll("input[type=radio]")).find(radio => radio.name === el.name && radio.checked)?.value ?? null');
        }

        return $locator->evaluate('el => el.multiple && el.tagName === "SELECT" ? Array.from(el.selectedOptions).map(option => option.value) : (el.value ?? null)');
    }

    public function setValue($xpath, $value): void
    {
        $locator = $this->locator($xpath);
        $type = $locator->getAttribute('type');

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

        if ('radio' === $locator->getAttribute('type')) {
            $locator->evaluate('(el, value) => { const target = Array.from(el.getRootNode().querySelectorAll("input[type=radio]")).find(radio => radio.name === el.name && radio.value === value); if (target) target.click(); }', $value);

            return;
        }

        $values = $value;

        if ($multiple) {
            $values = [...(array) $this->getValue($xpath), $value];
        }

        try {
            $locator->selectOption($values);
        } catch (PlaywrightException) {
            $locator->selectOption(['label' => $value]);
        }
    }

    public function isSelected($xpath): bool
    {
        return $this->locator($xpath)->evaluate('el => el.selected');
    }

    public function attachFile($xpath, $path): void
    {
        $this->locator($xpath)->setInputFiles((string) $path);
    }

    public function click($xpath): void
    {
        $locator = $this->locator($xpath);
        $locator->click(['trial' => true]);
        $locator->click(['timeout' => 30_000]);
        $this->page()->waitForLoadState();
    }

    public function isVisible($xpath): bool
    {
        return $this->locator($xpath)->isVisible();
    }

    public function executeScript($script): void
    {
        $this->page()->evaluate(\sprintf('() => { %s }', $script));
    }

    public function evaluateScript($script): mixed
    {
        return $this->page()->evaluate($script);
    }

    public function wait($timeout, $condition): bool
    {
        try {
            $this->page()->waitForFunction($condition, null, ['timeout' => $timeout]);

            return true;
        } catch (TimeoutException) {
            return false;
        }
    }

    public function waitForAssertion(string $assertion, array $arguments): void
    {
        $wait = new Wait($this->page());
        $wait->assertion($assertion, $arguments);
    }

    public function waitForCondition(string $predicate, array $arguments): void
    {
        $wait = new Wait($this->page());
        $wait->condition($predicate, $arguments);
    }

    protected function findElementXpaths($xpath): array
    {
        $locator = $this->locator($xpath);
        $count = $locator->count();
        $elements = [];

        for ($index = 1; $index <= $count; ++$index) {
            $elements[] = \sprintf('(%s)[%d]', $xpath, $index);
        }

        return $elements;
    }

    private function createBrowser(): PlaywrightBrowser
    {
        [$class, $options] = BrowserConfiguration::playwright($this->options);
        $container = $this->kernel->getContainer();
        $hosts = $container->hasParameter('playwright.intercepted_hosts') ? $container->getParameter('playwright.intercepted_hosts') : null;
        $baseUrl = $this->baseUrl ?: ($container->hasParameter('playwright.base_url') ? $container->getParameter('playwright.base_url') : null);

        $client = new PlaywrightKernelClient(
            $this->registry->createSession(),
            $this->kernel,
            new RequestConverter(),
            new ResponseConverter(),
            [],
            \is_array($hosts) ? $hosts : null,
            null,
            $container->has(AssetServer::class) ? $container->get(AssetServer::class) : null,
            \is_string($baseUrl) && '' !== $baseUrl ? $baseUrl : 'http://localhost',
        );

        return new $class($client, $options);
    }

    private function page(): PageInterface
    {
        $browser = $this->browser();
        $client = $browser->client();

        return $client->getPage() ?? throw new DriverException('The browser has no page.');
    }

    private function response(): Response
    {
        $browser = $this->browser();
        $client = $browser->client();

        return $client->getLastSymfonyResponse() ?? throw new DriverException('No response available.');
    }

    private function locator(string $xpath): LocatorInterface
    {
        $page = $this->page();

        return $page->locator('xpath='.$xpath);
    }
}
