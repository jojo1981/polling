<?php declare(strict_types=1);
/*
 * This file is part of the jojo1981/polling package
 *
 * Copyright (c) 2021 Joost Nijhuis <jnijhuis81@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed in the root of the source code
 */
namespace Jojo1981\Polling\Checker;

use Jojo1981\Polling\Result\PollResult;
use Jojo1981\TypedCollection\Collection;
use function array_merge;
use function array_values;
use function call_user_func;

/**
 * @package Jojo1981\Polling\Checker
 */
final class PollResultChecker
{
    /** @var callable */
    private $callback;

    /** @var array */
    private array $arguments;

    /**
     * @param callable $callback
     * @param array $arguments
     */
    public function __construct(callable $callback, array $arguments = [])
    {
        $this->callback = $callback;
        $this->arguments = array_values($arguments);
    }

    /**
     * @param mixed $result
     * @param array $arguments
     * @param Collection|PollResult[] $previousResults
     * @param int $currentPollCount
     * @return bool
     */
    public function checkResult($result, array $arguments, Collection $previousResults, int $currentPollCount): bool
    {
        return call_user_func($this->callback, ...array_merge($arguments, $this->arguments, [$result, $previousResults, $currentPollCount]));
    }
}
