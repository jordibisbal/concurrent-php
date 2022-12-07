<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Scheduler;

use j45l\concurrentPhp\Coroutine\Coroutine;

final class Task
{
    public function __construct(readonly public Coroutine $coroutine, readonly string $pool)
    {
    }
}
