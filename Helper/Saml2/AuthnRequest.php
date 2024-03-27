<?php

namespace MiniOrange\SP\Helper\Saml2;

use MiniOrange\SP\Helper\Saml2\SAML2Utilities;
use MiniOrange\SP\Helper\SPConstants;
use MiniOrange\SP\Helper\Exception\InvalidRequestInstantException;
use MiniOrange\SP\Helper\Exception\InvalidRequestVersionException;
use MiniOrange\SP\Helper\Exception\MissingIssuerValueException;

/**
 * This class is used to generate our AuthnRequest object.
 * The generate function is called to generate an XML
 * document that can then be passed to the IDP for
 * validation.
 *
 * @todo - the generateXML function uses string. Need to convert it so that request
 *        - is generated using \Dom functions
 */
class AuthnRequest
{

   
    private $requestType = SPConstants::AUTHN_REQUEST;
    private $acsUrl;
    private $issuer;
    private $ssoUrl;
    private $forceAuthn;
    private $bindingType;
    protected $destination;

    public function __construct($acsUrl, $issuer, $ssoUrl, $forceAuthn, $bindingType)
    {
        // all values required in the authn request are set here
        $this->acsUrl = $acsUrl;
        $this->issuer = $issuer;
        $this->forceAuthn = $forceAuthn;
        $this->destination = $ssoUrl;
        $this->bindingType = $bindingType;
    }

    /**
     * This function is called to generate our authnRequest. This is an internal
     * function and shouldn't be called directly. Call the @build function instead.
     * It returns the string format of the XML and encode it based on the sso
     * binding type.
     *
     * @todo - Have to convert this so that it's not a string value but an XML document
     */
    private function generateXML()
    {
        $requestXmlStr = '<?xml version="1.0" encoding="UTF-8"?>' .
                        ' <samlp:AuthnRequest 
                                xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" 
                                xmlns="urn:oasis:names:tc:SAML:2.0:assertion" ID="' . SAML2Utilities::generateID() .
                            '"  Version="2.0" IssueInstant="' . SAML2Utilities::generateTimestamp() . '"';
        
        // add force authn element
        if ($this->forceAuthn == 'TRUE') {
            $requestXmlStr .= ' ForceAuthn="true"';
        }
    
        $requestXmlStr .= '     ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" AssertionConsumerServiceURL="' . $this->acsUrl .
                        '"      Destination="' . $this->destination . '">
                                <saml:Issuer xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion">'. $this->issuer .'</saml:Issuer>
                                <samlp:NameIDPolicy AllowCreate="true" Format="urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified"/>
                            </samlp:AuthnRequest>';
        return $requestXmlStr;
    }


    /**
     * This function is used to build our AuthnRequest. Deflate
     * and encode the AuthnRequest string if the sso binding
     * type is empty or is of type HTTPREDIRECT.
     */
    public function build()
    {
        $requestXmlStr = $this->generateXML();
        if (empty($this->bindingType)
            || $this->bindingType == SPConstants::HTTP_REDIRECT) {
            $deflatedStr = gzdeflate($requestXmlStr);
            $base64EncodedStr = base64_encode($deflatedStr);
            $urlEncoded = urlencode($base64EncodedStr);
            $requestXmlStr = $urlEncoded;
        }
        return $requestXmlStr;
    }
}
