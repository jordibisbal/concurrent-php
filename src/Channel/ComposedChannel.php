<?php
declare(strict_types=1);

namespace j45l\concurrentPhp\Channel;

use Closure;

/**
 * @template T
 */
trait ComposedChannel
{

    /** @var Channel<T>  */
    protected Channel $channel;

    public function put(mixed $data): Channel
    {
        $this->channel->put(($data));

        return $this;
    }

    public function close(): void
    {
        $this->channel->close();
    }

    public function setCloseOn(Closure $predicate): Channel
    {
        return $this->channel->setCloseOn($predicate);
    }

    public function closed(): bool
    {
        return $this->channel->closed();
    }

    public function opened(): bool
    {
        return $this->channel->opened();
    }

    public function count(): int
    {
        return $this->channel->count();
    }

    public function empty(): bool
    {
        return $this->channel->empty();
    }

    public function hasSome(): bool
    {
        return $this->channel->hasSome();
    }

    public function full(): bool
    {
        return $this->channel->full();
    }

    public function get(): mixed
    {
        return $this->channel->get();
    }

    public function capacity(): int
    {
        return $this->channel->capacity();
    }

    public function name(): string
    {
        return $this->channel->name();
    }
}