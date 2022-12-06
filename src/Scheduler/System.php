<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Scheduler;

use j45l\concurrentPhp\Coroutine\Coroutine;
use j45l\concurrentPhp\Infrastructure\SystemTicker;
use j45l\concurrentPhp\Infrastructure\Ticker;
use Throwable;

final class System extends Scheduler
{
    protected function __construct(Ticker $ticker, float $quantumTime, float $loadExponentialFactor)
    {
        parent::__construct($ticker, 0, $loadExponentialFactor);
    }

    public static function create(
        Ticker $ticker = null,
        float $quantumSeconds = null,
        float $loadExponentialFactor = null,
        Closure $agentFactory,
        int $minAgents,
        Closure $raiseAgentWhile
    ): Scheduler {
        return new self(
            $ticker ?? SystemTicker::create(),
            0,
            $loadExponentialFactor ?? self::DEFAULT_LOAD_EXPONENTIAL_FACTOR
        );
    }


}
