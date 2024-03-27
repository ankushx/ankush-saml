<?php

namespace MiniOrange\SP\Helper\Exception;

use MiniOrange\SP\Helper\SPMessages;

/**
 * Exception denotes that the SAML resquest or response has missing
 * ID attribute.
 */
class MissingIDException extends \Exception
{
    public function __construct()
    {
        $message     = SPMessages::parse('MISSING_ID_FROM_RESPONSE');
        $code         = 125;
        parent::__construct($message, $code, null);
    }

    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
