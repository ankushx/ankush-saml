<?php

namespace MiniOrange\SP\Helper\Exception;

use MiniOrange\SP\Helper\SPMessages;

/**
 * Exception denotes that the request or response has more
 * than 1 NameID.
 */
class InvalidNumberOfNameIDsException extends \Exception
{
    public function __construct()
    {
        $message     = SPMessages::parse('INVALID_NO_OF_NAMEIDS');
        $code         = 124;
        parent::__construct($message, $code, null);
    }

    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
