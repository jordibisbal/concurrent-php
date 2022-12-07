<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Channel\Exceptions;

use RuntimeException;

final class UnableToGetFromChannel extends RuntimeException
{
    public static function becauseNotInAFiber(): RuntimeException
    {
        return new RuntimeException(
            'Unable to get from channel as Channel::get() has not been called from inside a Fiber.'
        );
    }

    public static function becauseIsClosed(): self
    {
        return new self('Unable to get from channel because is closed.');
    }
}
