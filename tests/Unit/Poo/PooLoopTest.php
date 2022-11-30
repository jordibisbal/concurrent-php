<?php

declare(strict_types=1);

namespace j45l\channels\Test\Unit\Poo;

use j45l\channels\Channel\Channel;
use j45l\channels\Poo\Poo;
use j45l\channels\Poo\PooLoop;
use PHPUnit\Framework\TestCase;
use Throwable;

use function Functional\each;
use function PHPUnit\Framework\assertEquals;

final class PooLoopTest extends TestCase
{
    /** @throws Throwable */
    public function test(): void
    {
        $channel = Channel::create();

        $ping = $this->ping($channel);
        $pong = $this->pong($channel);
        $collector = $this->collector($channel);

        $channel->closeOn(fn () => $ping->isTerminated() && $pong->isTerminated());

        $loop = PooLoop::create([$ping, $pong, $collector]);
        $loop->run();

        assertEquals(['ping', 'pong', 'ping', 'pong'], $collector->returnValue());
    }

    /**
     * @param Channel<string> $channel
     * @return Poo<string>
     */
    private function ping(Channel $channel): Poo
    {
        return Poo::create(static function () use ($channel) {
            each(range(1, 2), function () use ($channel) {
                $channel->put('ping');
                Poo::suspend();
            });
        });
    }

    /**
     * @param Channel<string> $channel
     * @return Poo<string>
     */
    private function pong(Channel $channel): Poo
    {
        return Poo::create(static function () use ($channel) {
            each(range(1, 2), function () use ($channel) {
                $channel->put('pong');
                Poo::suspend();
            });
        });
    }

    /**
     * @param Channel<string> $channel
     * @return Poo<array<string>>
     */
    private function collector(Channel $channel): Poo
    {
        return Poo::create(static function () use ($channel) {
            return $channel->reducePoo(fn($initial, $element) => [...$initial, $element], []);
        });
    }
}
