<?php

namespace Zenstruck\Browser\Test;

use Symfony\Component\BrowserKit\HttpBrowser as HttpBrowserClient;
use Symfony\Component\Panther\PantherTestCase;
use Zenstruck\Browser\HttpBrowser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @method HttpBrowser browser()
 */
trait HasHttpBrowser
{
    use HasBrowser;

    /** @var HttpBrowserClient[] */
    private static array $httpBrowserClients = [];

    /**
     * @internal
     * @after
     */
    public static function _resetHttpBrowserClients(): void
    {
        self::$httpBrowserClients = [];
    }

    protected function createBrowser(): HttpBrowser
    {
        if (!$this instanceof PantherTestCase) {
            throw new \RuntimeException(\sprintf('The "%s" trait can only be used on TestCases that extend "%s".', __TRAIT__, PantherTestCase::class));
        }

        $class = static::httpBrowserClass();

        if (!\is_a($class, HttpBrowser::class, true)) {
            throw new \RuntimeException(\sprintf('"HTTP_BROWSER_CLASS" env variable must reference a class that extends %s.', HttpBrowser::class));
        }

        if (empty(self::$httpBrowserClients)) {
            return new $class(self::$httpBrowserClients[] = static::createHttpBrowserClient());
        }

        $additionalClient = new HttpBrowserClient();

        // copied from PantherTestCaseTrait::createHttpBrowserClient()
        $urlComponents = \parse_url(self::$baseUri);

        $additionalClient->setServerParameter('HTTP_HOST', \sprintf('%s:%s', $urlComponents['host'], $urlComponents['port']));

        if ('https' === $urlComponents['scheme']) {
            self::$httpBrowserClient->setServerParameter('HTTPS', 'true');
        }

        return new $class(self::$httpBrowserClients[] = $additionalClient);
    }

    protected static function httpBrowserClass(): string
    {
        return $_SERVER['HTTP_BROWSER_CLASS'] ?? HttpBrowser::class;
    }
}
