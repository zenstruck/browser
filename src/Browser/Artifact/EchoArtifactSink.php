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
 * Preserves the historical PHPUnit-extension output: a plain `echo` of the
 * "Saved Browser Artifacts" summary at end of suite.
 *
 * @author Hugo Hamon <hello@kodero.fr>
 */
final class EchoArtifactSink implements ArtifactSink
{
    public function writeSummary(array $savedArtifacts): void
    {
        if ($savedArtifacts === []) {
            return;
        }

        echo "\n\nSaved Browser Artifacts:";

        foreach ($savedArtifacts as $test => $categories) {
            echo "\n\n  {$test}";

            foreach ($categories as $category => $artifacts) {
                echo "\n    {$category}:";

                foreach ($artifacts as $artifact) {
                    echo "\n      * {$artifact}:";
                }
            }
        }
    }
}
