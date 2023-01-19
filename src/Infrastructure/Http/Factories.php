<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Infrastructure\Http;

use j45l\concurrentPhp\Channel\Channel;
use j45l\concurrentPhp\Infrastructure\Http\Debug\DebuggingHttpClient;
use j45l\concurrentPhp\Infrastructure\Http\HttpClient;

use function is_null as isNull;

/**
 * @param Channel<string>|null $debuggingChannel
 */
function HttpClient(Channel $debuggingChannel = null): HttpClient
{
    return match (true) {
        !isNull($debuggingChannel) => new DebuggingHttpClient($debuggingChannel),
        default => new HttpClient(),
    };
}