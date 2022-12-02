<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Coroutine;

use Throwable;

/**
 * @template TResult
 * @param callable(): TResult $fn
 * @return Cor<TResult>
 * @throws Throwable
 */
function Cor(callable $fn): Cor
{
    return Cor::create($fn);
}
