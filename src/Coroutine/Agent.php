<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Coroutine;

use function j45l\functional\nop;

abstract class Agent extends Coroutine
{
    public function __construct(callable $onException = null)
    {
        parent::__construct($this->invoke(...), $onException ?? nop());
    }

    abstract protected function invoke(): mixed;
}
