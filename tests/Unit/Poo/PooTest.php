<?php

declare(strict_types=1);

namespace j45l\channels\Test\Unit\Poo;

use j45l\channels\Poo\Poo;
use PHPUnit\Framework\TestCase;
use Throwable;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertTrue;

final class PooTest extends TestCase
{
    public function testAPooIsCreatedNotStated(): void
    {
        assertFalse(Poo::create(static fn () => null)->isStarted());
    }

    /** @throws Throwable */
    public function testAPooCanBeStarted(): void
    {
        $poo = $this->Poo42();

        assertTrue($poo->isStarted());
        assertFalse($poo->isSuspended());
        assertTrue($poo->isTerminated());
    }

    /** @throws Throwable */
    public function testAPooReturnValueCanBeObtained(): void
    {
        $poo = $this->Poo42();

        assertTrue($poo->isStarted());
        assertFalse($poo->isSuspended());
        assertTrue($poo->isTerminated());
        assertEquals(42, $poo->returnValue());
    }


    /** @throws Throwable */
    public function testAPooCanBeSuspended(): void
    {
        $poo = Poo::create(static function () {
            Poo::suspend();
            return 42;
        })->start();

        assertTrue($poo->isStarted());
        assertTrue($poo->isSuspended());
        assertFalse($poo->isTerminated());
    }

    /** @throws Throwable */
    public function testAPooCanBeResumed(): void
    {
        $poo = Poo::create(static function () {
            Poo::suspend();
            return 42;
        })->start();

        assertTrue($poo->isStarted());
        assertTrue($poo->isSuspended());
        assertFalse($poo->isTerminated());
        assertNull($poo->returnValue());

        $poo->resume();

        assertTrue($poo->isStarted());
        assertFalse($poo->isSuspended());
        assertTrue($poo->isTerminated());
        assertEquals(42, $poo->returnValue());
    }

    /**
     * @return Poo<int>
     * @throws Throwable
     */
    private function Poo42(): Poo // phpcs:ignore
    {
        return Poo::create(static function () {
            return 42;
        })->start();
    }
}
