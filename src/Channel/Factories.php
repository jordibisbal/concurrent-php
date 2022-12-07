<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Channel;

/** @return Channel<mixed> */
function Channel(int $capacity = null, string $name = null): Channel
{
    return new Channel($capacity, $name);
}

/** @return Channel<mixed> */
function BlackHole(int $capacity = null, string $name = null): Channel
{
    return new BlackHole($capacity, $name);
}
