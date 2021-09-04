<?php declare(strict_types=1);
/*
 * This file is part of the jojo1981/polling package
 *
 * Copyright (c) 2021 Joost Nijhuis <jnijhuis81@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed in the root of the source code
 */
namespace Jojo1981\Polling;

use Jojo1981\Contracts\Exception\ValueExceptionInterface;
use Jojo1981\Polling\Checker\PollExceptionChecker;
use Jojo1981\Polling\Checker\PollResultChecker;
use Jojo1981\Polling\Executor\PollExecutor;
use Jojo1981\Polling\Result\PollResult;
use Jojo1981\Polling\Value\PollCount;
use Jojo1981\Polling\Value\UnsignedInteger;
use Jojo1981\TypedCollection\Collection;
use Jojo1981\TypedCollection\Exception\CollectionException;
use Throwable;
use function array_values;
use function usleep;

/**
 * @package Jojo1981\Polling
 */
final class Poller
{
    /** @var PollExecutor */
    private PollExecutor $pollExecutor;

    /** @var PollResultChecker */
    private PollResultChecker $pollResultChecker;

    /** @var PollExceptionChecker|null */
    private ?PollExceptionChecker $pollExceptionChecker;

    private UnsignedInteger $delay; // milliseconds

    /** @var PollCount */
    private PollCount $maxPolCount;

    /**
     * @param PollExecutor $pollExecutor
     * @param PollResultChecker $pollResultChecker
     * @param PollExceptionChecker|null $pollExceptionChecker
     * @param UnsignedInteger|null $delay
     * @param PollCount|null $maxPolCount
     * @throws ValueExceptionInterface
     */
    public function __construct(
        PollExecutor $pollExecutor,
        PollResultChecker $pollResultChecker,
        ?PollExceptionChecker $pollExceptionChecker = null,
        ?UnsignedInteger $delay = null,
        ?PollCount $maxPolCount = null
    ) {
        $this->pollExecutor = $pollExecutor;
        $this->pollResultChecker = $pollResultChecker;
        $this->pollExceptionChecker = $pollExceptionChecker;
        $this->delay = $delay ?? new UnsignedInteger(10000); // 10 seconds delay
        $this->maxPolCount = $maxPolCount ?? new PollCount(10);
    }

    /**
     * @param array $arguments
     * @return PollResult
     * @throws CollectionException
     * @throws Throwable
     */
    public function startPolling(array $arguments = []): PollResult
    {
        $arguments = array_values($arguments);
        $previousPollResults = new Collection(PollResult::class);
        $pollCount = 0;

        do {
            $pollCount++;
            $pollResult = $this->poll($arguments, $previousPollResults, $pollCount);
            $previousPollResults->pushElement($pollResult);

            if ($pollCount < $this->maxPolCount->getValue() && $pollResult->isFailed()) {
                usleep($this->delay->getValue());
            }

        } while ($pollCount < $this->maxPolCount->getValue() && $pollResult->isFailed());

        return $pollResult;
    }

    /**
     * @param array $arguments
     * @param Collection|PollResult[] $previousPollResults
     * @param int $currentPollCount
     * @return PollResult
     * @throws Throwable
     */
    private function poll(array $arguments, Collection $previousPollResults, int $currentPollCount): PollResult
    {
        $exception =  null;
        $result = null;

        try {
            $result = $this->pollExecutor->execute($arguments, $previousPollResults, $currentPollCount);
        } catch (Throwable $exception) {
            if (null === $this->pollExceptionChecker) {
                throw $exception;
            }
        }

        if (null === $exception) {
            $success = $this->pollResultChecker->checkResult($result, $arguments, $previousPollResults, $currentPollCount);
        } else {
            $result = $exception;
            $success = $this->pollExceptionChecker->checkException($exception, $arguments, $previousPollResults, $currentPollCount);
        }

        return new PollResult($result, $success, $currentPollCount);
    }
}
