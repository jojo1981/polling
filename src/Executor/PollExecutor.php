<?php declare(strict_types=1);
/*
 * This file is part of the jojo1981/polling package
 *
 * Copyright (c) 2021 Joost Nijhuis <jnijhuis81@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed in the root of the source code
 */
namespace Jojo1981\Polling\Executor;

use Jojo1981\Polling\Result\PollResult;
use Jojo1981\TypedCollection\Collection;
use function array_merge;
use function array_values;
use function call_user_func;

/**
 * @package Jojo1981\Polling\Executor
 */
final class PollExecutor
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
     * @param array $arguments
     * @param Collection|PollResult[] $previousResults
     * @param int $currentPollCount
     * @return mixed
     */
    public function execute(array $arguments, Collection $previousResults, int $currentPollCount)
    {
        return call_user_func($this->callback, ...array_merge($arguments, $this->arguments, [$previousResults, $currentPollCount]));
    }
}
