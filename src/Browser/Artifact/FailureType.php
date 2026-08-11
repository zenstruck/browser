<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Artifact;

/**
 * Distinguishes the two failure flavors PHPUnit reports: errors (unexpected
 * exceptions) and failures (assertion failures). The backed value is used as
 * the filename prefix when dumping a browser's state on failure.
 *
 * @author Hugo Hamon <hello@kodero.fr>
 */
enum FailureType: string
{
    case Error = 'error';
    case Failure = 'failure';
}
