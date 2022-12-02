<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Channel\Exceptions;

use RuntimeException;

final class UnableToOperateOnChannel extends RuntimeException
{
    public static function becauseCountIsNotStrictlyPositive(int $count): self
    {
        return new self(sprintf(
            'Unable to operate on channel because the count is not strictly positive (%s).',
            $count
        ));
    }
}
