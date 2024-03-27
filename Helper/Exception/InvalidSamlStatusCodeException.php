<?php

namespace MiniOrange\SP\Helper\Exception;

use MiniOrange\SP\Helper\SPMessages;
use MiniOrange\SP\Helper\SPUtility;

/**
 * Exception denotes that the SAML IDP sent a
 * Responder or Requester SAML response instead
 * of Success in the
 */
class InvalidSamlStatusCodeException extends SAMLResponseException
{
    public function __construct($statusCode, $xml)
    {
        $message     = SPMessages::parse('INVALID_INSTANT', ['statuscode'=>$statusCode]);
        $code         = 117;
        parent::__construct($message, $code, $xml, false);
    }

    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
