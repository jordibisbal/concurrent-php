<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Test\Unit\Coroutine;

use j45l\concurrentPhp\Channel\Channel;
use j45l\concurrentPhp\Coroutine\Coroutine;
use j45l\concurrentPhp\Infrastructure\Ticker;
use j45l\concurrentPhp\Scheduler\Scheduler;
use j45l\concurrentPhp\Test\Unit\Coroutine\Stubs\TestTicker;
use PHPUnit\Framework\TestCase;
use Throwable;

use function Functional\map;
use function Functional\repeat;
use function j45l\concurrentPhp\Channel\Channel;
use function j45l\concurrentPhp\Channel\MappedChannel;
use function j45l\concurrentPhp\Channel\reduceChannel;
use function j45l\concurrentPhp\Channel\RejectChannel;
use function j45l\concurrentPhp\Channel\SelectChannel;
use function j45l\concurrentPhp\Coroutine\Coroutine;
use function j45l\concurrentPhp\Coroutine\suspend;
use function j45l\concurrentPhp\Functions\exponentialAverage;
use function j45l\concurrentPhp\Scheduler\Scheduler;
use function PHPUnit\Framework\assertEquals;

final class SchedulerExamplesTest extends TestCase
{
    /** @throws Throwable */
    public function testCollectingFromCors(): void
    {
        $collector = $this->pingPongCollector(Channel());

        assertEquals(['ping', 'pong', 'ping', 'pong'], $collector->returnValue()->getOrElse(null));
    }

    /** @throws Throwable */
    public function testCollectingFromCorsWithMappedChannel(): void
    {
        $collector = $this->pingPongCollector(MappedChannel(Channel(), fn ($x) => sprintf("mapped %s", $x)));

        assertEquals(
            ['mapped ping', 'mapped pong', 'mapped ping', 'mapped pong'],
            $collector->returnValue()->getOrElse(null)
        );
    }

    public function testCollectingFromCorsWithSelectChannel(): void
    {
        $collector = $this->pingPongCollector(SelectChannel(Channel(), fn ($x) => $x === 'ping'));

        assertEquals(
            ['ping', 'ping'],
            $collector->returnValue()->getOrElse(null)
        );
    }

    public function testCollectingFromCorsWithRejectChannel(): void
    {
        $collector = $this->pingPongCollector(RejectChannel(Channel(), fn ($x) => $x === 'ping'));

        assertEquals(
            ['pong', 'pong'],
            $collector->returnValue()->getOrElse(null)
        );
    }

    /** @throws Throwable */
    public function testCollectingFromCorsOnce(): void
    {
        $channel = Channel();
        $ticker = TestTicker::create();
        $scheduler = Scheduler();

        $ping = $this->sender($channel, 'ping', 2, 0, $ticker);
        $pong = $this->sender($channel, 'pong', 2, 0, $ticker);
        $collector = $this->onceCollector($channel);

        $channel->setCloseOn(fn () => $ping->isTerminated() && $pong->isTerminated());

        $scheduler->schedule($ping, $pong, $collector)->run();

        assertEquals(['ping', 'pong'], $collector->returnValue()->getOrElse(null));
    }

    /**
     * @throws Throwable
     */
    public function testGettingLoadAverage(): void
    {
        $channel = Channel();
        $ticker = TestTicker::create();
        $scheduler = Scheduler($ticker, 100, 0.5);

        $ping = $this->sender($channel, 'ping', 10, 10, $ticker);
        $pong = $this->sender($channel, 'pong', 10, 20, $ticker);
        $channel->setCloseOn(fn () => $ping->isTerminated() && $pong->isTerminated());
        $loadMonitor = $this->loadMonitor($scheduler, $channel);

        $scheduler->schedule(
            $ping,
            $pong,
            $loadMonitor
        )->run();

        assertEquals(
            map(range(0, 9), fn ($rounds) => $this->loadAverage($rounds + 1, 0.3)),
            $loadMonitor->returnValue()->getOrElse(null)
        );
    }

    /**
     * @param Channel<string> $channel
     * @return Coroutine<string>
     */
    private function sender(Channel $channel, string $message, int $count, float $time, Ticker $ticker): Coroutine
    {
        return Coroutine(static function () use ($channel, $count, $message, $time, $ticker) {
            repeat(function () use ($channel, $message, $time, $ticker) {
                $channel->put($message);
                $ticker->sleep($time);
                suspend();
            })($count);
        });
    }

    /**
     * @param Channel<string> $channel
     * @return Coroutine<array<string>>
     */
    private function collector(Channel $channel): Coroutine
    {
        return Coroutine(static function () use ($channel) {
            return reduceChannel($channel, fn($initial, $element) => [...$initial, $element], []);
        });
    }

    /**
     * @param Channel<string> $channel
     * @return Coroutine<array<string>>
     */
    private function onceCollector(Channel $channel): Coroutine
    {
        return Coroutine(static function () use ($channel) {
            return reduceChannel($channel, fn($initial, $element) => [...$initial, $element], [], untilClosed: false);
        });
    }

    /**
     * @param Channel<string> $channel
     * @return Coroutine<array<float>>
     */
    private function loadMonitor(Scheduler $scheduler, Channel $channel): Coroutine
    {
        return Coroutine(static function () use ($scheduler, $channel) {
            $loads = [];
            while (!$channel->closed()) {
                Coroutine::suspend();
                $loads[] = $scheduler->loadAverage();
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

    /**
     * @param Channel<string> $channel
     * @return Coroutine<mixed>
     */
    public function pingPongCollector(Channel $channel): Coroutine
    {
        $ticker = TestTicker::create();
        $scheduler = Scheduler();

        $ping = $this->sender($channel, 'ping', 2, 0, $ticker);
        $pong = $this->sender($channel, 'pong', 2, 0, $ticker);
        $collector = $this->collector($channel);

        $channel->setCloseOn(fn() => $ping->isTerminated() && $pong->isTerminated());

        $scheduler->schedule($ping, $pong, $collector)->run();

        return $collector;
    }
}
