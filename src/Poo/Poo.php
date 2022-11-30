<?php

declare(strict_types=1);

namespace j45l\channels\Poo;

use Closure;
use Fiber;
use Throwable;

/**
 * @template TReturn
 */
final class Poo
{
    /** @var Fiber<mixed, mixed, TReturn, mixed> */
    private Fiber $fiber;

    private function __construct(callable $fn)
    {
        $this->fiber = new Fiber($fn);
    }

    /**
     * @param callable $fn
     * @return self<TReturn>
     */
    public static function create(callable $fn): self
    {
        return new self($fn);
    }

    /**
     * @param mixed $value
     * @throws Throwable
     */
    public static function suspend(mixed $value = null): void
    {
        Fiber::suspend($value);
    }

    /**
     * @throws Throwable
     */
    public static function waitFor(Closure $predicate, Closure $do = null): mixed
    {
        $do ??= static fn () => null;
        while (!$predicate()) {
            self::suspend();
        }

        return $do();
    }

    /**
     * @param array<mixed> $args
     * @throws Throwable
     * @return self<TReturn>
     */
    public function start(...$args): self
    {
        $this->fiber->start(...$args);

        return $this;
    }

    public function isTerminated(): bool
    {
        return $this->fiber->isTerminated();
    }

    public function isSuspended(): bool
    {
        return $this->fiber->isSuspended();
    }

    /**
     * @return mixed
     * @throws Throwable
     */
    public function resume(): mixed
    {
        return $this->fiber->resume();
    }

    /**
     * @return TReturn
     */
    public function returnValue(): mixed
    {
        return match (true) {
            $this->isTerminated() => $this->fiber->getReturn(),
            default => null
        };
    }

    public function isStarted(): bool
    {
        return $this->fiber->isStarted();
    }
}
