<?php

namespace PhpTec\JsonRpc\Client\Test\Support;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

class Logger implements LoggerInterface
{
    use LoggerTrait;

    public $logs = [];

    /**
     * {@inheritDoc}
     */
    public function log($level, $message, $context = []): void
    {
        $this->logs[] = compact('level', 'message', 'context');
    }
}