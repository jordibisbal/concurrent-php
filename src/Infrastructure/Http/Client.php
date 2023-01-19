<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Infrastructure\Http;

use GuzzleHttp\Psr7\Response;
use j45l\functional\Cats\Either\Either;

interface Client
{
    /**
     * @return Either<Response>
     */
    public function get(string $uri): Either;
}
