<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Channel;

use Closure;

/**
 * @template T
 * @template T2
 * @implements Channel<T2>
 */
final class MappedChannel implements Channel
{
    /** @use ComposedChannel<T2> */
    use ComposedChannel;

    private Closure $mapper;

    /**
     * @param Channel<T2> $channel
     * @param Closure $mapper
     */
    private function __construct(Channel $channel, Closure $mapper)
    {
        $this->mapper = $mapper;
        $this->channel = $channel;
    }

    /**
     * @param Channel<T2> $channel
     * @return self<T,T2>
     */
    public static function create(Channel $channel, Closure $mapper): MappedChannel
    {
        return new self($channel, $mapper);
    }

    public function put(mixed $data): Channel
    {
        $this->channel->put(($this->mapper)($data));

        return $this;
    }

    public function getOnSome(Closure $getter): bool
    {
        return $this->channel->getOnSome($getter);
    }
}
