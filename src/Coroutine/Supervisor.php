<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Coroutine;

use Closure;
use j45l\concurrentPhp\Scheduler\Scheduler;
use j45l\concurrentPhp\Scheduler\SubordinatedScheduler;
use Throwable;

use function Functional\each;
use function j45l\functional\doUntil;
use function j45l\functional\doWhile;

/**
 * @extends Coroutine<null>
 */
final class Supervisor extends Coroutine
{
    private static int $taskId = 1;

    private SubordinatedScheduler $scheduler;

    /** @var array<Task> */
    private array $tasks = [];
    private Closure $endPredicate;

    /**
     * @param Closure(): bool $endPredicate
     * @throws Throwable
     */
    public function __construct(Scheduler $scheduler, Closure $endPredicate)
    {
        $this->endPredicate = $endPredicate;
        $this->scheduler = SubordinatedScheduler::from($scheduler)
            ->schedule(SimpleCoroutine($this->loop(...)));

        parent::__construct(fn() => $this->loop(...));
    }

    /** @throws Throwable */
    public static function from(Scheduler $scheduler, Closure $endPredicate): self
    {
        return new self($scheduler, $endPredicate);
    }

    /**
     * @param Closure():Agent<mixed> $factory
     */
    public function schedule(Closure $factory, int $copies = null, string $name = null): self
    {
        $this->tasks[match (true) {
            !is_null($name) => $name,
            default => sprintf('#%s', self::$taskId++)
        }] = Task::create($factory, $copies ?? 1);

        return $this;
    }

    /** @throws Throwable */
    private function loop(): void
    {
        doUntil(
            $this->endPredicate,
            function (): void {
                each(
                    $this->tasks,
                    /** @phpstan-ignore-next-line  */
                    fn(Task $task, string $name) => doWhile(
                        fn() => count($this->scheduler->suspendedCoroutines($name)) < $task->copies,
                        fn() => $this->scheduler->schedule(($task->factory)())
                    )
                );

                Coroutine::suspend();
            }
        );
    }
}
