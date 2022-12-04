<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Coroutine;

use j45l\functional\Cats\Maybe\Maybe;

/**
 * @template TReturn
 * @extends Coroutine<TReturn>
 */
class Cor extends Coroutine
{
    /**
     * @param callable():Maybe<TReturn> $fn
     * @return $this
     */
    public static function create(callable $fn): self
    {
        return new self($fn);
    }
}
