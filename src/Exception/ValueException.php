<?php declare(strict_types=1);
/*
 * This file is part of the jojo1981/polling package
 *
 * Copyright (c) 2021 Joost Nijhuis <jnijhuis81@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed in the root of the source code
 */
namespace Jojo1981\Polling\Exception;

use DomainException;
use Jojo1981\Contracts\Exception\ValueExceptionInterface;

/**
 * @package Jojo1981\Polling\Exception
 */
final class ValueException extends DomainException implements ValueExceptionInterface
{
}
