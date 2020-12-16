<?php

namespace Zenstruck;

use Behat\Mink\Driver\DriverInterface;
use Behat\Mink\Element\DocumentElement;
use Behat\Mink\Mink;
use Behat\Mink\Session;
use Behat\Mink\WebAssert;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\VarDumper\VarDumper;
use Zenstruck\Browser\Actions;
use Zenstruck\Browser\Assertions;
use Zenstruck\Browser\Component;
use Zenstruck\Browser\FunctionExecutor;
use function JmesPath\search;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class Browser
{
    use Actions, Assertions;

    private const SESSION = 'app';

    private Mink $mink;
    private ?string $sourceDir = null;

    public function __construct(DriverInterface $driver)
    {
        $this->mink = new Mink([self::SESSION => new Session($driver)]);
    }

    final public function setSourceDir(string $dir): self
    {
        $this->sourceDir = $dir;

        return $this;
    }

    final public function minkSession(): Session
    {
        return $this->mink->getSession(self::SESSION);
    }

    final public function webAssert(): WebAssert
    {
        return $this->mink->assertSession(self::SESSION);
    }

    final public function documentElement(): DocumentElement
    {
        return $this->minkSession()->getPage();
    }

    final public function use(callable $callback): self
    {
        FunctionExecutor::createFor($callback)
            ->replaceUntypedArgument($this)
            ->replaceTypedArgument(self::class, $this)
            ->replaceTypedArgument(Component::class, fn(string $class) => new $class($this))
            ->execute()
        ;

        return $this;
    }

    final public function saveSource(string $filename): self
    {
        if ($this->sourceDir) {
            $filename = \sprintf('%s/%s', \rtrim($this->sourceDir, '/'), \ltrim($filename, '/'));
        }

        (new Filesystem())->dumpFile($filename, $this->rawResponse());

        return $this;
    }

    final public function dump(?string $selector = null): self
    {
        VarDumper::dump($selector ? $this->normalizeDumpVariable($selector) : $this->rawResponse());

        return $this;
    }

    final public function dd(?string $selector = null): void
    {
        $this->dump($selector);
        exit(1);
    }

    public function dumpCurrentState(string $filename): void
    {
        $this->saveSource("{$filename}.txt");
    }

    protected function normalizeDumpVariable(string $selector)
    {
        $contentType = $this->minkSession()->getResponseHeader('content-type');

        if (!str_contains((string) $contentType, 'application/json')) {
            return $this->documentElement()->find('css', $selector)->getHtml();
        }

        $array = \json_decode($this->documentElement()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        if (\function_exists('JmesPath\search')) {
            return search($selector, $array);
        }

        return $array[$selector];
    }

    protected function rawResponse(): string
    {
        $response = "URL: {$this->minkSession()->getCurrentUrl()} ({$this->minkSession()->getStatusCode()})\n\n";

        foreach ($this->minkSession()->getResponseHeaders() as $header => $values) {
            foreach ($values as $value) {
                $response .= "{$header}: {$value}\n";
            }
        }

        $body = $this->documentElement()->getContent();
        $contentType = $this->minkSession()->getResponseHeader('content-type');

        if (str_contains((string) $contentType, 'application/json')) {
            $body = \json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            $body = \json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        }

        return "{$response}\n{$body}";
    }
}
