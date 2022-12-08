<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Scheduler\Debug;

use j45l\concurrentPhp\Channel\Channel;
use j45l\concurrentPhp\Infrastructure\Ticker;
use j45l\concurrentPhp\Scheduler\MainScheduler;

use function j45l\concurrentPhp\Channel\BlackHole;

final class DebuggingMainScheduler extends MainScheduler
{
    use DebuggingTrait;

    /** @var Channel<string> */
    protected Channel $debuggingChannel;

    /**
     * @param Ticker $ticker
     * @param float|null $quantumTime
     * @param float|null $loadExponentialFactor
     * @param Channel<string>|null $debuggingChannel
     */
    public function __construct(
        Ticker $ticker,
        float $quantumTime = null,
        float $loadExponentialFactor = null,
        Channel $debuggingChannel = null
    ) {
        parent::__construct($ticker, $quantumTime, $loadExponentialFactor);
        $this->debuggingChannel = $debuggingChannel ?? BlackHole();
    }

    protected function reference(): string
    {
        return sprintf('MainScheduler %s#%s:', parent::class, $this->id);
    }
}
