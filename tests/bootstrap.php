<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\Filesystem\Filesystem;

require __DIR__.'/../vendor/autoload.php';

// paratest includes this bootstrap in every worker: only the parent process cleans up, and the
// cache dir is created up front so booting kernels do not race to create it. PARATEST is set for
// every worker, TEST_TOKEN only when tokens are enabled
if (!isset($_SERVER['PARATEST'])) {
    (new Filesystem())->remove(__DIR__.'/../var');
}

@\mkdir(__DIR__.'/../var/cache/'.($_SERVER['APP_ENV'] ?? 'test'), 0777, true);
