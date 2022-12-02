<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Test\Unit\Coroutine\Stubs;

use j45l\concurrentPhp\Infrastructure\Ticker;

final class TestTicker implements Ticker
{
    private float $time;

    private function __construct()
    {
        $this->time = 0;
    }

    public static function create(): TestTicker
    {
        return new self();
    }

    public function time(): float
    {
        return $this->time;
    }

    public function sleep(float $seconds): void
    {
        $this->time += $seconds;
    }
}
