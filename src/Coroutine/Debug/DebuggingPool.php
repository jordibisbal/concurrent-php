<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Coroutine\Debug;

use Closure;
use j45l\concurrentPhp\Channel\Channel;
use j45l\concurrentPhp\Coroutine\Job;
use j45l\concurrentPhp\Coroutine\Pool;
use j45l\concurrentPhp\Scheduler\Scheduler;
use j45l\concurrentPhp\Scheduler\SubordinatedScheduler;
use j45l\concurrentPhp\Scheduler\Task;

use function j45l\concurrentPhp\Functions\also;
use function j45l\concurrentPhp\Scheduler\SubordinatedScheduler;
use function j45l\functional\with;

final class DebuggingPool extends Pool
{
    /** @var Channel<string> */
    private Channel $debuggingChannel;

    /**
     * @param Channel<string> $debuggingChannel
     * @param Closure(): bool $endPredicate
     */
    public function __construct(Scheduler $scheduler, Closure $endPredicate, string $name, Channel $debuggingChannel)
    {
        $this->debuggingChannel = $debuggingChannel;

        parent::__construct($scheduler, $endPredicate, $name);
    }

    /**
     * @param Scheduler $scheduler
     * @return SubordinatedScheduler
     */
    protected function buildSubordinatedScheduler(Scheduler $scheduler): SubordinatedScheduler
    {
        return SubordinatedScheduler($scheduler, $this->debuggingChannel);
    }

    protected function launch(Job $job, string $name): Task
    {
        $this->debuggingChannel->put(
            sprintf(
                '  📁🚀 Pool %s#%s: Launched',
                $this->name,
                $this->id
            )
        );

        return with(parent::launch($job, $name))(
            fn (Task $task) => also(fn () => $this->debuggingChannel->put(
                sprintf(
                    '  📁🚀 Pool %s#%s: Launching from job %s coroutine %s#%s - %s',
                    $this->name,
                    $this->id,
                    $name,
                    $task->coroutine->name,
                    $task->coroutine->id,
                    $task->coroutine::class,
                )
            ))($task)
        );
    }

    function loop(): void
    {
        $this->debuggingChannel->put(sprintf(
            '  📁🏳️ Starting pool %s#%s',
            $this->name,
            $this->id
        ));

        parent::loop();

        $this->debuggingChannel->put(sprintf(
            '  📁🏁 Finishing pool %s#%s',
            $this->name,
            $this->id
        ));
    }
}
