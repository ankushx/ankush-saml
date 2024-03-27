<?php

namespace MiniOrange\SP\Helper\Exception;

use MiniOrange\SP\Helper\SPMessages;

/**
 * Exception denotes a SAMLResponseException.
 * This exception is not thrown but is
 * extended by other exception classes.
 */
class SAMLResponseException extends \Exception
{
    private $samlResponse;
    private $isCertError;
    public function __construct($message, $code, $xml, $isCertError)
    {
        $this->xml     = $xml;
        $this->isCertError = $isCertError;
        parent::__construct($message, $code, null);
    }

    public function getSamlResponse()
    {
        return SPMessages::parse(
            'SAML_RESPONSE',
            ['xml'=>$this->parseXML($this->xml)]
        );
    }

    public function isCertError()
    {
        return $this->isCertError;
    }


    /**
     * This function is used to show an XML in
     * the plain text format for debugging
     * purposes.
     */
    public static function parseXML($xml)
    {
        $dom = new \DOMDocument;
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = true;
        $dom->loadXML($xml->ownerDocument->saveXML($xml));
        return htmlentities($dom->saveXml());
    }
}
