<?php

namespace MiniOrange\SP\Helper\Saml2;

use MiniOrange\SP\Helper\Saml2\SAML2Utilities;

/**
 * This class is used to generate a metadata XML string.
 * The string will then have to be written in to a file.
 *
 * @todo Signature in the metadata file for validation.
 * @todo This class can be expanded to introduce dynamic changing metadata feature.
 * @todo Add Caching and Time limit for validitiy of the metadata.
 */
class MetadataGenerator
{
    private $xml;
    private $issuer;
    private $samlLoginURL;
    private $wantAssertionSigned;
    private $x509Certificate;
    private $nameIdFormats;
    private $singleSignOnServiceURLs;
    private $singleLogoutServiceURLs;
    private $acsUrl;
    private $authnRequestSigned;
    protected $nameIDFormats;

    public function __construct(
        $issuer,
        $wantAssertionSigned,
        $authnRequestSigned,
        $x509Certificate,
        $ssoURLPost,
        $ssoURLRedirect,
        $sloURLPost,
        $sloURLRedirect,
        $acsUrl
    ) {
        $this->xml                         = new \DOMDocument("1.0", "utf-8");
        $this->xml->preserveWhiteSpace     = false;
        $this->xml->formatOutput         = true;

        $this->issuer                     = $issuer;
        $this->wantAssertionSigned         = $wantAssertionSigned;
        $this->authnRequestSigned       = $authnRequestSigned;
        $this->x509Certificate             = $x509Certificate;
        $this->nameIDFormats             = ["urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress",
                                                "urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified"];
        $this->singleSignOnServiceURLs     = ["urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST"=>$ssoURLPost,
                                                "urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect"=>$ssoURLRedirect];
        $this->singleLogoutServiceURLs    = ["urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST"=>$sloURLPost,
                                                "urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect"=>$sloURLRedirect];
        $this->acsUrl                   = $acsUrl;
    }

    /**
     * The main function of this class called to generate
     * a Metadata string. This function handles which elements
     * need to be present in the Metadata file based on the
     * initialized values.
     */
    public function generateSPMetadata()
    {
        //Generating the Metadata Element
        $entity = $this->createEntityDescriptorElement();
        $this->xml->appendChild($entity);

        //Generating the SPDescriptor Element
        $descriptor = $this->createSpDescriptorElement();
        $entity->appendChild($descriptor);

        //Generate the Key descriptor element for idpDescriptor
        $key = $this->createKeyDescriptorElement('signing');
        $descriptor->appendChild($key);
        
        //Generate the Key descriptor element for RoleDescriptor
        $key2 = $this->createKeyDescriptorElement('encryption');
        $descriptor->appendChild($key2);
        
        //Generate SingleLogout URL Elements
        $sloUrlElements = $this->createSLOUrls();
        foreach ($sloUrlElements as $sloUrlElement) {
            $descriptor->appendChild($sloUrlElement);
        }

        //Generate NameID Formats
        $nameIDFormatElements = $this->createNameIdFormatElements();
        foreach ($nameIDFormatElements as $nameIDFormatElement) {
            $descriptor->appendChild($nameIDFormatElement);
        }
        
        //Generate AssertionConsumerService Element
        $acsElement = $this->createAcsUrlElement();
        $descriptor->appendChild($acsElement);

        $metadata = $this->xml->saveXML();
        return $metadata;
    }

