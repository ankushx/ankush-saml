<?php

namespace MiniOrange\SP\Controller\Actions;

use MiniOrange\SP\Helper\Exception\NotRegisteredException;
use MiniOrange\SP\Helper\Saml2\AuthnRequest;
use MiniOrange\SP\Helper\SPConstants;


/**
 * Handles generation and sending of AuthnRequest to the IDP
 * for authentication. AuthnRequest is generated and user is
 * redirected to the IDP for authentication.
 */
class SendAuthnRequest extends BaseAction
{

    /**
     * Execute function to execute the classes function.
     * @throws \Exception
     */
    public function execute()
    {
        $this->spUtility->customlog(" inside class SendAuthnRequest : execute: ");
        $params = $this->getRequest()->getParams();  //get params

        $idp_name = isset($params["idp_name"]) ? $params["idp_name"] : $this->spUtility->getStoreConfig(SPConstants::IDP_NAME);
        $this->spUtility->setSessionData(SPConstants::IDP_NAME,$idp_name);
        $collection = $this->spUtility->getIDPApps();       
        $idpDetails=null;    
     
     //storing values from custom table in $idpDetails array 
     foreach($collection as $item){  
         if($item->getData()["idp_name"]===$idp_name){   
             $idpDetails=$item->getData();
            }
        }
        $idpLoginUrl = $idpDetails['saml_login_url'];
        if ($this->spUtility->isBlank($idpLoginUrl)) {
            return;
        }
        $relayState = isset($params['relayState']) ? $params['relayState'] : '';
        $this->spUtility->customlog(" inside class SendAuthnRequest : relayState: ",$relayState);
        //get required values from the database
        
        $ssoUrl = $idpDetails['saml_login_url']; 
        $bindingType = $idpDetails['saml_login_binding']; 
        $forceAuthn = $idpDetails['force_authentication_with_idp']; 
        $acsUrl = $this->spUtility->getAcsUrl();
        $issuer = $this->spUtility->getIssuerUrl();
        $this->spUtility->customlog(" inside class SendAuthnRequest : ssoUrl: ",$ssoUrl);
        $this->spUtility->customlog(" inside class SendAuthnRequest : bindingType: ",$bindingType);
        $this->spUtility->customlog(" inside class SendAuthnRequest : forceAuthn: ",$forceAuthn);
        $this->spUtility->customlog(" inside class SendAuthnRequest : acsUrl: ",$acsUrl);
        $this->spUtility->customlog(" inside class SendAuthnRequest : issuer: ",$issuer);
        
        //generate the saml request
        $samlRequest = (new AuthnRequest($acsUrl, $issuer, $ssoUrl, $forceAuthn, $bindingType))->build();
        $idp_name = $this->spUtility->getSessionData(SPConstants::IDP_NAME);
        $this->spUtility->customlog("before sending saml request", $idp_name);
        // send saml request over
        if (empty($bindingType)
            || $bindingType == SPConstants::HTTP_REDIRECT) {
            return $this->sendHTTPRedirectRequest($samlRequest, $relayState, $ssoUrl,$params);
        } else {
            $this->sendHTTPPostRequest($samlRequest, $relayState, $ssoUrl);
        }

    }
}
