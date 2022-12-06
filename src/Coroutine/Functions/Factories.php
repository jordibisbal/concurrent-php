<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Coroutine;

use Closure;
use Throwable;

/**
 * @template TResult
 * @param Closure(): TResult $fn
 * @return SimpleCoroutine<TResult>
 * @throws Throwable
 */
function SimpleCoroutine(Closure $fn): SimpleCoroutine
{
    return SimpleCoroutine::create($fn);
}
