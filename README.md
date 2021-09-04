Polling library for conditionally retry operations based on a result checker.
=====================

[![Build Status](https://travis-ci.com/jojo1981/polling.svg?branch=master)](https://travis-ci.com/jojo1981/polling)
[![Coverage Status](https://coveralls.io/repos/github/jojo1981/polling/badge.svg)](https://coveralls.io/github/jojo1981/polling)
[![Latest Stable Version](https://poser.pugx.org/jojo1981/polling/v/stable)](https://packagist.org/packages/jojo1981/polling)
[![Total Downloads](https://poser.pugx.org/jojo1981/polling/downloads)](https://packagist.org/packages/jojo1981/polling)
[![License](https://poser.pugx.org/jojo1981/polling/license)](https://packagist.org/packages/jojo1981/polling)

Author: Joost Nijhuis <[jnijhuis81@gmail.com](mailto:jnijhuis81@gmail.com)>

A simple polling library which retries the poll action as long as the result checker is not returning a success result for a maximum number of times with a given delay in between.

The executor callback can perform any task you want and can return any result you want.
The result checker should be able to check whether the result from the executor is successful or not and must return a boolean value.
Optionally you can add an exception checker. This can be omitted and be handled in the executor, but for separation of concerns you can split this logic.
The delay in milliseconds can be given and is by default 10000 milliseconds (10 seconds). The delay will be between the retries.
The max poll count can be given and is by default 10. The max poll count is the number of times at maximum the executor will be invoked including the first time.
Default arguments for the executor, result checker, exception checker and start polling can be omitted. This can be handy to keep the callback functions pure.

## Installation

### Library

```bash
git clone https://github.com/jojo1981/polling.git
```

### Composer

[Install PHP Composer](https://getcomposer.org/doc/00-intro.md)

```bash
composer require jojo1981/polling
```

### Usage

```php
<?php

use Jojo1981\Polling\Checker\PollResultChecker;
use Jojo1981\Polling\Executor\PollExecutor;
use Jojo1981\Polling\Poller;
use Jojo1981\Polling\Result\PollResult;
use Jojo1981\Polling\Value\PollCount;
use Jojo1981\Polling\Value\UnsignedInteger;
use Jojo1981\TypedCollection\Collection;

require 'vendor/autoload.php';

// Example polling with 3 tries and then having a success result.
$pollExecutor = new PollExecutor(
    /**
     * @param string $text1
     * @param string $text2
     * @param int $number
     * @param Collection|PollResult[] $previousResults
     * @param int $currentPollCount
     * @return string
     */
    static function (string $text1, string $text2, int $number, Collection $previousResults, int $currentPollCount): string {
        // order of arguments: start polling arguments (when given), executor arguments (when given), $previousResults and $currentPollCount.

        return $currentPollCount === 3 ? 'This is a success result :)' : 'We are not yet there :(';
    },
    [1] // optionally the default arguments which will be given to the poll executor callback
);
$pollResultChecker = new PollResultChecker(
    /**
     * @param string $text1
     * @param string $text2
     * @param bool $number
     * @param string $result
     * @param Collection|PollResult[] $previousResults
     * @param int $currentPollCount
     * @return bool
     */
    static function (string $text1, string $text2, bool $number, string $result, Collection $previousResults, int $currentPollCount): bool {
        // order of arguments: start polling arguments (when given), executor arguments (when given), $previousResults and $currentPollCount.

        return 'This is a success result :)' === $result;
    },
    [1] // optionally the default arguments which will be given to the poll result checker callback
);

// poll delay is expressed in milliseconds, so poll max 5 times with in between 5 seconds delay.
$poller = new Poller($pollExecutor, $pollResultChecker, null, new UnsignedInteger(5000), new PollCount(5));
$finalPollResult = $poller->startPolling(['text1', 'text2']); // start polling with optionally some default arguments

$finalPollResult->getCount(); // Will return the number of poll retries including the first one. Value: 3 in this case.
$finalPollResult->isSuccess(); // Will return true when polling is succeeded. Value true in this case.
$finalPollResult->isFailed(); // Will return true when polling has been failed. Value false in this case.
$finalPollResult->getResult(); /// Will return the last poll result. Value 'This is a success result :)' in this case.
```
