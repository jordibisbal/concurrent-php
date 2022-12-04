<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Scheduler;

use j45l\concurrentPhp\Coroutine\Coroutine;
use j45l\concurrentPhp\Infrastructure\SystemTicker;
use j45l\concurrentPhp\Infrastructure\Ticker;
use Throwable;

use function Functional\each;
use function Functional\select;
use function Functional\some;
use function j45l\concurrentPhp\Functions\exponentialAverage;

class Scheduler
{
    private const DEFAULT_QUANTUM_TIME = 1 / 1_000;
    private const DEFAULT_LOAD_EXPONENTIAL_FACTOR = 0.9;

    /** @var array<Coroutine<mixed>> */
    private array $concurrentPhp;

    private float $loadAverage;
    private Ticker $ticker;
    private float $quantumTime;
    private float $loadExponentialFactor;

    private function __construct(Ticker $ticker, float $quantumTime, float $loadExponentialFactor)
    {
        $this->concurrentPhp = [];
        $this->loadAverage = 0.0;
        $this->ticker = $ticker;
        $this->quantumTime = $quantumTime;
        $this->loadExponentialFactor = $loadExponentialFactor;
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

    /** @param Coroutine<mixed> $coroutine */
    private function startCoroutine(Coroutine $coroutine): void
    {
        try {
            $coroutine->start();
        } catch (Throwable) {
        }
    }

    public function loadAverage(): float
    {
        return $this->loadAverage;
    }

    public function run(): void
    {
        $startTime = $this->ticker->time();
        each($this->concurrentPhp, $this->startCoroutine(...));

        while (some($this->concurrentPhp, fn (Coroutine $coroutine) => !$coroutine->isTerminated())) {
            $this->loadAverage = exponentialAverage(
                [$this->loadAverage, $this->elapsedSince($startTime, $this->ticker) / $this->quantumTime],
                $this->loadExponentialFactor
            );

            $startTime = $this->next($startTime);

            each(
                select(
                    $this->concurrentPhp,
                    fn(Coroutine $coroutine) => $coroutine->isSuspended()
                ),
                fn ($coroutine) => $this->resumeCoroutine($coroutine)
            );
        }
    }

    /**
     * @param Coroutine<mixed> $concurrentPhp
     * @return Scheduler
     */
    public function schedule(...$concurrentPhp): Scheduler
    {
        $this->concurrentPhp = [...$this->concurrentPhp, ...$concurrentPhp];

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

    private function elapsedSince(float $time, Ticker $ticker): float
    {
        return $ticker->time() - $time;
    }

    /** @param Coroutine<mixed> $coroutine */
    private function resumeCoroutine(Coroutine $coroutine): mixed
    {
        try {
            return $coroutine->resume();
        } catch (Throwable) {
            return null;
        }
    }

    private function sleepUntil(float $targetTime, Ticker $ticker): float
    {
        $ticker->sleep(max(0, $targetTime - $ticker->time()));

        return $ticker->time();
    }
}
