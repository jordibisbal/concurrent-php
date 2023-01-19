<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Infrastructure\Http;

use j45l\concurrentPhp\Channel\Channel;

use function is_null as isNull;

/** @param Channel<string>|null $debuggingChannel */
function HttpClient(Channel $debuggingChannel = null): Client
{
    /** @noinspection NullPointerExceptionInspection */
    return match (true) {
        isNull($debuggingChannel) => HttpClient::create(),
        default => DebuggingHttpClient::create(HttpClient::create(), $debuggingChannel)
    };
}
