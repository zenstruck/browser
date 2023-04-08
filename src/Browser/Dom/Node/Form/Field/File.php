<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Dom\Node\Form\Field;

use Zenstruck\Browser\Dom\Node\Form\Field;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class File extends Field
{
    public const SELECTOR = 'input[type="file"]';

    public function isMultiple(): bool
    {
        return $this->attributes()->has('multiple');
    }

    public function attach(string ...$filenames): void
    {
        if (\count($filenames) > 1 && !$this->isMultiple()) {
            throw new \InvalidArgumentException('Cannot attach multiple files to a non-multiple file input.');
        }

        foreach ($filenames as $filename) {
            if (!\file_exists($filename)) {
                throw new \InvalidArgumentException(\sprintf('File "%s" does not exist.', $filename));
            }
        }

        $this->ensureSession()->attach($this, $filenames);
    }
}
