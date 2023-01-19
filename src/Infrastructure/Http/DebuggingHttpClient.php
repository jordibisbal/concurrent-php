<?php
declare(strict_types=1);

namespace j45l\concurrentPhp\Infrastructure\Http;

use j45l\concurrentPhp\Channel\Channel;
use j45l\functional\Cats\Either\Either;
use j45l\functional\Cats\Maybe\Maybe;

use function Functional\compose;
use function j45l\concurrentPhp\Functions\also;

final class DebuggingHttpClient implements Client
{
    private Client $client;
    /** @var Channel<string>  */
    private Channel $debuggingChannel;

    /** @param Channel<string> $debuggingChannel */
    private function __construct(Client $client, Channel $debuggingChannel)
    {
        $this->client = $client;
        $this->debuggingChannel = $debuggingChannel;
    }

    /** @param Channel<string> $debuggingChannel */
    public static function create(Client $client, Channel $debuggingChannel): DebuggingHttpClient
    {
        return new self($client, $debuggingChannel);
    }

    public function get(string $uri, array $options = null): Either
    {
        return compose(
            fn () => $this->debuggingChannel->put(
                sprintf('    🌐🏳️ Http client - starting request %s', $uri)
            ),
            fn () => $this->client->get($uri, $options),
            fn (Maybe $result): Maybe => also(fn () => $this->debuggingChannel->put(
                sprintf('    🌐🏁 Http client - Request %s finished', $uri)
            ))($result)
        )(null);
    }
}