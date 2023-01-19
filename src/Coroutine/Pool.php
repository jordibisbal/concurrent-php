<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Coroutine;

use Closure;
use j45l\concurrentPhp\Scheduler\Scheduler;
use j45l\concurrentPhp\Scheduler\SubordinatedScheduler;
use j45l\concurrentPhp\Scheduler\Task;
use Throwable;

use function Functional\each;
use function j45l\concurrentPhp\Functions\also;
use function j45l\concurrentPhp\Scheduler\SubordinatedScheduler;
use function j45l\concurrentPhp\Scheduler\Task;
use function j45l\functional\doWhile;
use function j45l\functional\with;

class Pool extends Coroutine
{
    private static int $jobId = 1;

    private Scheduler $scheduler;

    /** @var array<Job> */
    private array $jobs = [];
    private Closure $endPredicate;

    /**
     * @param Closure(): bool $endPredicate
     */
    public function __construct(Scheduler $scheduler, Closure $endPredicate, string $name)
    {
        parent::__construct($this->loop(...), $name);

        $this->endPredicate = $endPredicate;
        $this->scheduler = $this->buildSubordinatedScheduler($scheduler)
            ->schedule(Task($this->loop(...), $this->poolName()));
    }

    /**
     * @param Scheduler $scheduler
     * @return SubordinatedScheduler
     */
    protected function buildSubordinatedScheduler(Scheduler $scheduler): SubordinatedScheduler
    {
        return SubordinatedScheduler($scheduler);
    }

    public function poolName(): string
    {
        return sprintf('%s#%s loop', $this->name, $this->id);
    }

    /**
     * @param Closure():Agent $factory
     */
    public function schedule(Closure $factory, int $copies = null, string $name = null): self
    {
        $this->jobs[sprintf('%s#%s', $name ?? $this->poolName(), self::$jobId++)] =
            Job::create($factory, $copies ?? 1);

        return $this;
    }

    protected function launch(Job $job, string $name): Task
    {
        return with(Task(($job->factory)(), $name))(
            fn (Task $task) =>
                also(fn (Task $task) => $this->scheduler->schedule($task))($task)
        );
    }

    /** @throws Throwable */
    protected function loop(): void
    {
        doWhile(
            fn () => !(($this->endPredicate)()),
            function (): void {
                each(
                    $this->jobs,
                    fn(Job $task, string $name) => doWhile(
                        fn() => count($this->scheduler->aliveTasks($name)) < $task->copies,
                        fn() => $this->launch($task, $name)
                    )
                );

                $this->scheduler->run();
                Coroutine::suspend();
            }
        );
    }
}
