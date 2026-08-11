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
 * Destination for the end-of-suite "Saved Browser Artifacts" summary. PHPUnit
 * uses {@see EchoArtifactSink}; Behat ships a sink that writes through Behat's
 * output printer instead of plain echo.
 *
 * @author Hugo Hamon <hello@kodero.fr>
 */
interface ArtifactSink
{
    /**
     * @param array<string, array<string, string[]>> $savedArtifacts indexed by test name then category
     */
    public function writeSummary(array $savedArtifacts): void;
}
