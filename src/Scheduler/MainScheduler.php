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

class MainScheduler implements Scheduler
{
    private const DEFAULT_QUANTUM_TIME = 1 / 100;
    protected const DEFAULT_LOAD_EXPONENTIAL_FACTOR = 0.9;

    protected static int $nextId = 1;
    protected int $id;
    protected Ticker $ticker;
    protected float $quantumTime;
    protected float $loadExponentialFactor;
    protected mixed $onThrowable;
    /** @var array<Task> */
    private array $tasks;
    private float $loadAverage;
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
     * @return void
     */
    public function startTasks(): void
    {
        each($this->notStartedTasks(), $this->startTask(...));
    }

    public function id(): int
    {
        return $this->id;
    }

    public function loadAverage(): float
    {
        return $this->loadAverage;
    }

    public function loadExponentialFactor(): float
    {
        return $this->loadExponentialFactor;
    }

    /**
     * @return array<Task>
     */
    public function notStartedTasks(): array
    {
        return select(
            $this->tasks,
            fn(Task $task) => !($task->coroutine->isStarted())
        );
    }

    public function quantumTime(): float
    {
        return $this->quantumTime;
    }

    public function run(): void
    {
        if ($this->running) {
            return;
        }

        $this->running = true;

        $startTime = $this->ticker->time();

        while (some($this->tasks, fn (Task $task) => !$task->coroutine->isTerminated())) {
            $this->loadAverage = $this->startLoop($startTime);
            $this->startTasks();
            $startTime = $this->nextLoop($startTime);
            each($this->suspendedTasks(), fn (Task $task) => $this->resumeTask($task));
            $this->endLoop();
        }

        $this->running = false;
    }

    /**
     * @phpstan-param  Task|Coroutine|Closure():mixed $taskable
     */
    public function schedule(mixed ...$taskable): static
    {
        $this->tasks = [
            ...$this->tasks,
            ...map($taskable, fn($task) => Task($task))
        ];

        $this->setOnThrowable($this->onThrowable);

        return $this;
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

    public function ticker(): Ticker
    {
        return $this->ticker;
    }

    /**
     * @param mixed $startTime
     * @return float
     */
    protected function nextLoop(mixed $startTime): float
    {
        return $this->sleepUntil($startTime + $this->quantumTime, $this->ticker);
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

    /**
     * @param float $startTime
     * @return float
     */
    protected function startLoop(float $startTime): float
    {
        return exponentialAverage(
            [$this->loadAverage, $this->elapsedSince($startTime, $this->ticker) / $this->quantumTime],
            $this->loadExponentialFactor
        );
    }

    protected function throwableThrown(Throwable $throwable): void
    {
        ($this->onThrowable)($throwable);
    }

    private function elapsedSince(float $time, Ticker $ticker): float
    {
        return $ticker->time() - $time;
    }

    protected function endLoop(): void
    {
        each(
            select($this->tasks, fn (Task $tasks) => $tasks->coroutine->isTerminated()),
            function ($task, $index) {
                unset($this->tasks[$index]);
            }
        );
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
}
