<?php

namespace MiniOrange\SP\Controller\Actions;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use MiniOrange\SP\Helper\Exception\MissingAttributesException;
use MiniOrange\SP\Helper\SPConstants;
use Magento\Framework\Serialize\SerializerInterface;
use MiniOrange\SP\Helper\SPMessages;
use MiniOrange\SP\Helper\Exception\AutoCreateUserLimitExceedException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use MiniOrange\SP\Helper\Curl;
/**
 * This action class processes the user attributes coming in
 * the SAML response to either log the customer or admin in
 * to their respective dashboard or create a customer or admin
 * based on the default role set by the admin and log them in
 * automatically.
 */
class ProcessUserAction extends BaseUserAction implements SerializerInterface
{
    private $attrs;
    private $relayState;
    private $sessionIndex;
    private $customerModel;
    private $customerLoginAction;
    private $customerFactory;
    private $randomUtility;
    protected $messageManager;
    protected $scopeConfig;
    protected $defaultRole;
    protected $storeManager;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \MiniOrange\SP\Helper\SPUtility $spUtility,
        \Magento\Customer\Model\Customer $customerModel,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \MiniOrange\SP\Controller\Actions\CustomerLoginAction $customerLoginAction,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Framework\Math\Random $randomUtility,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        ScopeConfigInterface $scopeConfig
    ) {
        //You can use dependency injection to get any class this observer may need.
        parent::__construct($context, $spUtility);
        $this->processUserValues();
        $this->defaultRole = $spUtility->getStoreConfig(SPConstants::DEFAULT_ROLE);
        $this->customerModel = $customerModel;
        $this->storeManager = $storeManager;
        $this->customerLoginAction = $customerLoginAction;
        $this->customerFactory = $customerFactory;
        $this->randomUtility = $randomUtility;
        $this->messageManager = $messageManager;
        $this->scopeConfig = $scopeConfig;
    }


    /**
     * Execute function to execute the classes function.
     *
     * @return ResponseInterface|ResultInterface|string
     * @throws MissingAttributesException
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $this->spUtility->customlog(" inside class ProcessUserAction : execute: ");
        // throw an exception if attributes are empty
        if (empty($this->attrs)) {
            throw new MissingAttributesException;
        }
        // get and set all the necessary attributes
        $user_email = isset($this->attrs[$this->emailAttribute]) ? $this->attrs[$this->emailAttribute][0] : null;
        $userName = isset($this->attrs[$this->usernameAttribute]) ? $this->attrs[$this->usernameAttribute][0]: null;
        if ($this->spUtility->isBlank($this->checkIfMatchBy)) {
            $this->checkIfMatchBy = SPConstants::DEFAULT_MAP_BY;
        }
        // process the user
        return $this->processUserAction(
            $user_email,
            $userName,
            $this->checkIfMatchBy,
            $this->attrs['NameID'][0]
        );
    }


    /**
     * This function processes the user values to either create
     * a new user on the site and log him/her in or log an existing
     * user to the site. Mapping is done based on $checkIfMatchBy
     * variable. Either email or username.
     *
     * @param $user_email
     * @param $firstName
     * @param $lastName
     * @param $userName
     * @param $checkIfMatchBy
     * @param $nameId
     * @return ResponseInterface|ResultInterface|string
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function processUserAction(
        $user_email,
        $userName,
        $checkIfMatchBy,
        $nameId
    ) {
        $this->spUtility->customlog(" inside class ProcessUserAction : processUserAction: ");

            $user = $this->getCustomerFromAttributes($user_email);
        // if no user found then create user
        if (!$user) {
            $this->spUtility->customlog("Inside autocreate user tab");
            $donotCreateUsers=$this->spUtility->getStoreConfig(SPConstants::MAGENTO_COUNT);
            if(is_null($donotCreateUsers)){
             $this->spUtility->customlog(" Magento Count is null" );
             $this->spUtility->setStoreConfig(SPConstants::MAGENTO_COUNT,10);
             $this->spUtility->reinitConfig();
             $donotCreateUsers=$this->spUtility->getStoreConfig(SPConstants::MAGENTO_COUNT);
            }
            $this->spUtility->customlog("Magento Count: ".$donotCreateUsers);
            if($donotCreateUsers<1){
                $email = $this->scopeConfig->getValue('trans_email/ident_support/email',ScopeInterface::SCOPE_STORE);
                $site = $this->spUtility->getBaseUrl();
                $magentoVersion = $this->spUtility->getProductVersion();
                Curl::submit_to_magento_team_autocreate_limit_exceeded($email,$site, $magentoVersion);
                $this->messageManager->addErrorMessage(SPMessages::AUTO_CREATE_USER_LIMIT);
                return $this->spUtility->getUrl($this->relayState);
            }
            else{
                $count=$this->spUtility->getStoreConfig(SPConstants::MAGENTO_COUNT);
                $this->spUtility->customlog("Magento Count: ".$count);
                $this->spUtility->setStoreConfig(SPConstants::MAGENTO_COUNT,$count-1);
                $this->spUtility->customlog("Magento updated Count: ".$count-1);
                $user = $this->createNewUser(
                $user_email,
                $userName,
                $nameId,
                $user
            );
            }
           
        } else {
            $this->updateUserAttributes($nameId, $user);
        }
        // log the user in to it's respective dashboard
        $this->spUtility->customlog(" inside class ProcessUserAction : processUserAction relayState: ",$this->relayState);
        
        return $this->customerLoginAction->setUser($user)->setRelayState($this->relayState)->execute();
    }

    /**
     * This function udpates the user attributes based on the value
     * in the SAML Response. This function decides if the user is
     * a customer or an admin and update it's attribute accordingly
     *
     * @param $firstName
     * @param $lastName
     * @param $userName
     * @param $user
     * @throws \Exception
     */
    private function updateUserAttributes(
        $nameId,
        $user
    ) {
        $this->spUtility->customlog(" inside class ProcessUserAction : updateUserAttributes: ");
        $userId = $user->getId();
        $parts = explode("@",$nameId);
        $firstName = preg_replace('/[^a-zA-Z0-9\s]/', '', $parts[0]);
        $lastName = preg_replace('/[^a-zA-Z0-9\s]/', '', $parts[1]);
        // update the attributes
        $this->spUtility->saveConfig(SPConstants::DB_FIRSTNAME, $firstName, $userId);
        $this->spUtility->saveConfig(SPConstants::DB_LASTNAME, $lastName, $userId);
        if (!$this->spUtility->isBlank($this->sessionIndex)) {
            $this->spUtility->saveConfig(SPConstants::SESSION_INDEX, $this->sessionIndex, $userId);
        }
        if (!$this->spUtility->isBlank($nameId)) {
            $this->spUtility->saveConfig(SPConstants::NAME_ID, $nameId, $userId);
        }
        //update group
        $user->setData('group_id', 1); // customer cannot have multiple groups
        $user->save();
        
    }
    
    /**
     * Create a temporary email address based on the username
     * in the SAML response. Email Address is a required so we
     * need to generate a temp/fake email if no email comes from
     * the IDP in the SAML response.
     *
     * @param $userName
     * @return string
     */
    private function generateEmail($userName)
    {
       
        $siteurl = $this->spUtility->getBaseUrl();
        $siteurl = substr($siteurl, strpos($siteurl, '//'), strlen($siteurl)-1);
        return $userName .'@'.$siteurl;
    }

    /**
     * Create a new user based on the SAML response and attributes. Log the user in
     * to it's appropriate dashboard. This class handles generating both admin and
     * customer users.
     *
     * @param $user_email
     * @param $firstName
     * @param $lastName
     * @param $userName
     * @param $nameId
     * @param $user
     * @return \Magento\User\Model\User|null
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function createNewUser(
        $user_email,
        $userName,
        $nameId,
        $user
    ) {
        // generate random string to be inserted as a password
        $this->spUtility->customlog(" inside class ProcessUserAction : createNewUser: ");
        $parts  = explode("@", $user_email);
        $firstName = preg_replace('/[^a-zA-Z0-9\s]/', '', $parts[0]);
        $lastName = preg_replace('/[^a-zA-Z0-9\s]/', '', $parts[1]);

        $random_password = $this->randomUtility->getRandomString(8);
        $userName = !$this->spUtility->isBlank($userName)? $userName : $user_email;
        $email = !$this->spUtility->isBlank($user_email)? $user_email : $this->generateEmail($userName);
        $firstName = !$this->spUtility->isBlank($firstName) ? $firstName : $userName;
        $lastName = !$this->spUtility->isBlank($lastName) ? $lastName : $userName;
        $setRole = 1;
        // create customer based on the role
        $createdUser = $this->createCustomer($userName, $firstName, $lastName, $email, $random_password, $setRole);
        // update session index and nameID in the database for thuser
        if (!$this->spUtility->isBlank($this->sessionIndex)) {
            $this->spUtility->saveConfig(SPConstants::SESSION_INDEX, $this->sessionIndex, $createdUser->getId());
        }
        if (!$this->spUtility->isBlank($nameId)) {
            $this->spUtility->saveConfig(SPConstants::NAME_ID, $nameId, $createdUser->getId());
        }
        return $createdUser;
    }

    /**
     * Create a new customer.
     *
     * @param $userName
     * @param $firstName
     * @param $lastName
     * @param $email
     * @param $random_password
     * @param $role_assigned
     * @return
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function createCustomer($userName, $firstName, $lastName, $email, $random_password, $role_assigned)
    {
        $this->spUtility->customlog(" inside class ProcessUserAction : createCustomer: ");
        $websiteId = $this->storeManager->getWebsite()->getWebsiteId();
        $store = $this->storeManager->getStore();
        $storeId = $store->getStoreId();
        $customer = $this->customerFactory->create()
                        ->setWebsiteId($websiteId)
                        ->setFirstname($firstName)
                        ->setLastname($lastName)
                        ->setEmail($email)
                        ->setPassword($random_password)
                        ->save();
        $assign_role = is_array($role_assigned) ? $role_assigned[0] : $role_assigned;
        $customer->setGroupId($assign_role); // customer cannot have multiple groups
        $customer->save();
        return $customer;
    }


    /**
     * Get the Customer User from the Attributes in the SAML response
     * Return false if the customer doesn't exist. The customer is fetched
     * by email only. There are no usernames to set for a Magento Customer.
     *
     * @param $user_email
     * @param $userName
     * @return bool|\Magento\Customer\Model\Customer
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getCustomerFromAttributes($user_email)
    {
        $this->spUtility->customlog(" inside class ProcessUserAction : getCustomerFromAttributes user_email: ",$user_email);
        $this->customerModel->setWebsiteId($this->storeManager->getStore()->getWebsiteId());
        $customer = $this->customerModel->loadByEmail($user_email);
        return !is_null($customer->getId()) ? $customer : false;
    }


    /** The setter function for the Attributes Parameter */
    public function setAttrs($attrs)
    {
        $this->attrs = $attrs;
        return $this;
    }
    

    /** The setter function for the RelayState Parameter */
    public function setRelayState($relayState)
    {
        $this->relayState = $relayState;
        return $this;
    }


    /** The setter function for the SessionIndex Parameter */
    public function setSessionIndex($sessionIndex)
    {
        $this->sessionIndex = $sessionIndex;
        return $this;
    }

    /**
     * Serialize data into string
     *
     * @param string|int|float|bool|array|null $data
     * @return string|bool
     * @throws \InvalidArgumentException
     * @since 101.0.0
     */
    public function serialize($data)
    {
        // TODO: Implement serialize() method.
    }

    /**
     * Unserialize the given string
     *
     * @param string $string
     * @return string|int|float|bool|array|null
     * @throws \InvalidArgumentException
     * @since 101.0.0
     */
    public function unserialize($string)
    {
        // TODO: Implement unserialize() method.
    }
}
