<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Coroutine;

use Closure;
use Exception;
use Fiber;
use j45l\functional\Cats\Maybe\Maybe;
use RuntimeException;
use Throwable;

use function is_null as isNull;
use function j45l\functional\Cats\Either\BecauseException;
use function j45l\functional\Cats\Either\Failure;
use function j45l\functional\Cats\Maybe\None;
use function j45l\functional\Cats\Maybe\Some;
use function j45l\functional\first;
use function j45l\functional\nop;

/**
 * @template T
 */
class Coroutine
{
    private static int $nextId = 1;

    /** @var array<self<mixed>> */
    private static array $coroutinesRegistry = [];

    public readonly int $id;

    /** @var Fiber<mixed, mixed, mixed, mixed> */
    private Fiber $fiber;

    /** @var Closure */
    protected Closure $onThrowable;

    readonly public string $name;

    public function __construct(Closure $fn, string $name = null)
    {
        $this->id = self::$nextId++;
        $this->name = $name ?? 'unnamed';
        $this->onThrowable = nop(...);

        $this->fiber = new Fiber(function () use ($fn) {
            try {
                return Some($fn());
            } catch (Exception $exception) {
                return Failure(BecauseException($exception));
            }
        });

        self::$coroutinesRegistry[$this->id] = $this;
    }

    public function __destruct()
    {
        unset(self::$coroutinesRegistry[$this->id]);
    }

    /**
     * @param Fiber<mixed, mixed, mixed, mixed> $fiber
     * @return self<mixed>|null
     */
    public static function byFiber(Fiber $fiber): self|null
    {
        return first(self::$coroutinesRegistry, fn (Coroutine $element) => $element->fiber === $fiber);
    }

    /** @return $this */
    public function setOnThrowable(Closure $onThrowable = null): self
    {
        $this->onThrowable = $onThrowable ?? nop(...);

        return $this;
    }

    public static function in(): bool
    {
        return !isNull(Fiber::getCurrent());
    }

    /**
     * @param mixed $value
     */
    public static function suspend(mixed $value = null): void
    {
        try {
            Fiber::suspend($value);
        } catch (Throwable $throwable) {
            throw new RuntimeException($throwable->getMessage(), 0, $throwable);
        }
    }

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
     * @return self<T>
     */
    public function start(...$args): self
    {
        try {
            $this->fiber->start(...$args);
        } catch (Throwable $throwable) {
            ($this->onThrowable)($throwable);
        }

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
     */
    public function resume(): mixed
    {
        try {
            return $this->fiber->resume();
        } catch (Throwable $throwable) {
            throw new RuntimeException($throwable->getMessage(), 0, $throwable);
        }
    }

    /**
     * @phpstan-return Maybe<T>
     */
    public function returnValue(): Maybe
    {
        return match (true) {
            $this->isTerminated() => $this->fiber->getReturn(),
            default => None()
        };
    }

    public function isStarted(): bool
    {
        return $this->fiber->isStarted();
    }
}
