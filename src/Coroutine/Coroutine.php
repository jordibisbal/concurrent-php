<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Coroutine;

use Closure;
use Exception;
use Fiber;
use j45l\functional\Maybe\Maybe;
use Throwable;

use function j45l\functional\Either\BecauseException;
use function j45l\functional\Either\Failure;
use function j45l\functional\Maybe\None;
use function j45l\functional\Maybe\Some;

/**
 * @template TReturn
 */
abstract class Coroutine
{
    /** @var Fiber<mixed, mixed, TReturn, mixed> */
    private Fiber $fiber;

    final private function __construct(callable $fn)
    {
        $this->fiber = new Fiber(function () use ($fn) {
            try {
                return Some($fn());
            } catch (Exception $exception) {
                return Failure(BecauseException($exception));
            }
        });
    }

    /**
     * @param callable $fn
     * @return static
     */
    public static function create(callable $fn): self
    {
        return new static($fn);
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
     * @phpstan-return Maybe<TReturn>
     * @phpstan-ignore-next-line
     */
    public function returnValue(): Maybe
    {
        /** @phpstan-ignore-next-line  */
        return match (true) {
            $this->isTerminated() => Some($this->fiber->getReturn()),
            default => None()
        };
    }

    public function isStarted(): bool
    {
        return $this->fiber->isStarted();
    }
}