    /**
     * The main function of this class called to generate
     * a Metadata string. This function handles which elements
     * need to be present in the Metadata file based on the
     * initialized values.
     */
    public function generateIdPMetadata()
    {
        //Generating the Metadata Element
        $entity = $this->createEntityDescriptorElement();
        $this->xml->appendChild($entity);

        //Generating the IdpDescriptor Element
        $idpDescriptor = $this->createIdpDescriptorElement();
        $entity->appendChild($idpDescriptor);

        //Generating the WsFed RoleDescriptor Element
        $roleDescriptor = $this->createRoleDescriptorElement();
        $entity->appendChild($roleDescriptor);

        //Generate the Key descriptor element for idpDescriptor
        $key = $this->createKeyDescriptorElement();
        $idpDescriptor->appendChild($key);
        
        //Generate the Key descriptor element for RoleDescriptor
        $key2 = $this->createKeyDescriptorElement();
        $roleDescriptor->appendChild($key2);

        //Generate the Token Type element
        $tokenTypes = $this->createTokenTypesElement();
        $roleDescriptor->appendChild($tokenTypes);

        //Generate the passive request for endpoint
        $passiveRequestEndpoints = $this->createPassiveRequestEndpoints();
        $roleDescriptor->appendChild($passiveRequestEndpoints);

        //Generate NameID Formats
        $nameIDFormatElements = $this->createNameIdFormatElements();
        foreach ($nameIDFormatElements as $nameIDFormatElement) {
            $idpDescriptor->appendChild($nameIDFormatElement);
        }

        //Generate SingleLogin URL Elements
        $ssoUrlElements = $this->createSSOUrls();
        foreach ($ssoUrlElements as $ssoUrlElement) {
            $idpDescriptor->appendChild($ssoUrlElement);
        }

        //Generate SingleLogout URL Elements
        $sloUrlElements = $this->createSLOUrls();
        foreach ($sloUrlElements as $sloUrlElement) {
            $idpDescriptor->appendChild($sloUrlElement);
        }

        $metadata = $this->xml->saveXML();
        return $metadata;
    }

    
    /**
     * This function is used to create the main EntityIdDescriptor Element
     */
    private function createEntityDescriptorElement()
    {
        $entity = $this->xml->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'EntityDescriptor');
        $entity->setAttribute('entityID', $this->issuer);
        return $entity;
    }


    /**
     * This function is used to create the IdpDescriptor Element
     * This elements lists all the configuration values for the
     * IDP ( plugin ).
     */
    private function createIdpDescriptorElement()
    {
        $idpDescriptor = $this->xml->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'IDPSSODescriptor');
        $idpDescriptor->setAttribute('WantAuthnRequestsSigned', $this->wantAssertionSigned);
        $idpDescriptor->setAttribute('protocolSupportEnumeration', 'urn:oasis:names:tc:SAML:2.0:protocol');
        return $idpDescriptor;
    }


    /**
     * This function is used to create the AssertionConsumerService
     * Element of the Sp Metadata.
     */
    private function createAcsUrlElement()
    {
        $acsUrlElement = $this->xml->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'AssertionConsumerService');
        $acsUrlElement->setAttribute('Binding', "urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST");
        $acsUrlElement->setAttribute('Location', $this->acsUrl);
        $acsUrlElement->setAttribute('Index', "1");
        return $acsUrlElement;
    }
    

    /**
     * This function is used to create the SPDescriptor Element
     * This elements lists all the configuration values for the
     * SP ( plugin ).
     */
    private function createSPDescriptorElement()
    {
        $spDescriptor = $this->xml->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'SPSSODescriptor');
        $spDescriptor->setAttribute('WantAuthnRequestsSigned', $this->wantAssertionSigned);
        $spDescriptor->setAttribute('AuthnRequestsSigned', $this->authnRequestSigned);
        $spDescriptor->setAttribute('protocolSupportEnumeration', 'urn:oasis:names:tc:SAML:2.0:protocol');
        return $spDescriptor;
    }


    /**
     * This function is used to generate the KeyDescriptor ELement.
     * This element contains the x509 certificate information which
     * is required by the SP to validate SAML response.
     */
    private function createKeyDescriptorElement($use)
    {
        $key = $this->xml->createElement('KeyDescriptor');
        $key->setAttribute('use', $use);
        $keyInfo = $this->generateKeyInfo();
        $key->appendChild($keyInfo);
        return $key;
    }


    /**
     * This function is used to generate the keyinfo, certificate elements.
     * Needs to be appended to the KeyDescriptor element.
     */
    private function generateKeyInfo()
    {
        $keyInfo = $this->xml->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:KeyInfo');
        $certdata = $this->xml->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:X509Data');
        $certValue = SAML2Utilities::desanitize_certificate($this->x509Certificate);
        $certElement = $this->xml->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:X509Certificate', $certValue);
        $certdata->appendChild($certElement);
        $keyInfo->appendChild($certdata);
        return $keyInfo;
    }


    /**
     * This function creates the NameIDFormats Element which
     * defines the NameIdFormat supported by the IDP.
     */
    private function createNameIdFormatElements()
    {
        $nameIDFormatElements = [];
        foreach ($this->nameIDFormats as $nameIDFormat) {
            array_push($nameIDFormatElements, $this->xml->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'NameIDFormat', $nameIDFormat));
        }
        return $nameIDFormatElements;
    }


    /**
     * This function creates the SingleSignOnService URL elements.
     * This defines the HTTP POST and HTTP Redirect endpoints.
     */
    private function createSSOUrls()
    {
        $ssoUrlElements = [];
        foreach ($this->singleSignOnServiceURLs as $binding => $url) {
            $ssoURLElement = $this->xml->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'SingleSignOnService');
            $ssoURLElement->setAttribute('Binding', $binding);
            $ssoURLElement->setAttribute('Location', $url);
            array_push($ssoUrlElements, $ssoURLElement);
        }
        return $ssoUrlElements;
    }


    /**
     * This function creates the SingleLogoutService URL elements.
     * This defines the HTTP POST and HTTP Redirect endpoints.
     */
    private function createSLOUrls()
    {
        $sloUrlElements = [];
        foreach ($this->singleLogoutServiceURLs as $binding => $url) {
            $sloUrlElement = $this->xml->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'SingleLogoutService');
            $sloUrlElement->setAttribute('Binding', $binding);
            $sloUrlElement->setAttribute('Location', $url);
            array_push($sloUrlElements, $sloUrlElement);
        }
        return $sloUrlElements;
    }


    /**
     * This function creates the RoleDescriptor Element for Ws-Fed.
     * This defines the attributes required for WS-FED SSO.
     */
    private function createRoleDescriptorElement()
    {
        $roleDescriptor = $this->xml->createElement('RoleDescriptor');
        $roleDescriptor->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $roleDescriptor->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:fed', 'http://docs.oasis-open.org/wsfed/federation/200706');
        $roleDescriptor->setAttribute('ServiceDisplayName', "miniOrnage Inc");
        $roleDescriptor->setAttribute('xsi:type', "fed:SecurityTokenServiceType");
        $roleDescriptor->setAttribute('protocolSupportEnumeration', "http://docs.oasis-open.org/ws-sx/ws-trust/200512 http://schemas.xmlsoap.org/ws/2005/02/trust http://docs.oasis-open.org/wsfed/federation/200706");
        return $roleDescriptor;
    }

    /**
     * This function creates the WS-Fed TokenTypes Element. Defines
     * the type of assertion being send in the response to the SP.
     */
    private function createTokenTypesElement()
    {
        $tokenTypes = $this->xml->createElement('fed:TokenTypesOffered');
        $samlToken = $this->xml->createElement('fed:TokenType');
        $samlToken->setAttribute('Uri', 'urn:oasis:names:tc:SAML:1.0:assertion');
        $tokenTypes->appendChild($samlToken);
        return $tokenTypes;
    }

    /**
     * This function creates the WS-Fed PassiveRequestorEndpoint Element. Defines
     * the URLs the Service Providers will send the Ws-Fed Requests to.
     */
    private function createPassiveRequestEndpoints()
    {
        $passiveRequestEndpoints = $this->xml->createElement('fed:PassiveRequestorEndpoint');
        $endpointReference = $this->xml->createElementNS('http://www.w3.org/2005/08/addressing', 'ad:EndpointReference');
        $endpointReference->appendChild(
            $this->xml->createElement('Address', $this->singleSignOnServiceURLs['urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST'])
        );
        $passiveRequestEndpoints->appendChild($endpointReference);
        return $passiveRequestEndpoints;
    }
}
