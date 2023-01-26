<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Channel;

use Closure;

/** @template T */
interface Channel
{
    public function close(): void;

    /** @return self<T> */
    public function setCloseOn(Closure $predicate): Channel;

    public function closed(): bool;

    public function opened(): bool;

    public function count(): int;

    public function empty(): bool;

    public function hasSome(): bool;

    public function full(): bool;

    /**
     * @return T
     */
    public function get(): mixed;

    /**
     * @param T $data
     * @return Channel<T>
     */
    public function put(mixed $data): Channel;

    public function capacity(): int;

    public function name(): string;
}