<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Channel;

use Closure;
use Fiber;
use j45l\concurrentPhp\Channel\Exceptions\UnableToGetFromChannel;
use j45l\concurrentPhp\Channel\Exceptions\UnableToOperateOnChannel;
use j45l\concurrentPhp\Coroutine\Coroutine;
use Throwable;

use function array_unshift as arrayUnshift;
use function Functional\compose;
use function j45l\concurrentPhp\Functions\also;
use function PHPUnit\Framework\throwException;

/** @template T */
final class Channel
{
    /** @var array<T> */
    private array $buffer;

    private bool $closed;

    private Closure $closeOn;

    private function __construct()
    {
        $this->buffer = [];
        $this->closed = false;
        $this->closeOn = static fn () => false;
    }

    /** @return Channel<T> */
    public static function create(): Channel
    {
        return new self();
    }

    /** @return array<T> */
    public function peek(int $count = null): array
    {
        return match (true) {
            $this->closed => [],
            default => compose(
                fn (?int $count) => $count ?? 1,
                fn (int $count) => $this->guardCount($count),
                fn (int $count) => array_slice($this->buffer, -$count, $count)
            )($count)
        };
    }

    public function empty(): bool
    {
        return empty($this->buffer);
    }

    public function count(): int
    {
        return count($this->buffer);
    }

    /**
     * @throws Throwable
     */
    public function get(int $count = 1): mixed
    {
        $this->guardFiber();
        $this->guardCount($count);

        $hasCount = fn() => $this->count() >= $count;

        return match (true) {
            !$hasCount() && $this->closed() => throwException(UnableToGetFromChannel::becauseIsClosed()),
            $count === 1 =>  Coroutine::waitFor(fn () => $this->count() >= 1, fn () => $this->getCount(1)[0]),
            default => Coroutine::waitFor($hasCount, fn () => $this->getCount($count))
        };
    }

    /**
     * @template T2
     * @param int $count
     * @param Closure(T|array<T>):T2 $fnOnHasData
     * @param Closure():T2 $fnOnHasNoData
     * @return T2
     */
    public function getIf(int $count, Closure $fnOnHasData, Closure $fnOnHasNoData): mixed
    {
        return match (true) {
            $this->count() < $count => $fnOnHasNoData(),
            $count === 1 => $fnOnHasData($this->getCount(1)[0]),
            default => $fnOnHasData($this->getCount($count))
        };
    }

    /**
     * @template TAccumulator
     * @param Closure(TAccumulator, T|array<T>):TAccumulator $fnAccumulator
     * @param TAccumulator $initial
     * @return TAccumulator
     * @throws Throwable
     */
    public function reduce(
        Closure $fnAccumulator,
        mixed $initial,
        int $count = null,
        bool $untilClosed = true
    ): mixed {
        $count ??= 1;
        $terminate = false;
        $accumulator = $initial;

        while (!$terminate) {
            $this->getIf(
                $count,
                function ($element) use (&$accumulator, $fnAccumulator) {
                    $accumulator = $fnAccumulator($accumulator, $element);
                },
                function () use (&$terminate, $untilClosed) {
                    $terminate = match (Fiber::getCurrent()) {
                        null => true,
                        default => also(fn () => Coroutine::suspend())($this->closed() || !$untilClosed)
                    };
                }
            );
        }

        return $accumulator;
    }

    /**
     * @param T $data
     * @return $this
     */
    public function put(mixed $data): self
    {
        arrayUnshift($this->buffer, $data);

        return $this;
    }

    private function guardFiber(): void
    {
        match (true) {
            Fiber::getCurrent() === null => throw UnableToGetFromChannel::becauseNotInAFiber(),
            default => null
        };
    }

    private function guardCount(int $count): void
    {
        match (true) {
            $count < 1 => throw UnableToOperateOnChannel::becauseCountIsNotStrictlyPositive($count),
            default => null
        };
    }

    public function close(): void
    {
        $this->closed = true;
    }

    public function closed(): bool
    {
        return match (true) {
            !$this->closed && ($this->closeOn)() => compose(fn () => $this->close(), fn () => true)(null),
            default => $this->closed
        };
    }

    /**
     * @return array<T>
     */
    private function getCount(int $count): array
    {
        $return = array_slice($this->buffer, -$count, $count);
        $this->buffer = array_slice($this->buffer, 0, -$count);

        return $return;
    }

    /** @return self<T> */
    public function closeOn(Closure $predicate): self
    {
        $this->closeOn = $predicate;

        return $this;
    }
}
