<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Scheduler;

use Closure;
use j45l\concurrentPhp\Coroutine\Coroutine;
use j45l\concurrentPhp\Infrastructure\Ticker;
use Throwable;

use function Functional\each;
use function Functional\map;
use function Functional\select;
use function Functional\some;
use function j45l\concurrentPhp\Functions\exponentialAverage;
use function j45l\functional\nop;

class Scheduler
{
    private const DEFAULT_QUANTUM_TIME = 1 / 100;
    protected const DEFAULT_LOAD_EXPONENTIAL_FACTOR = 0.9;

    protected static int $nextId = 1;
    protected int $id;
    protected Ticker $ticker;
    protected float $quantumTime;
    protected float $loadExponentialFactor;
    /** @var array<Task> */
    private array $tasks;
    private float $loadAverage;
    protected mixed $onThrowable;
    private bool $running;

    public function __construct(Ticker $ticker, float $quantumTime = null, float $loadExponentialFactor = null)
    {
        $this->id = self::$nextId++;

        $this->running = false;
        $this->tasks = [];
        $this->loadAverage = 0.0;
        $this->ticker = $ticker;
        $this->quantumTime = $quantumTime  ?? self::DEFAULT_QUANTUM_TIME;
        $this->loadExponentialFactor = $loadExponentialFactor ?? self::DEFAULT_LOAD_EXPONENTIAL_FACTOR;
        $this->onThrowable = nop(...);
    }

    public function loadAverage(): float
    {
        return $this->loadAverage;
    }

    /**
     * @phpstan-param (Closure(Throwable):void)|null $onThrowable
     * @return $this
     */
    public function setOnThrowable(callable $onThrowable = null): static
    {
        $this->onThrowable = $onThrowable ?? nop(...);
        each($this->tasks, static fn (Task $task) => $task->coroutine->setOnThrowable($onThrowable));

        return $this;
    }

    public function run(): void
    {
        if ($this->running) {
            return;
        }

        $this->running = true;

        $startTime = $this->ticker->time();

        while (some($this->tasks, fn (Task $task) => !$task->coroutine->isTerminated())) {
            $this->loadAverage = exponentialAverage(
                [$this->loadAverage, $this->elapsedSince($startTime, $this->ticker) / $this->quantumTime],
                $this->loadExponentialFactor
            );

            each($this->notStartedTasks(), $this->startTask(...));

            $startTime = $this->next($startTime);

            each($this->suspendedTasks(), fn (Task $task) => $this->resumeTask($task));
        }

        $this->running = false;
    }

    /** @phpstan-param Task|Coroutine|(callable():mixed) $tasks */
    public function schedule(mixed ...$tasks): static
    {
        $this->tasks = [
            ...$this->tasks,
            ...map($tasks, fn ($task) => Task($task))
        ];

        $this->setOnThrowable($this->onThrowable);

        return $this;
    }

    public function subordinated(Closure ...$fns): self
    {
        return $this->schedule(...map($fns, fn ($fn) => $fn($this)));
    }

    /**
     * @return array<Task>
     */
    public function suspendedTasks(string $name = null): array
    {
        return select(
            $this->tasks,
            fn (Task $tasks) =>
                $tasks->coroutine->isSuspended() &&
                ($tasks->pool === $name || is_null($name))
        );
    }

    /**
     * @return array<Task>
     */
    public function terminatedTasks(string $name = null): array
    {
        return select(
            $this->tasks,
            fn (Task $tasks) =>
                $tasks->coroutine->isTerminated() &&
                ($tasks->pool === $name || is_null($name))
        );
    }

    /**
     * @return array<Task>
     */
    public function aliveTasks(string $name = null): array
    {
        return select(
            $this->tasks,
            fn (Task $task) =>
                !$task->coroutine->isTerminated()
                && ($task->pool === $name || is_null($name))
        );
    }

    /**
     * @return array<Task>
     */
    protected function notStartedTasks(): array
    {
        return select(
            $this->tasks,
            fn(Task $task) => !($task->coroutine->isStarted())
        );
    }

    /**
     * @param mixed $startTime
     * @return float
     */
    protected function next(mixed $startTime): float
    {
        return $this->sleepUntil($startTime + $this->quantumTime, $this->ticker);
    }

    protected function throwableThrown(Throwable $throwable): void
    {
        ($this->onThrowable)($throwable);
    }

    private function elapsedSince(float $time, Ticker $ticker): float
    {
        return $ticker->time() - $time;
    }

    protected function resumeTask(Task $task): mixed
    {
        try {
            return $task->coroutine->resume();
        } catch (Throwable $throwable) {
            $this->throwableThrown($throwable);

            return null;
        }
    }

    private function sleepUntil(float $targetTime, Ticker $ticker): float
    {
        $ticker->sleep(max(0, $targetTime - $ticker->time()));

        return $ticker->time();
    }

    private function startTask(Task $coroutine): void
    {
        try {
            $coroutine->coroutine->start();
        } catch (Throwable $throwable) {
            $this->throwableThrown($throwable);
        }
    }

    public function ticker(): Ticker
    {
        return $this->ticker;
    }

    public function quantumTime(): float
    {
        return $this->quantumTime;
    }

    public function loadExponentialFactor(): float
    {
        return $this->loadExponentialFactor;
    }
}
