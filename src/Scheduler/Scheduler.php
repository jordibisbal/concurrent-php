<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Scheduler;

use Closure;
use j45l\concurrentPhp\Coroutine\Coroutine;
use j45l\concurrentPhp\Infrastructure\Ticker;
use Throwable;

interface Scheduler
{
    public function loadAverage(): float;

    /**
     * @phpstan-param (Closure(Throwable):void)|null $onThrowable
     * @return $this
     */
    public function setOnThrowable(callable $onThrowable = null): static;

    public function run(): void;

    /**
     * @phpstan-param  Task|Coroutine|Closure():mixed $taskable
     */
    public function schedule(mixed ...$taskable): static;

    /** @return array<Task> */
    public function suspendedTasks(string $name = null): array;

    /** @return array<Task> */
    public function terminatedTasks(string $name = null): array;

    /** @return array<Task> */
    public function aliveTasks(string $name = null): array;

    public function ticker(): Ticker;

    public function quantumTime(): float;

    public function loadExponentialFactor(): float;
}
