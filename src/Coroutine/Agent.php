<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Coroutine;

abstract class Agent extends Coroutine
{
    public function __construct(string $name = null)
    {
        parent::__construct($this->invoke(...), $name ?? sprintf('unnamed %s', __CLASS__));
    }

    abstract protected function invoke(): mixed;
}
