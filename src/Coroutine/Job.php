<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Coroutine;

use Closure;

final class Job
{
    private function __construct(readonly public Closure $factory, readonly int $copies)
    {
    }

    public static function create(Closure $factory, int $copies): Job
    {
        return new self($factory, $copies);
    }
}
