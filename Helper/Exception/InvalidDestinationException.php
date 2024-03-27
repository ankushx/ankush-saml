<?php

namespace MiniOrange\SP\Helper\Exception;

use MiniOrange\SP\Helper\SPMessages;

/**
 * Exception denotes that the destination value
 * in the SAML Response doesn't match the one
 * set by the plugin.
 */
class InvalidDestinationException extends SAMLResponseException
{
    public function __construct($destination, $currenturl, $xml)
    {
        $message     = SPMessages::parse('INVALID_DESTINATION', ['destination'=>$destination,'currenturl'=>$currenturl]);
        $code         = 108;
        parent::__construct($message, $code, $xml, false);
    }

    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
