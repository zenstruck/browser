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

/**
 * @internal
 */
final class BrowserConfiguration
{
    /** @return array{class-string<KernelBrowser>, array<string, mixed>} */
    public static function kernel(array $options = []): array
    {
        return self::resolve($options, 'KERNEL_BROWSER_CLASS', KernelBrowser::class);
    }

    /** @return array{class-string<PlaywrightBrowser>, array<string, mixed>} */
    public static function playwright(array $options = []): array
    {
        return self::resolve($options, 'PLAYWRIGHT_BROWSER_CLASS', PlaywrightBrowser::class, [
            'screenshot_dir' => self::environment('BROWSER_SCREENSHOT_DIR', './var/browser/screenshots'),
            'console_log_dir' => self::environment('BROWSER_CONSOLE_LOG_DIR', './var/browser/console-logs'),
            'default_timeout' => self::environment('BROWSER_DEFAULT_TIMEOUT', null),
        ]);
    }

    /**
     * @template T of KernelBrowser|PlaywrightBrowser
     *
     * @param class-string<T> $baseClass
     *
     * @return array{class-string<T>, array<string, mixed>}
     */
    private static function resolve(array $options, string $classVariable, string $baseClass, array $defaults = []): array
    {
        $configuredClass = $options['browser_class'] ?? null;
        $class = $configuredClass ?? self::environment($classVariable, $baseClass);

        if (!\is_string($class) || !\is_a($class, $baseClass, true)) {
            $setting = null === $configuredClass ? \sprintf('"%s" env variable', $classVariable) : '"browser_class" option';

            throw new \LogicException(\sprintf('%s must reference a class that extends %s.', $setting, $baseClass));
        }

        unset($options['browser_class']);

        return [$class, $options + $defaults + [
            'source_dir' => self::environment('BROWSER_SOURCE_DIR', './var/browser/source'),
            'source_debug' => self::environment('BROWSER_SOURCE_DEBUG', false),
            'follow_redirects' => (bool) self::environment('BROWSER_FOLLOW_REDIRECTS', true),
            'catch_exceptions' => (bool) self::environment('BROWSER_CATCH_EXCEPTIONS', true),
        ]];
    }

    private static function environment(string $variable, mixed $default): mixed
    {
        $value = $_SERVER[$variable] ?? $_ENV[$variable] ?? null;

        if (null !== $value) {
            return $value;
        }

        $value = \getenv($variable);

        return false === $value ? $default : $value;
    }
}
