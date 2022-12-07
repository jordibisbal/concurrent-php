<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Channel;

use function Functional\each;
use function str_repeat as strRepeat;

final class Debug
{
    /**
     * @param array<Channel<mixed>> $channels
     * @param Channel<string> $debuggingChannel
     */
    public static function debugChannels(array $channels, Channel $debuggingChannel, string $indentation = null): void
    {
        $channelUsage = static fn(Channel $channel, $scale = 100): int
            => (int)round($channel->count() * $scale / $channel->capacity());
        each(
            $channels,
            fn(Channel $channel) => $debuggingChannel->put(sprintf(
                "%s  Channel [%s%s] %s, capacity: %s, usage: %s (%1.0f %%) %s",
                $indentation ?? '',
                strRepeat('*', $channelUsage($channel, 10)),
                strRepeat('.', 10 - $channelUsage($channel, 10)),
                $channel->name(),
                $channel->capacity(),
                $channel->count(),
                $channelUsage($channel),
                $channel->closed() ? 'closed' : 'opened'
            ))
        );
    }
}
