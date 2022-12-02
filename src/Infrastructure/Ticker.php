<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Infrastructure;

interface Ticker
{
    public function time(): float;
    public function sleep(float $seconds): void;
}
