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
        $class = static::httpBrowserClass();

        if (!\is_a($class, HttpBrowser::class, true)) {
            throw new \RuntimeException(\sprintf('"HTTP_BROWSER_CLASS" env variable must reference a class that extends %s.', HttpBrowser::class));
        }

        $client = new HttpBrowserClient();
        $urlComponents = \parse_url($this->httpBrowserBaseUri());

        // copied from PantherTestCaseTrait::createHttpBrowserClient()
        $host = $urlComponents['host'];

        if (isset($urlComponents['port'])) {
            $host .= ":{$urlComponents['port']}";
        }

        $client->setServerParameter('HTTP_HOST', $host);

        if ('https' === ($urlComponents['scheme'] ?? 'http')) {
            $client->setServerParameter('HTTPS', 'true');
        }

        return new $class(self::$httpBrowserClients[] = $client);
    }

    protected function httpBrowserBaseUri(): string
    {
        if (!$this instanceof PantherTestCase) {
            throw new \RuntimeException(\sprintf('If not using "%s", you must override "%s" and return a base uri.', PantherTestCase::class, __METHOD__));
        }

        self::startWebServer();

        return self::$baseUri;
    }

    protected static function httpBrowserClass(): string
    {
        return $_SERVER['HTTP_BROWSER_CLASS'] ?? HttpBrowser::class;
    }
}
