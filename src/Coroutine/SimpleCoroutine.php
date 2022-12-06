<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Coroutine;

use Closure;
use j45l\functional\Cats\Maybe\Maybe;

/**
 * @template TReturn
 * @extends Coroutine<TReturn>
 */
class SimpleCoroutine extends Coroutine
{
    /**
     * @param Closure():Maybe<TReturn> $fn
     * @return self<TReturn>
     */
    public static function create(Closure $fn): self
    {
        return new self($fn);
    }
}
