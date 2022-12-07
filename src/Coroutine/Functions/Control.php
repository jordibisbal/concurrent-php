<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Coroutine;

use Throwable;

/**
 * @throws Throwable
 */
function suspend(): void
{
    Coroutine::suspend();
}
