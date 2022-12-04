<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Infrastructure\Http;

use GuzzleHttp\Client as GuzzleClient;
use j45l\concurrentPhp\Coroutine\Coroutine;
use j45l\functional\Cats\Either\Either;
use j45l\functional\Cats\Either\Reason\Reason;
use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

use function j45l\functional\Cats\Either\Because;
use function j45l\functional\Cats\Either\Failure;
use function j45l\functional\Cats\Either\Success;
use function j45l\functional\Cats\Maybe\isNone;
use function j45l\functional\Cats\Maybe\None;
use function j45l\functional\Cats\Maybe\Some;
use function j45l\functional\doWhile;

final class HttpClient implements Client
{
    private GuzzleClient $client;

    /** @param array<mixed> $config */
    private function __construct(array $config)
    {
        $this->client = new GuzzleClient($config);
    }

    /** @param array<mixed> $config */
    public static function create(array $config): HttpClient
    {
        return new self($config);
    }

    /**
     * @param array<mixed> $options
     * @return Either<Reason,ResponseInterface>
     * @throws Throwable
     */
    public function get(string $uri, array $options): Either
    {
        return match (true) {
            Coroutine::in() => $this->getForCoroutine($uri, $options),
            default =>  $this->getForCoroutine($uri, $options),
        };
    }

    /**
     * @param array<mixed> $options
     * @return Either<Reason,ResponseInterface>
     * @throws Throwable
     */
    private function getForCoroutine(string $uri, array $options): Either
    {
        $promise = $this->client->getAsync($uri, $options);
        $response = None();

        $promise->then(
            function (ResponseInterface $res) use (&$response) {
                $response = Some(Success($res));
            },
            function (RequestExceptionInterface $res) use (&$response) {
                $response = Some(Failure(Because($res->getMessage())->on($res)));
            }
        );

        return doWhile(fn () => isNone($response), fn () => Coroutine::suspend(), fn () => $response->getOrFail());
    }
}
