<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Channel;

use Closure;

/** @return PlainChannel<mixed> */
function Channel(int $capacity = null, string $name = null): PlainChannel
{
    return new PlainChannel($capacity, $name);
}

/**
 * @template T
 * @template T2
 * @param Channel<T2> $channel
 * @param Closure(T):T2 $mapper
 * @return MappedChannel<T,T2>
 */
function MappedChannel(Channel $channel, Closure $mapper): MappedChannel
{
    return MappedChannel::create($channel, $mapper);
}

/** @return BlackHole<mixed> */
function BlackHole(int $capacity = null, string $name = null): BlackHole
{
    return new BlackHole($capacity, $name);
}

/**
 * @template T
 * @param Channel<T> $channel
 * @param Closure(T):bool $predicate
 * @return FilterChannel<T>
 */
function SelectChannel(Channel $channel, Closure $predicate): FilterChannel
{
    return FilterChannel::create($channel, $predicate);
}

/**
 * @template T
 * @param Channel<T> $channel
 * @param Closure(T):bool $predicate
 * @return FilterChannel<T>
 */
function RejectChannel(Channel $channel, Closure $predicate): FilterChannel
{
    return FilterChannel::create($channel, fn ($x) => !$predicate($x));
}