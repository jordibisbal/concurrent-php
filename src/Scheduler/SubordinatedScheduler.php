<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Scheduler;

use j45l\concurrentPhp\Coroutine\Coroutine;
use Throwable;

class SubordinatedScheduler extends Scheduler
{
    protected function next(mixed $startTime): float
    {
        try {
            Coroutine::suspend();
        } catch (Throwable $throwable) {
            ($this->onThrowable)($throwable);
        }

        return 1;
    }
}
