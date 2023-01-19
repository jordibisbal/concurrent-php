<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Infrastructure\Http\Debug;

use j45l\concurrentPhp\Channel\Channel;
use j45l\concurrentPhp\Infrastructure\Http\HttpClient;
use j45l\functional\Cats\Either\Either;

final class DebuggingHttpClient extends HttpClient
{
    /** @param Channel<string> $debuggingChannel */
    public function __construct(private readonly Channel $debuggingChannel)
    {
    }

    public function get(string $uri): Either
    {
        $this->debuggingChannel->put(sprintf('  🌐🏳️ Downloading %s.', $uri));

        $result = parent::get($uri);

        $this->debuggingChannel->put(sprintf('  🌐🏁 Finished downloading %s.', $uri));

        return $result;
    }
}
