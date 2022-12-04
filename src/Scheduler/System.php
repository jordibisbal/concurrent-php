<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Scheduler;

use j45l\concurrentPhp\Coroutine\Coroutine;
use Throwable;

final class System extends Scheduler
{
    protected function next(mixed $startTime): float
    {
        try {
            Coroutine::suspend();
        } catch (Throwable) {
        }

        return 1;
    }
}
