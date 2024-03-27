<?php

namespace MiniOrange\SP\Helper\Exception;

use MiniOrange\SP\Helper\SPMessages;

/**
 * Exception denotes that the SAML Issuer value is missing.
 */
class MissingIssuerValueException extends \Exception
{
    public function __construct()
    {
        $message     = SPMessages::parse('MISSING_ISSUER_VALUE');
        $code         = 123;
        parent::__construct($message, $code, null);
    }

    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
