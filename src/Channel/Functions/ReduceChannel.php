<?php
declare(strict_types=1);

namespace j45l\concurrentPhp\Channel;

use j45l\concurrentPhp\Coroutine\Coroutine;

/**
 * @template T
 * @template TAccumulator
 * @param Channel<T> $channel
 * @param callable(TAccumulator, T|array<T>):TAccumulator $fnAccumulator
 * @param TAccumulator $initial
 * @return TAccumulator
 */
function reduceChannel(
    Channel $channel,
    callable $fnAccumulator,
    mixed $initial,
    bool $untilClosed = true
): mixed {
    $accumulator = $initial;

    while (!$channel->empty() || ($untilClosed && !$channel->closed())) {
        match (true) {
            $channel->empty() => Coroutine::suspend(),
            default => $accumulator = $fnAccumulator($accumulator, $channel->get())
        };
    }

    return $accumulator;
}