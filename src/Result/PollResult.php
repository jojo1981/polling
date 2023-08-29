<?php declare(strict_types=1);
/*
 * This file is part of the jojo1981/polling package
 *
 * Copyright (c) 2021 Joost Nijhuis <jnijhuis81@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed in the root of the source code
 */
namespace Jojo1981\Polling\Result;

/**
 * @package Jojo1981\Polling\Result
 * @template T
 */
final class PollResult
{
    /** @var T */
    private $result;

    /** @var bool */
    private bool $success;

    /** @var int */
    private int $count;

    /**
     * @param T $result
     * @param bool $success
     * @param int $count
     */
    public function __construct($result, bool $success, int $count)
    {
        $this->result = $result;
        $this->success = $success;
        $this->count = $count;
    }

    /**
     * @return T
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * @return bool
     */
    public function isFailed(): bool
    {
        return !$this->success;
    }

    /**
     * @return int
     */
    public function getCount(): int
    {
        return $this->count;
    }
}
