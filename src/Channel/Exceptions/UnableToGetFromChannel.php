<?php

declare(strict_types=1);

namespace j45l\channels\Channel\Exceptions;

use RuntimeException;

final class UnableToGetFromChannel extends RuntimeException
{
    public static function becauseNotInAFiber(): self
    {
        return new self(
            'Unable to get from channel as Channel::get() has not been called from inside a Fiber.'
        );
    }

    public static function becauseIsClosed(): self
    {
        return new self('Unable to get from channel because is closed.');
    }
}
