<?php 

namespace MiniOrange\SP\Controller\Actions;

use MiniOrange\SP\Helper\Curl;
use MiniOrange\SP\Helper\SPConstants;
use MiniOrange\SP\Helper\SPMessages;
use MiniOrange\SP\Helper\SPUtility;
use MiniOrange\SP\Helper\Exception\PasswordMismatchException;
use MiniOrange\SP\Helper\Exception\AccountAlreadyExistsException;
use MiniOrange\SP\Helper\Exception\TransactionLimitExceededException;

/**
 * Handles registration of new user account. This is called when the 
 * registration form is submitted. Process the credentials and 
 * information provided by the admin.
 * 
 * This action class first checks if a customer exists with the email
 * address provided. If no customer exists then start the validation process.
 */
class RegisterNewUserAction extends BaseAdminAction
{
    private $loginExistingUserAction;
    protected $logger;

	public function __construct(\Magento\Backend\App\Action\Context $context,
                                \Magento\Framework\View\Result\PageFactory $resultPageFactory,
                                \MiniOrange\SP\Helper\SPUtility $spUtility,
                                \Magento\Framework\Message\ManagerInterface $messageManager,
                                \Psr\Log\LoggerInterface $logger,
                                \MiniOrange\SP\Controller\Actions\LoginExistingUserAction $loginExistingUserAction)
    {
        //You can use dependency injection to get any class this observer may need.
        parent::__construct($context,$resultPageFactory,$spUtility);
        $this->loginExistingUserAction = $loginExistingUserAction;
        $this->logger = $logger;
    }

    
	/**
	 * Execute function to execute the classes function. 
     * 
	 * @throws \Exception
	 */

    //  update----not required first,last,company name ....changed the code accordingly
    public function execute()
    {
        $this->logger->debug("RegisterNewUserAction: execute()");
        $email = $this->REQUEST['email'];
        $password = $this->REQUEST['password'];
        $confirmPassword = isset($this->REQUEST['confirmPassword']) ? $this->REQUEST['confirmPassword'] : null; // Check if confirmPassword is set
        
        $this->checkIfRequiredFieldsEmpty(['email' => $email, 'password' => $password]);
        
        $companyName = '';
        $firstName = '';
        $lastName = '';
        
         // Check if confirmPassword is set and non-empty
    if ($confirmPassword !== null && !empty($confirmPassword)) {
        if (strcasecmp($confirmPassword, $password) != 0) {
            throw new PasswordMismatchException;
        }
    }
    
    $result = $this->checkIfUserExists($email);
        
        $result = $this->checkIfUserExists($email);
        if (strcasecmp($result['status'], 'CUSTOMER_NOT_FOUND') == 0) {
            //first time user
            $this->spUtility->setStoreConfig(SPConstants::SAMLSP_EMAIL, $email);
            $this->spUtility->setStoreConfig(SPConstants::SAMLSP_CNAME, $companyName);
            $this->spUtility->setStoreConfig(SPConstants::SAMLSP_FIRSTNAME, $firstName);
            $this->spUtility->setStoreConfig(SPConstants::SAMLSP_LASTNAME, $lastName);
            $this->spUtility->setStoreConfig(SPConstants::REG_STATUS, SPConstants::STATUS_COMPLETE_LOGIN);
            $this->startVerificationProcess($result, $email, $companyName, $firstName, $lastName, $password);
        } else {
        //     //already register
            if($confirmPassword)
            {
                throw new \Exception("You already have an account, please login to continue");
            }
             $this->spUtility->setStoreConfig(SPConstants::SAMLSP_EMAIL, $email);
            $this->loginExistingUserAction
            ->setRequestParam($this->REQUEST)
            ->execute();
        }
    }
    

    /**
     * Function is used to make a cURL call which will check
     * if a user exists with the given credentials. If a user
     * is found then his details are fetched automatically and
     * saved.
     * 
     * @param $email
     */
    
    private function checkIfUserExists($email)
    {
        $this->logger->debug("RegisterNewUserAction: checkIfUserExists");
        $content = Curl::check_customer($email);
        return json_decode($content, true);
    }
    

    private function startVerificationProcess($result,$email,$companyName,$firstName,$lastName,$password)
    {
        $this->logger->debug("RegisterNewUserAction: StartVerificationProcess");

        if ($this->REQUEST['confirmPassword']=='') {
          
            throw new \Exception("Account does not exist");
        } else {
            $this->createUserInMiniorange($result, $email, $companyName, $firstName, $lastName, $password);
        }
    }


    private function createUserInMiniorange($result,$email,$companyName,$firstName,$lastName,$pass)
    {
        $this->logger->debug("In createUserInMiniorange()");
        $result = Curl::create_customer($email, $companyName,$pass, '', $firstName, $lastName);
        $result= json_decode($result, true);
        $this->logger->debug(print_r($result,true));
        if (strcasecmp($result['status'], 'SUCCESS') == 0) {
            $content = Curl::get_customer_key($email, $pass);
            $customerKey = json_decode($content, true);
            $this->configureUserInMagento($result,$customerKey);
        }
        elseif(strcasecmp($result['status'], 'CUSTOMER_USERNAME_ALREADY_EXISTS') == 0)
        {
            $this->spUtility->setStoreConfig(SPConstants::REG_STATUS, '');
            throw new AccountAlreadyExistsException;
        }
        elseif(strcasecmp($result['status'], 'TRANSACTION_LIMIT_EXCEEDED')==0)
        {
            $this->spUtility->setStoreConfig(SPConstants::REG_STATUS, '');
            throw new TransactionLimitExceededException;
        }
        
    }

    private function configureUserInMagento($result,$customerKey)
    {
        $this->logger->debug("In configureUserInMagento()");
        $this->spUtility->setStoreConfig(SPConstants::SAMLSP_KEY, $result['id']);
        $this->spUtility->setStoreConfig(SPConstants::API_KEY, $result['apiKey']);
        $this->spUtility->setStoreConfig(SPConstants::TOKEN, $result['token']);
        $this->spUtility->setStoreConfig(SPConstants::REG_STATUS, SPConstants::STATUS_COMPLETE_LOGIN);
        $this->getMessageManager()->addSuccessMessage(SPMessages::REG_SUCCESS);
    }
}