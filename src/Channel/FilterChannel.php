<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Channel;

use Closure;

/**
 * @template T
 * @implements Channel<T>
 */
final class FilterChannel implements Channel
{
    /** @use ComposedChannel<T> */
    use ComposedChannel;

    private Closure $predicate;

    /**
     * @param Channel<T> $channel
     * @param Closure $predicate
     */
    private function __construct(Channel $channel, Closure $predicate)
    {
        $this->predicate = $predicate;
        $this->channel = $channel;
    }

    /**
     * @param Channel<T> $channel
     * @return self<T>
     */
    public static function create(Channel $channel, Closure $predicate): FilterChannel
    {
        return new self($channel, $predicate);
    }

    public function put(mixed $data): Channel
    {
        return match (true) {
            ($this->predicate)($data) => $this->channel->put($data),
            default => $this
        };
    }
}