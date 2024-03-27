<?php

namespace MiniOrange\SP\Helper\Exception;

use MiniOrange\SP\Helper\SPMessages;

/**
 * Exception denotes that the version in the SAML
 * request made is Invalid.
 */
class InvalidSAMLVersionException extends SAMLResponseException
{
    public function __construct($xml)
    {
        $message     = SPMessages::parse('INVALID_SAML_VERSION');
        $code         = 118;
        parent::__construct($message, $code, $xml, false);
    }

    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
