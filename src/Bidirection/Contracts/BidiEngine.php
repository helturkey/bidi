<?php

declare(strict_types=1);

namespace Hussein\Bidirection\Contracts;

/**
 * Converts a logical Unicode string into the visual order expected by a
 * rendering target that cannot perform the Unicode Bidirectional Algorithm.
 */
interface BidiEngine
{
    /**
     * @param bool|string $forceRtl Legacy direction override passed to the engine.
     */
    public function toVisual(string $text, bool|string $forceRtl = false): string;
}
