<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Coroutine;

use Closure;
use j45l\concurrentPhp\Channel\Channel;
use j45l\concurrentPhp\Coroutine\Debug\DebuggingPool;
use j45l\concurrentPhp\Scheduler\Scheduler;

function Coroutine(Closure $fn, string $name = null): Coroutine
{
    return new Coroutine($fn, $name);
}

/** @param Channel<string>|null $debuggingChannel */
function Pool(Scheduler $scheduler, Closure $endPredicate, string $name = null, Channel $debuggingChannel = null): Pool
{
    return match (true) {
        !is_null($debuggingChannel) =>
            new DebuggingPool($scheduler, $endPredicate, $name ?? 'unnamed', $debuggingChannel),
        default => new Pool($scheduler, $endPredicate, $name ?? 'unnamed')
    };
}
