<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Scheduler;

use j45l\concurrentPhp\Coroutine\Coroutine;
use Throwable;

class SubordinatedScheduler extends MainScheduler
{
    protected function nextLoop(mixed $startTime): float
    {
        try {
            Coroutine::suspend();
        } catch (Throwable $throwable) {
            ($this->onThrowable)($throwable);
        }

        return 1;
    }

    public function loadAverage(): float
    {
        return 1;
    }
}
