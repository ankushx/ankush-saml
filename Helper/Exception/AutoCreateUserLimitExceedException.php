<?php

namespace MiniOrange\SP\Helper\Exception;

use MiniOrange\SP\Helper\SPMessages;

/**
 * Exception denotes that the user trying to log in
 * or register in the plugin already has an account
 * and that the credentials provided are incorrect
 */
class AutoCreateUserLimitExceedException extends \Exception
{
    public function __construct()
    {
        $message     = SPMessages::parse('AUTO_CREATE_USER_LIMIT');
        $code         = 127;
        parent::__construct($message, $code, null);
    }

    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
