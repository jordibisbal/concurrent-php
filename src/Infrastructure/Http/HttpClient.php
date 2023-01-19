<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Infrastructure\Http;

use GuzzleHttp\Psr7\Response;
use j45l\concurrentPhp\Coroutine\Coroutine;
use j45l\functional\Cats\Either\Either;

use function Functional\compose;
use function Functional\map;
use function Functional\reindex;
use function j45l\functional\Cats\Either\Because;
use function j45l\functional\Cats\Either\Failure;
use function j45l\functional\Cats\Either\Success;
use function j45l\functional\first;
use function j45l\functional\select;
use function j45l\functional\tail;
use function j45l\functional\with;

class HttpClient implements Client
{
    public static function create(): HttpClient
    {
        return new self();
    }

    /**
     * @return Either<Response>
     */
    public function get(string $uri): Either
    {
        return match (true) {
            Coroutine::in() => $this->getForCoroutine($uri),
            default => Failure(Because('No in a fiber'))
        };
    }

    /**
     * @return Either<Response>
     */
    private function getForCoroutine(string $uri): Either
    {
        $ch1 = curl_init();

        curl_setopt($ch1, CURLOPT_URL, $uri);
        curl_setopt($ch1, CURLOPT_HEADER, 1);
        curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);

        $mh = curl_multi_init();

        curl_multi_add_handle($mh, $ch1);

        do {
            $status = curl_multi_exec($mh, $active);
            if ($active) {
                Coroutine::suspend();
            }
        } while ($active && $status === CURLM_OK);

        if ($status !== CURLM_OK) {
            curl_multi_remove_handle($mh, $ch1);
            curl_multi_close($mh);

            return Failure(Because('Curl failed'));
        }

        $result = curl_multi_getcontent($ch1) ?? '';
        $statusCode = curl_getinfo($ch1, CURLINFO_HTTP_CODE);
        [$headers, $result] = $this->parseResult($result, curl_getinfo($ch1, CURLINFO_HEADER_SIZE));

        curl_multi_remove_handle($mh, $ch1);
        curl_multi_close($mh);

        return Success(new Response($statusCode, $headers, $result));
    }

    /**
     * @return array{0: array<string>, 1:string}
     */
    public function parseResult(string $result, mixed $headersSize): array
    {
        return [
            with(substr($result, 0, $headersSize))(compose(
                fn(string $rawHeaders) => explode("\n", $rawHeaders),
                fn(array $wholeHeaders) => map(
                    $wholeHeaders,
                    fn(string $wholeHeader) => explode(':', trim($wholeHeader))
                ),
                fn(array $headers) => select($headers, fn(array $parts) => count($parts) > 1),
                fn(array $headers) => reindex($headers, fn(array $parts) => first($parts)),
                fn(array $headers) => map($headers, fn(array $parts) => tail($parts)),
                fn(array $headers) => map($headers, fn(array $parts) => implode(':', $parts)),
                fn(array $headers) => map($headers, fn(string $header) => trim($header)),
            )),
            substr($result, $headersSize)
        ];
    }
}
