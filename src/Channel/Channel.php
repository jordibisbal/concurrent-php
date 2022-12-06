<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Channel;

use Closure;
use Fiber;
use j45l\concurrentPhp\Channel\Exceptions\UnableToGetFromChannel;
use j45l\concurrentPhp\Coroutine\Coroutine;
use Throwable;

use function array_pop as arrayPop;
use function array_unshift as arrayUnshift;
use function Functional\compose;
use function j45l\functional\doWhile;
use function PHPUnit\Framework\throwException;

/** @template T */
final class Channel
{
    /** @var array<T> */
    private array $buffer;

    private bool $closed;

    private Closure $closeOn;

    private int $capacity;

    private function __construct(int $capacity)
    {
        $this->capacity = $capacity;
        $this->buffer = [];
        $this->closed = false;
        $this->closeOn = static fn () => false;
    }

    /** @return Channel<T> */
    public static function create(int $capacity = null): Channel
    {
        return new self($capacity ?? 100);
    }

    public function close(): void
    {
        $this->closed = true;
    }

    /** @return self<T> */
    public function closeOn(Closure $predicate): self
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

    public function count(): int
    {
        return count($this->buffer);
    }

    public function empty(): bool
    {
        return empty($this->buffer);
    }

    public function full(): bool
    {
        return $this->count() >= $this->capacity;
    }

    /**
     * @throws Throwable
     * @return T
     */
    public function get(): mixed
    {
        $this->guardFiber();

        return match (true) {
            $this->empty() && $this->closed() => throwException(UnableToGetFromChannel::becauseIsClosed()),
            default =>  Coroutine::waitFor(fn () => !$this->empty(), fn () => arrayPop($this->buffer))
        };
    }

    /**
     * @param T $data
     * @return $this
     * @throws Throwable
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
}
