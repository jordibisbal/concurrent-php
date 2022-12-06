<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Coroutine;

use Closure;

final class Task
{
    /**
     * @param Closure(): Agent $factory
     */
    private function __construct(readonly public Closure $factory, readonly int $copies)
    {
    }

    /**
     * @param Closure(): Agent $factory
     */
    public static function create(Closure $factory, int $copies): Task
    {
        return new self($factory, $copies);
    }
}
