<?php declare(strict_types=1);
/*
 * This file is part of the jojo1981/polling package
 *
 * Copyright (c) 2021 Joost Nijhuis <jnijhuis81@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed in the root of the source code
 */
namespace Jojo1981\Polling\Value;

use Jojo1981\Contracts\Exception\ValueExceptionInterface;
use Jojo1981\Contracts\HashableInterface;
use Jojo1981\Contracts\ValueInterface;
use Jojo1981\Polling\Exception\ValueException;
use function hash;
use function is_int;
use function sprintf;

/**
 * @package Jojo1981\Polling\Value
 */
final class UnsignedInteger implements ValueInterface, HashableInterface
{
    /** @var int */
    private int $value;

    /**
     * @param int $value
     * @throws ValueExceptionInterface
     */
    public function __construct($value)
    {
        $this->value = $this->assertValue($value);
    }

    /**
     * @return int
     */
    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * @param ValueInterface $otherValue
     * @return bool
     */
    public function match(ValueInterface $otherValue): bool
    {
        return $otherValue instanceof self && $otherValue->getValue() === $this->getValue();
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return hash('sha256', (string) $this->value);
    }

    /**
     * @param mixed $value
     * @return int
     * @throws ValueExceptionInterface
     */
    private function assertValue($value): int
    {
        if (!is_int($value)) {
            throw new ValueException('Value for UnsignedInteger should be be of type integer');
        }

        if ($value < 0) {
            throw new ValueException(sprintf('Value for UnsignedInteger should be higher than or equals zero. Given value: `%d`.', $value));
        }

        return $value;
    }
}
