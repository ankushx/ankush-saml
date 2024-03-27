<?php

namespace MiniOrange\SP\Controller\Actions;

use MiniOrange\SP\Helper\Saml2\SAML2Response;
use MiniOrange\SP\Helper\Saml2\SAML2Assertion;
use MiniOrange\SP\Helper\SPZendUtility;
use MiniOrange\SP\Helper\SPConstants;

/**
 * Handles reading of SAML Responses from the IDP. Read the SAML Response
 * from the IDP and process it to detect if it's a valid response from the IDP.
 * Generate a SAML Response Object and log the user in. Update existing user
 * attributes and groups if necessary.
 */
class ReadResponseAction extends BaseAction
{   protected $REQUEST;
    protected $POST;
    private $processResponseAction;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \MiniOrange\SP\Helper\SPUtility $spUtility,
        \MiniOrange\SP\Controller\Actions\ProcessResponseAction $processResponseAction
    ) {
        //You can use dependency injection to get any class this observer may need.
        $this->processResponseAction = $processResponseAction;
        parent::__construct($context, $spUtility);
    }

    /**
     * Execute function to execute the classes function.
     * @throws NotRegisteredException
     * @throws InvalidSAMLVersionException
     * @throws MissingIDException
     * @throws MissingIssuerValueException
     * @throws MissingNameIdException
     * @throws InvalidNumberOfNameIDsException
     * @throws \Exception
     */
    public function execute()
    {
        $this->spUtility->customlog(" inside class ReadResponseAction : execute: ");
        // read the response
        $samlResponse = $this->REQUEST['SAMLResponse'];        
        $relayState  = isset($this->REQUEST['RelayState']) ? $this->REQUEST['RelayState'] : '/';
        //decode the saml response
        $this->spUtility->customlog(" inside class ReadResponseAction : relaystate: ",$relayState);
        $samlResponse = base64_Decode($samlResponse);    
        if (!isset($this->POST['SAMLResponse'])) {
            $samlResponse = gzInflate($samlResponse);
        }
        
        $document = new \DOMDocument();
        $document->loadXML($samlResponse); 
        $samlResponseXML = $document->firstChild; 
        $saml_obj          = new SAML2Assertion($samlResponseXML,$this->spUtility);
        $issuer =$saml_obj->getIssuer();

		$collection = $this->spUtility->getIDPApps();    
        $idpDetails=null;    
        foreach($collection as $item){  
            if($item->getData()["idp_entity_id"]===$issuer){   
                $idpDetails=$item->getData();    
            }   
        }
		$this->spUtility->setSessionData(SPConstants::IDP_NAME,$idpDetails['idp_name']);

        //if logout response then redirect the user to the relayState
        if ($samlResponseXML->localName == 'LogoutResponse') {       
            return $this->resultRedirectFactory->create()->setUrl($relayState);
        }
        
        $samlResponse = new SAML2Response($samlResponseXML, $this->spUtility); 
           //convert the xml to SAML2Response object
           $attrs = current($samlResponse->getAssertions());        
        return $this->processResponseAction->setSamlResponse($samlResponse)
            ->setRelayState($relayState)->execute();
    }
    public function setRequestParam($request)
    {
		$this->REQUEST = $request;
		return $this;
    }


    /** Setter for the post Parameter */
    public function setPostParam($post)
    {
		$this->POST = $post;
		return $this;
    }
}
