<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Coroutine;

use Closure;
use j45l\concurrentPhp\Scheduler\Scheduler;
use Throwable;

function Coroutine(Closure $fn, string $name = null): Coroutine
{
    return new Coroutine($fn, $name);
}

function Pool(Scheduler $scheduler, Closure $endPredicate, string $name = null): Pool
{
    return new Pool($scheduler, $endPredicate, $name ?? 'unnamed');
}