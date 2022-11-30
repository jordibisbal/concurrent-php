<?php

declare(strict_types=1);

namespace j45l\channels\Poo;

use Throwable;

use function Functional\each;
use function Functional\select;
use function Functional\some;

final class PooLoop
{
    /** @var array<Poo<mixed>> */
    private array $poos;

    /**
     * @param array<Poo<mixed>> $poos
     */
    private function __construct(array $poos)
    {
        $this->poos = $poos;
    }

    /**
     * @param array<Poo<mixed>> $poos
     * @return PooLoop
     */
    public static function create(array $poos): PooLoop
    {
        return new self($poos);
    }

    /** @throws Throwable */
    public function run(): void
    {
        each($this->poos, fn (Poo $poo) => $poo->start());

        while (some($this->poos, fn (Poo $poo) => !$poo->isTerminated())) {
            each(
                select(
                    $this->poos,
                    fn(Poo $poo) => $poo->isSuspended()
                ),
                fn(Poo $poo) => $poo->resume()
            );
        }
    }
}
