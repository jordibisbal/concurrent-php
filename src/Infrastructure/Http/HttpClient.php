<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Infrastructure\Http;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Promise\Is;
use GuzzleHttp\Promise\Utils;
use j45l\concurrentPhp\Coroutine\Coroutine;
use j45l\functional\Cats\Either\Either;
use j45l\functional\Cats\Either\Reason\Reason;
use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\ResponseInterface;

use function j45l\functional\Cats\Either\Because;
use function j45l\functional\Cats\Either\Failure;
use function j45l\functional\Cats\Either\Success;
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
    public static function create(array $config = null): HttpClient
    {
        return new self($config ?? []);
    }

    /**
     * @param array<mixed> $options
     * @return Either<Reason,ResponseInterface>
     */
    public function get(string $uri, array $options = null): Either
    {
        return match (true) {
            Coroutine::in() => $this->getForCoroutine($uri, $options ?? []),
            default => Failure(Because('No in a fiber'))
        };
    }

    /**
     * @param array<mixed> $options
     * @return Either<Reason,ResponseInterface>
     */
    private function getForCoroutine(string $uri, array $options): Either
    {
        $response = null;

        $promise = $this->client->getAsync($uri, $options)
            ->then(
                function (ResponseInterface $res) use (&$response) {
                    $response = Some(Success($res));
                },
                function (RequestExceptionInterface $res) use (&$response) {
                    $response = Some(Failure(Because($res->getMessage())->on($res)));
                }
            );

        return doWhile(
            function () use ($promise) {
                Utils::queue()->run();

                return !Is::settled($promise);
            },
            fn () => Coroutine::suspend(),
            function () use (&$response) {
                return $response->getOrFail();
            }
        );
    }
}
