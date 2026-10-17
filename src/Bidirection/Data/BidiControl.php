<?php

declare(strict_types=1);

namespace Hussein\Bidirection\Data;

/**
 * Unicode bidirectional formatting controls used by the legacy algorithm.
 */
final class BidiControl
{
    public const LRM = 8206;
    public const RLM = 8207;
    public const LRE = 8234;
    public const RLE = 8235;
    public const PDF = 8236;
    public const LRO = 8237;
    public const RLO = 8238;

    private function __construct()
    {
    }
}
