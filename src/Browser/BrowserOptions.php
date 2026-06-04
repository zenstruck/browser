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
 * Value object holding the env-var-driven options used by {@see BrowserFactory}
 * to construct {@see KernelBrowser} and {@see PantherBrowser} instances.
 *
 * @author Hugo Hamon <hello@kodero.fr>
 */
final readonly class BrowserOptions
{
    public function __construct(
        public ?string $kernelBrowserClass = null,
        public ?string $pantherBrowserClass = null,
        public string $sourceDir = './var/browser/source',
        public bool $sourceDebug = false,
        public bool $followRedirects = true,
        public bool $catchExceptions = true,
        public string $screenshotDir = './var/browser/screenshots',
        public string $consoleLogDir = './var/browser/console-logs',
        public bool $alwaysStartWebserver = false,
        public ?string $pantherBrowser = null,
    ) {
    }

    public static function fromEnv(): self
    {
        return new self(
            kernelBrowserClass: $_SERVER['KERNEL_BROWSER_CLASS'] ?? null,
            pantherBrowserClass: $_SERVER['PANTHER_BROWSER_CLASS'] ?? null,
            sourceDir: $_SERVER['BROWSER_SOURCE_DIR'] ?? './var/browser/source',
            sourceDebug: (bool) ($_SERVER['BROWSER_SOURCE_DEBUG'] ?? false),
            followRedirects: (bool) ($_SERVER['BROWSER_FOLLOW_REDIRECTS'] ?? true),
            catchExceptions: (bool) ($_SERVER['BROWSER_CATCH_EXCEPTIONS'] ?? true),
            screenshotDir: $_SERVER['BROWSER_SCREENSHOT_DIR'] ?? './var/browser/screenshots',
            consoleLogDir: $_SERVER['BROWSER_CONSOLE_LOG_DIR'] ?? './var/browser/console-logs',
            alwaysStartWebserver: (bool) ($_SERVER['BROWSER_ALWAYS_START_WEBSERVER'] ?? false),
            pantherBrowser: $_SERVER['PANTHER_BROWSER'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toKernelBrowserOptions(): array
    {
        return [
            'source_dir' => $this->sourceDir,
            'source_debug' => $this->sourceDebug,
            'follow_redirects' => $this->followRedirects,
            'catch_exceptions' => $this->catchExceptions,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toPantherBrowserOptions(): array
    {
        return [
            'source_dir' => $this->sourceDir,
            'source_debug' => $this->sourceDebug,
            'screenshot_dir' => $this->screenshotDir,
            'console_log_dir' => $this->consoleLogDir,
        ];
    }
}
