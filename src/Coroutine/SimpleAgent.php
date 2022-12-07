<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Coroutine;

use Closure;
use j45l\functional\Cats\Maybe\Maybe;

final class SimpleAgent extends Agent
{
    private Closure $fn;

    public function __construct(Closure $fn, string $name = null)
    {
        $this->fn = $fn;
        parent::__construct($name);
    }

    /**
     * @param Closure():Maybe<mixed> $fn
     */
    public static function create(Closure $fn, string $name = null): self
    {
        return new self($fn, $name ?? sprintf('unnamed %s', __CLASS__));
    }

    protected function invoke(): mixed
    {
        return ($this->fn)();
    }
}
