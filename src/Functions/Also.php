<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Functions;

use Closure;

/**
 * @param Closure(mixed):mixed $fn
 * @return Closure(mixed):mixed
 */
function also(Closure $fn): Closure
{
    return static function ($value) use ($fn) {
        $fn($value);
        return $value;
    };
}
