<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Coroutine;

abstract class Agent extends Coroutine
{
    public function __construct()
    {
        parent::__construct($this->invoke(...));
    }

    abstract protected function invoke(): mixed;
}
