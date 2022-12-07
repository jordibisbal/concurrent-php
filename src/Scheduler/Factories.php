<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Scheduler;

use Closure;
use j45l\concurrentPhp\Channel\Channel;
use j45l\concurrentPhp\Coroutine\Coroutine;
use j45l\concurrentPhp\Infrastructure\SystemTicker;
use j45l\concurrentPhp\Infrastructure\Ticker;
use RuntimeException;

use function is_null as isNull;
use function j45l\concurrentPhp\Coroutine\Coroutine;

function Task(mixed $task, string $pool = null): Task
{
    return match (true) {
        ($task instanceof Task) && !isNull($pool) => new Task($task->coroutine, $pool),
        $task instanceof Task => $task,
        $task instanceof Coroutine => new Task($task, $pool ?? get_class($task)),
        $task instanceof Closure => new Task(Coroutine($task), $pool ?? Closure::class),
        default => throw new RuntimeException(sprintf(
            'Task, Coroutine or Closure expected in %s, but %s found',
            __CLASS__,
            get_debug_type($task)
        ))
    };
}

/**
 * @param Channel<string>|null $debuggingChannel
 * @return Scheduler
 */
function Scheduler(
    Ticker $ticker = null,
    float $quantumSeconds = null,
    float $loadExponentialFactor = null,
    Channel $debuggingChannel = null
): Scheduler {
    return match (true) {
        !isNull($debuggingChannel) =>  new DebuggingScheduler(
            $ticker ?? SystemTicker::create(),
            $quantumSeconds,
            $loadExponentialFactor,
            $debuggingChannel
        ),
        default => new Scheduler(
            $ticker ?? SystemTicker::create(),
            $quantumSeconds,
            $loadExponentialFactor
        )
    };
}

function SubordinatedScheduler(Scheduler $scheduler): SubordinatedScheduler
{
    return new SubordinatedScheduler(
        $scheduler->ticker(),
        $scheduler->quantumTime(),
        $scheduler->loadExponentialFactor()
    );
}