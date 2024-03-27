<?php

namespace MiniOrange\SP\Helper\Exception;

use MiniOrange\SP\Helper\SPMessages;
use MiniOrange\SP\Helper\SPUtility;

/**
 * Exception denotes that the Signature In the SAML
 * request is invalid.
 */
class InvalidSignatureInResponseException extends SAMLResponseException
{
    private $pluginCert;
    private $certInResponse;

    public function __construct($pluginCert, $certInResponse, $xml)
    {
        $message     = SPMessages::parse('INVALID_RESPONSE_SIGNATURE');
        $code         = 120;
        $this->pluginCert = $pluginCert;
        $this->certInResponse = $certInResponse;
        parent::__construct($message, $code, $xml, true);
    }

    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }

    public function getPluginCert()
    {
        return SPMessages::parse('FORMATTED_CERT', ['cert'=>$this->pluginCert]);
    }

    public function getCertInResponse()
    {
        return SPMessages::parse('FORMATTED_CERT', ['cert'=>$this->certInResponse]);
    }
}
