<?php

namespace MiniOrange\SP\Helper\Exception;

use MiniOrange\SP\Helper\SPMessages;

/**
 * Exception denotes that the Issue Instant in the
 * SAML request is invalid.
 */
class InvalidSAMLInstantException extends \Exception
{
    public function __construct()
    {
        $message     = SPMessages::parse('INVALID_INSTANT');
        $code         = 117;
        parent::__construct($message, $code, null);
    }

    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
