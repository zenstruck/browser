<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Bridge\Behat\Output;

use Zenstruck\Browser\Artifact\ArtifactSink;

/**
 * Behat-friendly {@see ArtifactSink} that writes the end-of-suite "Saved
 * Browser Artifacts" summary directly to STDOUT, bypassing Behat's formatter
 * manager so the summary always appears even with `--format=progress`.
 *
 * @author Hugo Hamon <hello@kodero.fr>
 */
final class BehatOutputArtifactSink implements ArtifactSink
{
    public function writeSummary(array $savedArtifacts): void
    {
        if ($savedArtifacts === []) {
            return;
        }

        $output = "\n\nSaved Browser Artifacts:";

        foreach ($savedArtifacts as $test => $categories) {
            $output .= "\n\n  {$test}";

            foreach ($categories as $category => $artifacts) {
                $output .= "\n    {$category}:";

                foreach ($artifacts as $artifact) {
                    $output .= "\n      * {$artifact}:";
                }
            }
        }

        \fwrite(\STDOUT, $output."\n");
    }
}
