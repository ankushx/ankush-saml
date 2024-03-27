<?php

namespace MiniOrange\SP\Helper\Exception;

use MiniOrange\SP\Helper\SPMessages;

/**
 * Exception denotes that Issuer in the SAML response
 * doesn't match the one set by the plugin
 */
class InvalidIssuerException extends SAMLResponseException
{
    public function __construct($expect, $found, $xml)
    {
        $message     = SPMessages::parse('INVALID_ISSUER', ['expect'=>$expect,'found'=>$found]);
        $code         = 101;
        parent::__construct($message, $code, $xml, false);
        error_log("invalidIssuer");
    }

    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
