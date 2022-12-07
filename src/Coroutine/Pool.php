<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Coroutine;

use Closure;
use j45l\concurrentPhp\Scheduler\Scheduler;
use j45l\concurrentPhp\Scheduler\SubordinatedScheduler;
use Throwable;

use function Functional\each;
use function j45l\concurrentPhp\Scheduler\SubordinatedScheduler;
use function j45l\concurrentPhp\Scheduler\Task;
use function j45l\functional\doUntil;
use function j45l\functional\doWhile;

final class Pool extends Coroutine
{
    private static int $jobId = 1;

    private SubordinatedScheduler $scheduler;

    /** @var array<Job> */
    private array $jobs = [];
    private Closure $endPredicate;

    /**
     * @param Closure(): bool $endPredicate
     */
    public function __construct(Scheduler $scheduler, Closure $endPredicate, string $name)
    {
        $this->endPredicate = $endPredicate;
        $this->scheduler = SubordinatedScheduler($scheduler)
            ->schedule(Task(Coroutine($this->loop(...)), 'loop'));

        parent::__construct($this->loop(...), $name);
    }

    /**
     * @param Closure():Agent $factory
     */
    public function schedule(Closure $factory, int $copies = null, string $name = null): self
    {
        $this->jobs[match (true) {
            !is_null($name) => $name,
            default => sprintf('#%s', self::$jobId++)
        }] = Job::create($factory, $copies ?? 1);

        return $this;
    }

    /** @throws Throwable */
    private function loop(): void
    {
        doWhile(
            $this->endPredicate,
            function (): void {
                each(
                    $this->jobs,
                    fn(Job $task, string $name) => doWhile(
                        fn() => count($this->scheduler->aliveTasks($name)) < $task->copies,
                        fn() => $this->scheduler->schedule(Task(($task->factory)(), $name))
                    )
                );

                $this->scheduler->run();
                Coroutine::suspend();
            }
        );
    }
}
