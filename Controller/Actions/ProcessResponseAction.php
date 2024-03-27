<?php

namespace MiniOrange\SP\Controller\Actions;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use MiniOrange\SP\Helper\Exception\MissingAttributesException;
use MiniOrange\SP\Helper\Xmlseclibs\XMLSecurityKey;
use MiniOrange\SP\Helper\Saml2\SAML2Response;
use MiniOrange\SP\Helper\Saml2\SAML2Utilities;
use MiniOrange\SP\Helper\Exception\InvalidAudienceException;
use MiniOrange\SP\Helper\Exception\InvalidIssuerException;
use MiniOrange\SP\Helper\Exception\InvalidSignatureInResponseException;
use MiniOrange\SP\Helper\Exception\InvalidSamlStatusCodeException;
use MiniOrange\SP\Helper\Exception\InvalidDestinationException;
use MiniOrange\SP\Helper\SPConstants;

/**
 * Handles processing of SAML Responses from the IDP. Process the SAML Response
 * from the IDP and detect if it's a valid response from the IDP. Validate the
 * certificates and the SAML attributes and Update existing user attributes
 * and groups if necessary. Log the user in.
 */
class ProcessResponseAction extends BaseAction
{
    private $samlResponse;
    private $certfpFromPlugin;
    private $acsUrl;
    private $relayState;
    private $responseSigned;
    private $assertionSigned;
    private $issuer;
    private $spEntityId;
    private $attrMappingAction;
    protected $x509_certificate;
    

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \MiniOrange\SP\Helper\SPUtility $spUtility,
        \MiniOrange\SP\Controller\Actions\CheckAttributeMappingAction $attrMappingAction
    ) {
        //You can use dependency injection to get any class this observer may need.    
        $this->spEntityId = $spUtility->getIssuerUrl();

        //You can use dependency injection to get any class this observer may need.    
        $this->attrMappingAction = $attrMappingAction;
        parent::__construct($context, $spUtility);
        $this->acsUrl = $this->spUtility->getAcsUrl();
    }
    
    /**
     * Execute function to execute the classes function.
     * @return ResponseInterface|ResultInterface|string|null
     * @throws InvalidAudienceException
     * @throws InvalidDestinationException
     * @throws InvalidIssuerException
     * @throws InvalidSamlStatusCodeException
     * @throws InvalidSignatureInResponseException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws MissingAttributesException
     */
    public function execute()
    {
        $this->spUtility->customlog(" in processResponseAction :");                
        $this->validateStatusCode();
        
        $this->spUtility->customlog(" processResponseAction : execute: validated status code");
        $idp_name = $this->spUtility->getSessionData(SPConstants::IDP_NAME);
        $collection = $this->spUtility->getIDPApps();
        $idpDetails=null;   

      //storing values from custom table in $idpDetails array 
        foreach($collection as $item){  
            if($item->getData()["idp_name"]===$idp_name){   
                 $idpDetails=$item->getData();    
                }   
            }
            $this->x509_certificate = $idpDetails['x509_certificate'];
            $this->issuer = $idpDetails['idp_entity_id'];
            $this->assertionSigned = $idpDetails['assertion_signed'];
            $this->responseSigned = $idpDetails['response_signed'];
        $responseSignatureData = $this->samlResponse->getSignatureData();
        $assertionSignatureData = current($this->samlResponse->getAssertions())->getSignatureData();
        $this->certfpFromPlugin = XMLSecurityKey::getRawThumbprint($idpDetails['x509_certificate']);
        $this->certfpFromPlugin = iconv("UTF-8", "CP1252//IGNORE", $this->certfpFromPlugin);
        $this->certfpFromPlugin = preg_replace('/\s+/', '', $this->certfpFromPlugin);
        
        //$this->validateDestinationURL();
        $this->validateResponseSignature($responseSignatureData);
         $this->spUtility->customlog(" processResponseAction : execute: validated response signature");
        $this->validateAssertionSignature($assertionSignatureData);
        $this->spUtility->customlog(" processResponseAction : execute: validated assertion signature");
        //$this->validateIssuerAndAudience();
        return $this->attrMappingAction->setSamlResponse($this->samlResponse)
            ->setRelayState($this->relayState)->execute();
    }


    /**
     * Function checks if the signature in the Response element
     * of the SAML response is a valid response. Throw an error
     * otherwise.
     *
     * @param $responseSignatureData
     * @throws InvalidSignatureInResponseException
     */
    private function validateResponseSignature($responseSignatureData)
    {   $this->spUtility->customlog(" processResponseAction : inside validateResponseSignature");
        if ($this->responseSigned!="1" || empty($responseSignatureData)) {
            $this->spUtility->customlog(" processResponseAction : response not signed");
             return;
        }
        $validSignature = SAML2Utilities::processResponse(
            $this->certfpFromPlugin,
            $responseSignatureData,
            $this->samlResponse
        );
        if (!$validSignature) {
            throw new InvalidSignatureInResponseException(
                $this->x509_certificate,
                $responseSignatureData['Certificates'][0],
                $this->samlResponse->getXML()
            );
        }
        $this->spUtility->customlog(" processResponseAction : response signed");
      
    }
    
    /**
     * Function checks if the status coming in the SAML
     * response is SUCCESS and not a responder or
     * requester
     *
     * @param $responseSignatureData
     * @throws InvalidSamlStatusCodeException
     */
    private function validateStatusCode()
    {
        $statusCode = $this->samlResponse->getStatusCode();
        if (strpos($statusCode, 'Success')===false) {
            throw new InvalidSamlStatusCodeException($statusCode, $this->samlResponse->getXML());
        }
    }


    /**
     * Function checks if the signature in the Assertion element
     * of the SAML response is a valid response. Throw an error
     * otherwise.
     *
     * @param $assertionSignatureData
     * @throws InvalidSignatureInResponseException
     */
    private function validateAssertionSignature($assertionSignatureData)
    {$this->spUtility->customlog(" processResponseAction : inside validateAssertionSignature");
        if ($this->assertionSigned!="1" || empty($assertionSignatureData)) {
            $this->spUtility->customlog(" processResponseAction : assertion not signed");
            return;
        }
        $validSignature = SAML2Utilities::processResponse(
            $this->certfpFromPlugin,
            $assertionSignatureData,
            $this->samlResponse
        );
        if (!$validSignature) {
            throw new InvalidSignatureInResponseException(
                $this->x509_certificate,
                $assertionSignatureData['Certificates'][0],
                $this->samlResponse->getXML()
            );
        }
        $this->spUtility->customlog(" processResponseAction : assertion signed");
    }


    /**
     * Function validates the Issuer and Audience from the
     * SAML Response. THrows an error if the Issuer and
     * Audience values don't match with the one in the
     * database.
     *
     * @throws InvalidIssuerException
     * @throws InvalidAudienceException
     */
    private function validateIssuerAndAudience()
    {
        $issuer = current($this->samlResponse->getAssertions())->getIssuer();
        $audience = current(current($this->samlResponse->getAssertions())->getValidAudiences());
        if (strcmp($this->issuer, $issuer) != 0) {
            throw new InvalidIssuerException($this->issuer, $issuer, $this->samlResponse->getXML());
        }
        if (strcmp($audience, $this->spEntityId) != 0) {
            throw new InvalidAudienceException($this->spEntityId, $audience, $this->samlResponse->getXML());
        }
    }


    /**
     * Function validates the Destination in the SAML Response.
     * Throws an error if the Destination doesn't match
     * with the one in the database.
     *
     * @param $currentURL
     * @throws InvalidDestinationException
     */
    private function validateDestinationURL()
    {
        $msgDestination = $this->samlResponse->getDestination();
        if ($msgDestination !== null && $msgDestination !== $this->acsUrl) {
            throw new InvalidDestinationException($msgDestination, $this->acsUrl, $this->samlResponse);
        }
    }


    /** Setter for the SAML Response Parameter
     * @param SAML2Response $samlResponse
     * @return ProcessResponseAction
     */
    public function setSamlResponse(SAML2Response $samlResponse)
    {
        $this->samlResponse = $samlResponse;
        return $this;
    }


    /** Setter for the RelayState Parameter
     * @param $relayState
     * @return ProcessResponseAction
     */
    public function setRelayState($relayState)
    {
        $this->relayState = $relayState;
        return $this;
    }
}
