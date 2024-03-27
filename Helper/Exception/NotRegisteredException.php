<?php

namespace MiniOrange\SP\Helper\Exception;

use MiniOrange\SP\Helper\SPMessages;

/**
 * Exception denotes that user has not completed his registration.
 */
class NotRegisteredException extends \Exception
{
    public function __construct()
    {
        $message     = SPMessages::parse('NOT_REG_ERROR');
        $code         = 102;
        parent::__construct($message, $code, null);
    }

    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
