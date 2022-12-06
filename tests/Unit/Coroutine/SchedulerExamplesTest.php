<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Test\Unit\Coroutine;

use j45l\concurrentPhp\Channel\Channel;
use j45l\concurrentPhp\Coroutine\SimpleCoroutine;
use j45l\concurrentPhp\Infrastructure\Ticker;
use j45l\concurrentPhp\Scheduler\Scheduler;
use j45l\concurrentPhp\Test\Unit\Coroutine\Stubs\TestTicker;
use PHPUnit\Framework\TestCase;
use Throwable;
use function Functional\map;
use function Functional\repeat;
use function j45l\concurrentPhp\Channel\reduceChannel;
use function j45l\concurrentPhp\Coroutine\suspend;
use function j45l\concurrentPhp\Functions\exponentialAverage;
use function PHPUnit\Framework\assertEquals;

final class SchedulerExamplesTest extends TestCase
{
    /** @throws Throwable */
    public function testCollectingFromCors(): void
    {
        $channel = Channel::create();
        $ticker = TestTicker::create();
        $scheduler = Scheduler::create();

        $ping = $this->sender($channel, 'ping', 2, 0, $ticker);
        $pong = $this->sender($channel, 'pong', 2, 0, $ticker);
        $collector = $this->collector($channel);

        $channel->closeOn(fn () => $ping->isTerminated() && $pong->isTerminated());

        $scheduler->schedule($ping, $pong, $collector)->run();

        assertEquals(['ping', 'pong', 'ping', 'pong'], $collector->returnValue()->getOrElse(null));
    }

    /** @throws Throwable */
    public function testCollectingFromCorsOnce(): void
    {
        $channel = Channel::create();
        $ticker = TestTicker::create();
        $scheduler = Scheduler::create();

        $ping = $this->sender($channel, 'ping', 2, 0, $ticker);
        $pong = $this->sender($channel, 'pong', 2, 0, $ticker);
        $collector = $this->onceCollector($channel);

        $channel->closeOn(fn () => $ping->isTerminated() && $pong->isTerminated());

        $scheduler->schedule($ping, $pong, $collector)->run();

        assertEquals(['ping', 'pong'], $collector->returnValue()->getOrElse(null));
    }

    /**
     * @throws Throwable
     */
    public function testGettingLoadAverage(): void
    {
        $channel = Channel::create();
        $ticker = TestTicker::create();
        $scheduler = Scheduler::create($ticker, 100, 0.5);

        $ping = $this->sender($channel, 'ping', 10, 10, $ticker);
        $pong = $this->sender($channel, 'pong', 10, 20, $ticker);
        $collector = $this->loadMonitor($scheduler, $channel);

        $channel->closeOn(fn () => $ping->isTerminated() && $pong->isTerminated());

        $scheduler->schedule($ping, $pong, $collector)->run();

        assertEquals(
            map(range(0, 9), fn ($rounds) => $this->loadAverage($rounds + 1, 0.3)),
            $collector->returnValue()->getOrElse(null)
        );
    }

    /**
     * @param Channel<string> $channel
     * @return SimpleCoroutine<string>
     */
    private function sender(Channel $channel, string $message, int $count, float $time, Ticker $ticker): SimpleCoroutine
    {
        return SimpleCoroutine::create(static function () use ($channel, $count, $message, $time, $ticker) {
            repeat(function () use ($channel, $message, $time, $ticker) {
                $channel->put($message);
                $ticker->sleep($time);
                suspend();
            })($count);
        });
    }

    /**
     * @param Channel<string> $channel
     * @return SimpleCoroutine<array<string>>
     */
    private function collector(Channel $channel): SimpleCoroutine
    {
        return SimpleCoroutine::create(static function () use ($channel) {
            return reduceChannel($channel, fn($initial, $element) => [...$initial, $element], []);
        });
    }

    /**
     * @param Channel<string> $channel
     * @return SimpleCoroutine<array<string>>
     */
    private function onceCollector(Channel $channel): SimpleCoroutine
    {
        return SimpleCoroutine::create(static function () use ($channel) {
            return reduceChannel($channel, fn($initial, $element) => [...$initial, $element], [], untilClosed: false);
        });
    }

    /**
     * @param Channel<string> $channel
     * @return SimpleCoroutine<array<float>>
     */
    private function loadMonitor(Scheduler $scheduler, Channel $channel): SimpleCoroutine
    {
        return SimpleCoroutine::create(static function () use ($scheduler, $channel) {
            $loads = [];
            while (!$channel->closed()) {
                $loads[] = $scheduler->loadAverage();
                SimpleCoroutine::suspend();
            }

            return $loads;
        });
    }

    private function loadAverage(int $rounds, float $amount, float $alpha = 0.5): float
    {
        return match (true) {
            $rounds > 1 => exponentialAverage([$this->loadAverage($rounds - 1, $amount, $alpha), $amount], $alpha),
            default => 0
        };
    }
}
