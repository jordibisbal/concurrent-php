<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Scheduler;

use j45l\concurrentPhp\Channel\Channel;
use j45l\concurrentPhp\Infrastructure\Ticker;
use Throwable;

use function get_class as getClass;
use function j45l\concurrentPhp\Channel\BlackHole;

class DebuggingScheduler extends Scheduler
{
    /** @var Channel<string> */
    private Channel $debuggingChannel;

    /**
     * @param Ticker $ticker
     * @param float|null $quantumTime
     * @param float|null $loadExponentialFactor
     * @param Channel<string>|null $debuggingChannel
     */
    public function __construct(
        Ticker $ticker,
        float $quantumTime = null,
        float $loadExponentialFactor = null,
        Channel $debuggingChannel = null
    ) {
        parent::__construct($ticker, $quantumTime, $loadExponentialFactor);
        $this->debuggingChannel = $debuggingChannel ?? BlackHole();
    }

    /** @throws Throwable */
    protected function next(mixed $startTime): float
    {
        $this->debuggingChannel->put(sprintf(
            'SCH:#%s [N: %s S: %s, T: %s] Next @ %s',
            $this->id,
            count($this->notStartedTasks()),
            count($this->suspendedTasks()),
            count($this->terminatedTasks()),
            $startTime,
        ));

        return parent::next($startTime);
    }

    /** @throws Throwable */
    protected function resumeTask(Task $task): mixed
    {
        $this->debuggingChannel->put(sprintf(
            'SCH:#%s Resuming coroutine %s (%s - %s)',
            $this->id,
            $task->coroutine->id,
            sprintf('%s:%s', $task->pool, $task->coroutine->name),
            getClass($task->coroutine)
        ));

        return parent::resumeTask($task);
    }
}
