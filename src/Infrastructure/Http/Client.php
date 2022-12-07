<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Infrastructure\Http;

use GuzzleHttp\Psr7\Response;
use j45l\functional\Cats\Either\Either;

interface Client
{
    /**
     * @param array<mixed> $options
     * @return Either<Response>
     */
    public function get(string $uri, array $options = null): Either;
}
