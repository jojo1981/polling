<?php declare(strict_types=1);
/*
 * This file is part of the jojo1981/polling package
 *
 * Copyright (c) 2021 Joost Nijhuis <jnijhuis81@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed in the root of the source code
 */
namespace Jojo1981\Polling\TestSuite;

use Exception;
use Jojo1981\Contracts\Exception\ValueExceptionInterface;
use Jojo1981\Polling\Checker\PollExceptionChecker;
use Jojo1981\Polling\Checker\PollResultChecker;
use Jojo1981\Polling\Executor\PollExecutor;
use Jojo1981\Polling\Poller;
use Jojo1981\Polling\Value\PollCount;
use Jojo1981\TypedCollection\Collection;
use Jojo1981\TypedCollection\Exception\CollectionException;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\RecursionContext\InvalidArgumentException as SebastianBergmannInvalidArgumentException;
use Throwable;

/**
 * @package Jojo1981\Polling\TestSuite
 */
final class PollerTest extends TestCase
{
    /**
     * @return void
     * @throws ExpectationFailedException
     * @throws SebastianBergmannInvalidArgumentException
     * @throws Throwable
     * @throws ValueExceptionInterface
     * @throws CollectionException
     */
    public function testPollerWithFailedResult(): void
    {
        $executorCalled = 0;
        $pollExecutor = new PollExecutor(
            static function (string $pollArg1, int $arg1, array $arg2, Collection $previousResults, int $pollCount) use(&$executorCalled): string {
                $executorCalled++;
                self::assertEquals($executorCalled, $pollCount);
                self::assertEquals('pollArg1', $pollArg1);
                self::assertEquals(230, $arg1);
                self::assertEquals([1,2,3], $arg2);

                return 3 === $executorCalled ? 'OK' : 'NOK';
            },
            [230, [1, 2, 3]]
        );
        $resultCheckerCalled = 0;
        $pollResultChecker = new PollResultChecker(
            static function (string $pollArg1, string $result, Collection $previousResults, int $pollCount) use(&$resultCheckerCalled): bool {
                $resultCheckerCalled++;
                self::assertEquals($resultCheckerCalled, $pollCount);
                self::assertEquals('pollArg1', $pollArg1);

                return 'OK' === $result;
            }
        );
        $poller = new Poller($pollExecutor, $pollResultChecker, null, null, new PollCount(2));
        $pollResult = $poller->startPolling(['pollArg1']);
        self::assertEquals(2, $pollResult->getCount());
        self::assertFalse($pollResult->isSuccess());
        self::assertTrue($pollResult->isFailed());
        self::assertEquals('NOK', $pollResult->getResult());
    }

    /**
     * @return void
     * @throws CollectionException
     * @throws ExpectationFailedException
     * @throws SebastianBergmannInvalidArgumentException
     * @throws Throwable
     * @throws ValueExceptionInterface
     */
    public function testPollerWithSuccessResult(): void
    {
        $executorCalled = 0;
        $pollExecutor = new PollExecutor(
            static function (string $pollArg1, string $arg1, float $arg2, Collection $previousResults, int $pollCount) use(&$executorCalled): string {
                $executorCalled++;
                self::assertEquals($executorCalled, $pollCount);
                self::assertEquals('pollArg1', $pollArg1);
                self::assertEquals('text', $arg1);
                self::assertEquals(12.9, $arg2);

                return 3 === $executorCalled ? 'OK' : 'NOK';
            },
            ['text', 12.9]
        );

        $resultCheckerCalled = 0;
        $pollResultChecker = new PollResultChecker(
            static function (string $pollArg1, int $arg1, bool $arg2, string $result, Collection $previousResults, int $pollCount) use(&$resultCheckerCalled): bool {
                $resultCheckerCalled++;
                self::assertEquals($resultCheckerCalled, $pollCount);
                self::assertEquals('pollArg1', $pollArg1);
                self::assertEquals(1, $arg1);
                self::assertEquals(true, $arg2);

                return 'OK' === $result;
            },
            [1, true]
        );

        $poller = new Poller($pollExecutor, $pollResultChecker);
        $pollResult = $poller->startPolling(['pollArg1']);
        self::assertEquals(3, $pollResult->getCount());
        self::assertTrue($pollResult->isSuccess());
        self::assertFalse($pollResult->isFailed());
        self::assertEquals('OK', $pollResult->getResult());
    }

    /**
     * @return void
     * @throws Throwable
     * @throws ValueExceptionInterface
     * @throws CollectionException
     */
    public function testPollerWithoutExceptionCheckerShouldThrowException(): void
    {
        $this->expectExceptionObject(new Exception('NOK'));

        $pollExecutor = new PollExecutor(
            static function (string $pollArg1, Collection $previousResults, int $currentPollCount): string {
                self::assertEquals('pollArg1', $pollArg1);
                self::assertCount($currentPollCount - 1, $previousResults);

                throw new Exception('NOK');
            }
        );
        $pollResultChecker = new PollResultChecker(
            static function (string $pollArg1, string $result): bool {
                self::assertEquals('pollArg1', $pollArg1);

                return 'OK' === $result;
            }
        );
        $poller = new Poller($pollExecutor, $pollResultChecker);
        $poller->startPolling(['pollArg1']);
    }

    /**
     * @return void
     * @throws Throwable
     * @throws ValueExceptionInterface
     * @throws CollectionException
     */
    public function testPollerWithExceptionCheckerShouldNotThrowException(): void
    {
        $executorCalled = 0;
        $resultCheckerCalled = 0;
        $exceptionCheckerCalled = 0;

        $pollExecutor = new PollExecutor(
            static function (string $pollArg1, Collection $previousResults, int $currentPollCount) use(&$executorCalled): string {
                $executorCalled++;
                self::assertEquals('Polling is nice', $pollArg1);
                if (2 === $currentPollCount || 4 === $currentPollCount) {
                    throw new Exception('My nice exception which can be interpreted as a invalid poll result');
                }

                return 5 === $currentPollCount ? 'OK' : 'NOK';
            }
        );
        $pollResultChecker = new PollResultChecker(
            static function (string $pollArg1, string $result) use (&$resultCheckerCalled): bool {
                $resultCheckerCalled++;
                self::assertEquals('Polling is nice', $pollArg1);

                return 'OK' === $result;
            }
        );
        $pollExceptionChecker = new PollExceptionChecker(
            static function (string $pollArg1, bool $arg1, bool $arg2, string $arg3, int $arg4, Throwable $exception, Collection $previousResults, int $currentPollCount) use (&$exceptionCheckerCalled): bool {
                $exceptionCheckerCalled++;
                self::assertEquals('Polling is nice', $pollArg1);
                self::assertEquals(true, $arg1);
                self::assertEquals(false, $arg2);
                self::assertEquals('text', $arg3);
                self::assertEquals(2, $arg4);
                self::assertEquals($exceptionCheckerCalled * 2, $currentPollCount);

                return false;
            },
            [true, false, 'text', 2]
        );
        $poller = new Poller($pollExecutor, $pollResultChecker, $pollExceptionChecker);
        $pollResult = $poller->startPolling(['Polling is nice']);
        self::assertEquals(5, $pollResult->getCount());
        self::assertTrue($pollResult->isSuccess());
        self::assertFalse($pollResult->isFailed());
        self::assertEquals('OK', $pollResult->getResult());

        self::assertEquals(5, $executorCalled);
        self::assertEquals(3, $resultCheckerCalled);
        self::assertEquals(2, $exceptionCheckerCalled);
    }
}
