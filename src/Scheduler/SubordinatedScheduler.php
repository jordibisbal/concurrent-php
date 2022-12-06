<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Scheduler;

use j45l\concurrentPhp\Coroutine\Coroutine;
use j45l\concurrentPhp\Infrastructure\SystemTicker;
use j45l\concurrentPhp\Infrastructure\Ticker;
use Throwable;

use function Functional\each;
use function Functional\map;
use function Functional\select;
use function Functional\some;
use function j45l\concurrentPhp\Functions\exponentialAverage;
use function j45l\functional\nop;

class SubordinatedScheduler extends Scheduler
{
    public static function from(Scheduler $scheduler): self
    {
        return new self(
            $scheduler->ticker,
            $scheduler->quantumTime,
            $scheduler->loadExponentialFactor
        );
    }

    protected function next(mixed $startTime): float
    {
        try {
            Coroutine::suspend();
        } catch (Throwable) {
        }

        return 1;
    }
}
