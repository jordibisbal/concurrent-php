<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Test\Unit\Coroutine\Stubs;

final class CallableTestObject
{
    private function __construct(readonly private mixed $value)
    {
    }

    public static function create(mixed $value): CallableTestObject
    {
        return new self($value);
    }

    public function __invoke(): mixed
    {
        return $this->value;
    }
}
