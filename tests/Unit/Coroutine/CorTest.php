<?php

declare(strict_types=1);

namespace j45l\concurrentPhp\Test\Unit\Coroutine;

use j45l\concurrentPhp\Coroutine\Coroutine;
use PHPUnit\Framework\TestCase;
use Throwable;

use function j45l\concurrentPhp\Coroutine\Cor;
use function j45l\concurrentPhp\Coroutine\suspend;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertTrue;

final class CorTest extends TestCase
{
    /** @throws Throwable */
    public function testACoroutineCanBeResumed(): void
    {
        $coroutine = Cor(static function () {
            suspend();
            return 42;
        })->start();

        assertTrue($coroutine->isStarted());
        assertTrue($coroutine->isSuspended());
        assertFalse($coroutine->isTerminated());
        assertNull($coroutine->returnValue());

        $coroutine->resume();

        assertTrue($coroutine->isStarted());
        assertFalse($coroutine->isSuspended());
        assertTrue($coroutine->isTerminated());
        assertEquals(42, $coroutine->returnValue());
    }

    /** @throws Throwable */
    public function testACoroutineCanBeStarted(): void
    {
        $cor42 = $this->Cor42();

        assertTrue($cor42->isStarted());
        assertFalse($cor42->isSuspended());
        assertTrue($cor42->isTerminated());
    }

    /**
     * @return Coroutine<int>
     * @throws Throwable
     */
    private function Cor42(): Coroutine // phpcs:ignore
    {
        return Cor(static function () {
            return 42;
        });
    }

    /** @throws Throwable */
    public function testACoroutineCanBeSuspended(): void
    {
        $coroutine = Cor(static function () {
            Coroutine::suspend();
            return 42;
        })->start();

        assertTrue($coroutine->isStarted());
        assertTrue($coroutine->isSuspended());
        assertFalse($coroutine->isTerminated());
    }

    /**
     * @throws Throwable
     */
    public function testACoroutineIsCreatedNotStated(): void
    {
        assertFalse(Cor(static fn () => null)->isStarted());
    }

    /** @throws Throwable */
    public function testACoroutineReturnValueCanBeObtained(): void
    {
        $coroutine = $this->Cor42();

        assertTrue($coroutine->isStarted());
        assertFalse($coroutine->isSuspended());
        assertTrue($coroutine->isTerminated());
        assertEquals(42, $coroutine->returnValue());
    }
}
