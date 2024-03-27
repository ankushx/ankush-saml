<?php

namespace MiniOrange\SP\Helper\Exception;

use MiniOrange\SP\Helper\SPMessages;

/**
 * Exception denotes that the SAML resquest or response has missing
 * ID attribute.
 */
class MissingAttributesException extends \Exception
{
    public function __construct()
    {
        $message     = SPMessages::parse('MISSING_ATTRIBUTES_EXCEPTION');
        $code         = 125;
        parent::__construct($message, $code, null);
    }

    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
