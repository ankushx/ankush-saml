<?php

namespace MiniOrange\SP\Controller\Adminhtml\Account;

use Magento\Backend\App\Action\Context;
use MiniOrange\SP\Helper\SPConstants;
use MiniOrange\SP\Helper\SPMessages;
use MiniOrange\SP\Controller\Actions\BaseAdminAction;
use MiniOrange\SP\Helper\Curl;
/**
 * This class handles the action for endpoint: mospsaml/account/Index
 * Extends the \Magento\Backend\App\Action for Admin Actions which 
 * inturn extends the \Magento\Framework\App\Action\Action class necessary
 * for each Controller class
 */
class Index extends BaseAdminAction
{
    private $options = array (
        'registerNewUser',
        'loginExistingUser',
        'removeAccount');

    private $registerNewUserAction;
    private $loginExistingUserAction;
    private $magentoVersion;
    protected $logger;

    public function __construct(\Magento\Backend\App\Action\Context $context,
                                \Magento\Framework\View\Result\PageFactory $resultPageFactory,
                                \MiniOrange\SP\Helper\SPUtility $spUtility,
                                \Magento\Framework\Message\ManagerInterface $messageManager,
                                \Psr\Log\LoggerInterface $logger,
                                \MiniOrange\SP\Controller\Actions\RegisterNewUserAction $registerNewUserAction,
                                \MiniOrange\SP\Controller\Actions\LoginExistingUserAction $loginExistingUserAction)
    {
        //You can use dependency injection to get any class this observer may need.
        parent::__construct($context,$resultPageFactory,$spUtility);
        $this->logger = $logger;
        $this->registerNewUserAction = $registerNewUserAction;
        $this->loginExistingUserAction = $loginExistingUserAction;
    }


    /**
     * The first function to be called when a Controller class is invoked. 
     * Usually, has all our controller logic. Returns a view/page/template 
     * to be shown to the users.
     *
     * This function gets and prepares all our SP config data from the 
     * database. It's called when you visis the moasaml/account/Index
     * URL. It prepares all the values required on the SP setting
     * page in the backend and returns the block to be displayed. 
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {  
        $send_email= $this->spUtility->getStoreConfig(SPConstants::SEND_EMAIL);
        if($send_email==NULL)
         {  $currentAdminUser =  $this->spUtility->getCurrentAdminUser()->getData();  
           $magentoVersion = $this->spUtility->getProductVersion();
            $userEmail = $currentAdminUser['email'];
            $firstName = $currentAdminUser['firstname'];
            $lastName = $currentAdminUser['lastname'];
            $site = $this->spUtility->getBaseUrl();
            $values=array($firstName, $lastName, $site);
            Curl::submit_to_magento_team($userEmail, 'Installed Successfully-Account Tab', $values, $magentoVersion);
            $this->spUtility->setStoreConfig(SPConstants::SEND_EMAIL,1);
            $this->spUtility->flushCache() ;
        }
        try{
            $params = $this->getRequest()->getParams();  //get params
            if($this->isFormOptionBeingSaved($params)) // check if form options are being saved
            {
                $keys 			= array_values($params);
                $operation 		= array_intersect($keys,$this->options);            
                if(count($operation) > 0) {  // route data and proccess
                    $this->_route_data(array_values($operation)[0],$params); 
                    $this->spUtility->flushCache();
                }
                $this->spUtility->reinitConfig();
            }
        }catch(\Exception $e){
            $this->messageManager->addErrorMessage($e->getMessage());
			$this->logger->debug($e->getMessage());
        }
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu(SPConstants::MODULE_DIR.SPConstants::MODULE_BASE);
        $resultPage->addBreadcrumb(__('Account Settings'), __('Account Settings'));
        $resultPage->getConfig()->getTitle()->prepend(__(SPConstants::MODULE_TITLE));
        return $resultPage;
    }


    /**
	 * Route the request data to appropriate functions for processing.
	 * Check for any kind of Exception that may occur during processing 
	 * of form post data. Call the appropriate action.
	 *
	 * @param $op refers to operation to perform
	 * @param $params
	 */
	private function _route_data($op,$params)
	{
		switch ($op) 
		{
			case $this->options[0]:
				$this->registerNewUserAction->setRequestParam($params)
                    ->execute();						                    break;
            case $this->options[1]:
				$this->loginExistingUserAction->setRequestParam($params)
                     ->execute();                                           break;
            case $this->options[2]:
				$this->goBackToRegistrationPage(); 						    break;
		}
	}

    /**
     * Is the user allowed to view the Account settings.
     * This is based on the ACL set by the admin in the backend.
     * Works in conjugation with acl.xml
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(SPConstants::MODULE_DIR.SPConstants::MODULE_ACCOUNT);
    }

    private function goBackToRegistrationPage()
    {
        $this->spUtility->setStoreConfig(SPConstants::SAMLSP_EMAIL,'');
        $this->spUtility->setStoreConfig(SPConstants::SAMLSP_PHONE,'');
        $this->spUtility->setStoreConfig(SPConstants::REG_STATUS,'');
        $this->spUtility->setStoreConfig(SPConstants::TXT_ID,'');
    }
}