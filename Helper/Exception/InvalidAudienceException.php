<?php

namespace MiniOrange\SP\Helper\Exception;

use MiniOrange\SP\Helper\SPMessages;

/**
 * Exception denotes that the audience value in
 * the SAML response doesn't match the one set
 * by the plugin
 */
class InvalidAudienceException extends SAMLResponseException
{
    public function __construct($expect, $found, $xml)
    {
        $message     = SPMessages::parse('INVALID_AUDIENCE', ['expect'=>$expect,'found'=>$found]);
        $code         = 108;
        parent::__construct($message, $code, $xml, false);
        error_log("invalidAudience");
    }

    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
