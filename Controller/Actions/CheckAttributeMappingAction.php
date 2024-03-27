<?php

namespace MiniOrange\SP\Controller\Actions;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use MiniOrange\SP\Helper\Exception\MissingAttributesException;
use MiniOrange\SP\Helper\Saml2\SAML2Response;
use MiniOrange\SP\Helper\SPConstants;

/**
 * This class handles checking of the SAML attributes and NameID
 * coming in the response and mapping it to the attribute mapping
 * done in the plugin settings by the admin to update the user.
 */
class CheckAttributeMappingAction extends BaseUserAction
{
    const TEST_VALIDATE_RELAYSTATE = SPConstants::TEST_RELAYSTATE;

    private $samlResponse;
    private $relayState;
    private $testAction;
    private $processUserAction;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \MiniOrange\SP\Helper\SPUtility $spUtility,
        \MiniOrange\SP\Controller\Actions\ShowTestResultsAction $testAction,
        \MiniOrange\SP\Controller\Actions\ProcessUserAction $processUserAction
    ) {
        //You can use dependency injection to get any class this observer may need.
        parent::__construct($context, $spUtility);
        $this->processUserValues();
        $this->testAction = $testAction;
        $this->processUserAction = $processUserAction;
    }

    /**
     * Execute function to execute the classes function.
     * @return ResponseInterface|ResultInterface|string|null
     * @throws MissingAttributesException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $this->spUtility->customlog(" inside CheckAttributeMappingAction : exceute ");
        $ssoemail = current(current($this->samlResponse->getAssertions())->getNameId());
        $attrs = current($this->samlResponse->getAssertions())->getAttributes();
     if (!filter_var($ssoemail, FILTER_VALIDATE_EMAIL)) {
        $ssoemail=$this->findUserEmail($attrs);
    }
        $attrs['NameID'] = [$ssoemail];   
        $sessionIndex = current($this->samlResponse->getAssertions())->getSessionIndex();
        return $this->moSAMLcheckMapping($attrs, $sessionIndex);
    }

    private function findUserEmail($attrs)
    {
        
        if ($attrs) {
            foreach ($attrs as $value) {
                if (is_array($value)) {
                    $value = $this->findUserEmail($value);
                }
                if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return $value;
                }
            }
            return "";
        }
    }

    /**
     * This function checks the SAML Attribute Mapping done
     * in the plugin and matches it to update the user's
     * attributes.
     *
     * @param $attrs
     * @param $sessionIndex
     * @return ResponseInterface|ResultInterface|string|null
     * @throws MissingAttributesException ;
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function moSAMLcheckMapping($attrs, $sessionIndex)
    {
        if (empty($attrs)) {
            throw new MissingAttributesException;
        }
        if ($this->spUtility->isBlank($this->checkIfMatchBy)) {
            $this->checkIfMatchBy = SPConstants::DEFAULT_MAP_BY;
        }
        $this->processUserName($attrs);
        $this->processEmail($attrs);
        $this->processGroupName($attrs);
        return $this->processResult($attrs, $sessionIndex, $attrs['NameID']);
    }


    /**
     * Process the result to either show a Test result
     * screen or log/create user in Magento.
     *
     * @param $attrs
     * @param $sessionIndex
     * @param $nameId
     * @return ResponseInterface|ResultInterface|string|null
     * @throws MissingAttributesException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function processResult($attrs, $sessionIndex, $nameId)
    {
        switch ($this->relayState) {
            case self::TEST_VALIDATE_RELAYSTATE :{
                return $this->testAction->setAttrs($attrs)->setNameId($nameId[0])->execute();

            }
            default:{
                return $this->processUserAction->setAttrs($attrs)->setRelayState($this->relayState)
                    ->setSessionIndex($sessionIndex)->execute();
                    
            }
                
        }
    }


    /**
     * Check if the attribute list has a FirstName. If
     * no firstName is found then NameID is considered as
     * the firstName. This is done because Magento needs
     * a firstName for creating a new user.
     *
     * @param $attrs
     */
    private function processFirstName(&$attrs)
    {
        if (!isset($attrs[$this->firstName])) {
            $temp = explode('@',$attrs['NameID'][0]);
            $attrs[$this->firstName][0] = $temp[0] ;
            $this->spUtility->customlog(" inside CheckAttributeMappingAction : processFirstName: Changed firstName: ". $attrs[$this->firstName][0]);
        }
    }


    private function processLastName(&$attrs)
    {
        if(!isset($attrs[$this->lastName])){
            $temp = explode('@',$attrs['NameID'][0]);
            $attrs[$this->lastName][0] = $temp[1] ;
            $this->spUtility->customlog(" inside CheckAttributeMappingAction : processLastName: Changed LastName: ". $attrs[$this->lastName][0]);
        }

    }

    /**
     * Check if the attribute list has a UserName. If
     * no UserName is found then NameID is considered as
     * the UserName. This is done because Magento needs
     * a UserName for creating a new user.
     *
     * @param $attrs
     */
    private function processUserName(&$attrs)
    {
        if (!isset($attrs[$this->usernameAttribute])) {
            $attrs[$this->usernameAttribute][0]
                = $this->checkIfMatchBy==SPConstants::DEFAULT_MAP_USERN ? $attrs['NameID'][0] : null;
        }
    }


    /**
     * Check if the attribute list has a Email. If
     * no Email is found then NameID is considered as
     * the Email. This is done because Magento needs
     * a Email for creating a new user.
     *
     * @param $attrs
     */
    private function processEmail(&$attrs)
    {
        if (!isset($attrs[$this->emailAttribute])) {
            $attrs[$this->emailAttribute][0]
                = $this->checkIfMatchBy==SPConstants::DEFAULT_MAP_EMAIL ? $attrs['NameID'][0] : null;
        }
    }


    /**
     * Check if the attribute list has a Group/Role. If
     * no Group/Role is found then NameID is considered as
     * the Group/Role. This is done because Magento needs
     * a Group/Role for creating a new user.
     *
     * @param $attrs
     */
    private function processGroupName(&$attrs)
    {
        if (!isset($attrs[$this->groupName])) {
            $this->groupName = [];
        }
    }
    

    /** Setter for the SAML Response Parameter */
    public function setSamlResponse(SAML2Response $samlResponse)
    {
        $this->samlResponse = $samlResponse;
        return $this;
    }


    /** Setter for the RelayState Parameter */
    public function setRelayState($relayState)
    {
        $this->relayState = $relayState;
        $this->spUtility->customlog(" inside CheckAttributeMappingAction : setRelayState: relayState: ". $this->relayState);
        return $this;
    }
}
