<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Coroutine\Exceptions;

use Exception;

final class Terminate extends Exception
{
    public static function signal(): Terminate
    {
        return new self();
    }
}
