<?php

namespace MiniOrange\SP\Helper\Exception;

use MiniOrange\SP\Helper\SPMessages;

/**
 * Exception denotes that there was an Invalid Operation
 */
class InvalidOperationException extends \Exception
{
    public function __construct()
    {
        $message     = SPMessages::parse('INVALID_OP');
        $code         = 105;
        parent::__construct($message, $code, null);
    }

    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
