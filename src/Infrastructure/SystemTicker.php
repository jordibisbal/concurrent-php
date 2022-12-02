<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Infrastructure;

final class SystemTicker implements Ticker
{
    private function __construct()
    {
    }

    public static function create(): self
    {
        return new self();
    }

    public function time(): float
    {
        return microtime(true);
    }

    public function sleep(float $seconds): void
    {
        usleep((int) ($seconds * 1_000_000));
    }
}
