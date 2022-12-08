<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Scheduler;

use Closure;
use j45l\concurrentPhp\Channel\Channel;
use j45l\concurrentPhp\Coroutine\Coroutine;
use j45l\concurrentPhp\Infrastructure\SystemTicker;
use j45l\concurrentPhp\Infrastructure\Ticker;
use j45l\concurrentPhp\Scheduler\Debug\DebuggingMainScheduler;
use j45l\concurrentPhp\Scheduler\Debug\DebuggingSubordinatedScheduler;
use RuntimeException;

use function is_null as isNull;
use function j45l\concurrentPhp\Coroutine\Coroutine;

function Task(mixed $task, string $poolName = null): Task
{
    return match (true) {
        ($task instanceof Task) && !isNull($poolName) => new Task($task->coroutine, $poolName),
        $task instanceof Task => $task,
        $task instanceof Coroutine => new Task($task, $poolName ?? get_class($task)),
        $task instanceof Closure => new Task(Coroutine($task), $poolName ?? Closure::class),
        default => throw new RuntimeException(sprintf(
            'Task, Coroutine or Closure expected in %s, but %s found',
            __CLASS__,
            get_debug_type($task)
        ))
    };
}

/** @param Channel<string>|null $debuggingChannel */
function Scheduler(
    Ticker $ticker = null,
    float $quantumSeconds = null,
    float $loadExponentialFactor = null,
    Channel $debuggingChannel = null
): MainScheduler {
    return match (true) {
        !isNull($debuggingChannel) =>  new DebuggingMainScheduler(
            $ticker ?? SystemTicker::create(),
            $quantumSeconds,
            $loadExponentialFactor,
            $debuggingChannel
        ),
        default => new MainScheduler(
            $ticker ?? SystemTicker::create(),
            $quantumSeconds,
            $loadExponentialFactor
        )
    };
}

/** @param Channel<string>|null $debuggingChannel */
function SubordinatedScheduler(Scheduler $scheduler, Channel $debuggingChannel = null): SubordinatedScheduler
{
    return match (true) {
        !isNull($debuggingChannel) => new DebuggingSubordinatedScheduler(
            $scheduler->ticker(),
            $scheduler->quantumTime(),
            $scheduler->loadExponentialFactor(),
            $debuggingChannel
        ),
        default => new SubordinatedScheduler(
            $scheduler->ticker(),
            $scheduler->quantumTime(),
            $scheduler->loadExponentialFactor()
        )
    };
}