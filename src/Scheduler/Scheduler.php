<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Scheduler;

use j45l\concurrentPhp\Coroutine\Coroutine;
use j45l\concurrentPhp\Infrastructure\SystemTicker;
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
    private const DEFAULT_QUANTUM_TIME = 1 / 1_000;
    protected const DEFAULT_LOAD_EXPONENTIAL_FACTOR = 0.9;

    /** @var array<Coroutine<mixed>> */
    private array $coroutines;

    private float $loadAverage;
    protected Ticker $ticker;
    protected float $quantumTime;
    protected float $loadExponentialFactor;
    private mixed $onThrowable;

    protected function __construct(Ticker $ticker, float $quantumTime, float $loadExponentialFactor)
    {
        $this->coroutines = [];
        $this->loadAverage = 0.0;
        $this->ticker = $ticker;
        $this->quantumTime = $quantumTime;
        $this->loadExponentialFactor = $loadExponentialFactor;
        $this->onThrowable = nop(...);
    }

    public static function create(
        Ticker $ticker = null,
        float $quantumSeconds = null,
        float $loadExponentialFactor = null
    ): Scheduler {
        return new self(
            $ticker ?? SystemTicker::create(),
            $quantumSeconds ?? self::DEFAULT_QUANTUM_TIME,
            $loadExponentialFactor ?? self::DEFAULT_LOAD_EXPONENTIAL_FACTOR
        );
    }

    public function loadAverage(): float
    {
        return $this->loadAverage;
    }

    /**
     * @param Closure(Throwable):void|null $onThrowable
     * @return $this
     */
    public function onThrowable(callable $onThrowable = null): static
    {
        $this->onThrowable = $onThrowable ?? nop(...);

        return $this;
    }

    public function run(): void
    {
        $startTime = $this->ticker->time();
        each($this->coroutines, $this->startCoroutine(...));

        while (some($this->coroutines, fn (Coroutine $coroutine) => !$coroutine->isTerminated())) {
            $this->loadAverage = exponentialAverage(
                [$this->loadAverage, $this->elapsedSince($startTime, $this->ticker) / $this->quantumTime],
                $this->loadExponentialFactor
            );

            $startTime = $this->next($startTime);

            each(
                select(
                    $this->coroutines,
                    fn(Coroutine $coroutine) => $coroutine->isSuspended()
                ),
                fn ($coroutine) => $this->resumeCoroutine($coroutine)
            );
        }
    }

    /**
     * @phpstan-param Coroutine<mixed> $coroutines
     * @return static
     */
    public function schedule(Coroutine ...$coroutines): static
    {
        $this->coroutines = [
            ...$this->coroutines,
            ...map($coroutines, fn (Coroutine $coroutine) => $coroutine->onThrowable($this->throwableThrown(...)))
        ];

        return $this;
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

    /** @param Coroutine<mixed> $coroutine */
    private function resumeCoroutine(Coroutine $coroutine): mixed
    {
        try {
            return $coroutine->resume();
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

    /** @param Coroutine<mixed> $coroutine */
    private function startCoroutine(Coroutine $coroutine): void
    {
        try {
            $coroutine->start();
        } catch (Throwable $throwable) {
            $this->throwableThrown($throwable);
        }
    }

    /**
     * @return array<Coroutine<mixed>>
     */
    public function suspendedCoroutines(string $name = null): array
    {
        return select(
            $this->coroutines,
            fn (Coroutine $coroutine) =>
                ($coroutine->name === $name || is_null($name))
                && $coroutine->isSuspended()
        );
    }
}
