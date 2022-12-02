<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Functions;

use function Functional\reduce_left as reduce;

/**
 * @param array<float> $samples
 */
function exponentialAverage(array $samples, float $alpha): float
{
    return reduce(
        $samples,
        fn ($sample, $_1, $_2, $initial) => (1 - $alpha) * $initial + $alpha * $sample,
        $samples[0] ?? 0.0
    );
}
