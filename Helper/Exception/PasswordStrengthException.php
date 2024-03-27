<?php

namespace MiniOrange\SP\Helper\Exception;

use MiniOrange\SP\Helper\SPMessages;

/**
 * Exception denotes that user didnot provide a valid
 * password and confirm password.
 */
class PasswordStrengthException extends \Exception
{
    public function __construct()
    {
        $message     = SPMessages::parse('INVALID_PASS_STRENGTH');
        $code         = 110;
        parent::__construct($message, $code, null);
    }

    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
