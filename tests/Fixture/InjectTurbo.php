<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Tests\Fixture;

use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Loads Turbo into every html page while "TURBO" is set, so the same fixtures run with and
 * without it.
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class InjectTurbo
{
    public function __invoke(ResponseEvent $event): void
    {
        if (!isset($_SERVER['TURBO'])) {
            return;
        }

        $response = $event->getResponse();

        if (!\str_contains($response->headers->get('Content-Type', 'text/html'), 'text/html')) {
            return;
        }

        $content = (string) $response->getContent();

        if (!\str_contains($content, '</head>')) {
            return;
        }

        $response->setContent(\str_replace('</head>', '<script src="/turbo.js"></script></head>', $content));
    }
}
