<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Channel;

use Closure;
use Fiber;
use j45l\concurrentPhp\Channel\Exceptions\UnableToGetFromChannel;
use j45l\concurrentPhp\Coroutine\Coroutine;

use function array_pop as arrayPop;
use function array_unshift as arrayUnshift;
use function Functional\compose;
use function j45l\concurrentPhp\Functions\also;
use function j45l\functional\doWhile;

/**
 * @template T
 * @implements Channel<T>
 */
class PlainChannel implements Channel
{
    /** @var array<T> */
    private array $buffer;

    private bool $closed;

    private Closure $closeOn;

    private int $capacity;
    private string $name;

    public function __construct(int $capacity = null, string $name = null)
    {
        $this->capacity = $capacity ?? 100;
        $this->buffer = [];
        $this->closed = false;
        $this->closeOn = static fn () => false;
        $this->name = $name ?? 'unnamed';
    }

    public function close(): void
    {
        $this->closed = true;
    }

    /** @return self<T> */
    public function setCloseOn(Closure $predicate): self
    {
        $this->closeOn = $predicate;

        return $this;
    }

    public function closed(): bool
    {
        return match (true) {
            !$this->closed && ($this->closeOn)() => compose(fn () => $this->close(), fn () => true)(null),
            default => $this->closed
        };
    }
    public function opened(): bool
    {
        return !$this->closed();
    }

    public function count(): int
    {
        return count($this->buffer);
    }

    public function empty(): bool
    {
        return empty($this->buffer);
    }

    public function hasSome(): bool
    {
        return !empty($this->buffer);
    }

    public function full(): bool
    {
        return $this->count() >= $this->capacity;
    }

    /**
     * @return T
     */
    public function get(): mixed
    {
        $this->guardFiber();

        return Coroutine::waitFor(fn () => $this->hasSome(), fn () => arrayPop($this->buffer));
    }

    /**
     * @param T $data
     * @return $this
     */
    public function put(mixed $data): self
    {
        doWhile(
            $this->full(...),
            fn() => Coroutine::suspend(),
            fn() => arrayUnshift($this->buffer, $data)
        );

        return $this;
    }

    private function guardFiber(): void
    {
        match (true) {
            Fiber::getCurrent() === null => throw UnableToGetFromChannel::becauseNotInAFiber(),
            default => null
        };
    }

    public function capacity(): int
    {
        return $this->capacity;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function getOnSome(Closure $getter): bool
    {
        /** @noinspection SuspiciousBinaryOperationInspection */
        /** @noinspection PhpBooleanCanBeSimplifiedInspection */
        return match (true) {
            $this->hasSome() => $getter($this->get()) || true,
            default => Coroutine::suspend() || false
        };
    }
}
