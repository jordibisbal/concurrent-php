<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Channel;

use Throwable;

/**
 * @template T
 * @extends Channel<T>
 */
final class BlackHole extends Channel
{
    /**
     * @param T $data
     * @return $this
     * @throws Throwable
     */
    public function put(mixed $data): self
    {
        return $this;
    }
}
