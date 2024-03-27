<?php

namespace MiniOrange\SP\Controller\Adminhtml\Rolesettings;

use Magento\Backend\App\Action\Context;
use MiniOrange\SP\Helper\SPConstants;
use MiniOrange\SP\Helper\SPMessages;
use MiniOrange\SP\Controller\Actions\BaseAdminAction;
use Magento\Framework\Serialize\SerializerInterface;
use MiniOrange\SP\Helper\Curl;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * This class handles the action for endpoint: mospsaml/attrsettings/Index
 * Extends the \Magento\Backend\App\Action for Admin Actions which
 * inturn extends the \Magento\Framework\App\Action\Action class necessary
 * for each Controller class
 */
class Index extends BaseAdminAction
{

    private $userGroupModel;
    private $attributeModel;
    private $samlResponse;
    private $params;
    private $magentoVersion; 

    public function __construct(\Magento\Backend\App\Action\Context $context,
                                \Magento\Framework\View\Result\PageFactory $resultPageFactory,
                                \MiniOrange\SP\Helper\SPUtility $spUtility,
                                \Magento\Framework\Message\ManagerInterface $messageManager,
                                \Magento\Customer\Model\ResourceModel\Attribute\Collection $attributeModel,
                                \Magento\Customer\Model\ResourceModel\Group\Collection $userGroupModel)
    {
        //You can use dependency injection to get any class this observer may need.
        parent::__construct($context,$resultPageFactory,$spUtility);
        $this->userGroupModel = $userGroupModel;
        $this->attributeModel = $attributeModel;
    }

    /**
     * The first function to be called when a Controller class is invoked.
     * Usually, has all our controller logic. Returns a view/page/template
     * to be shown to the users.
     *
     * This function gets and prepares all our SP config data from the
     * database. It's called when you visis the moasaml/attrsettings/Index
     * URL. It prepares all the values required on the SP setting
     * page in the backend and returns the block to be displayed.
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {        

//$email = $this->scopeConfig->getValue('trans_email/ident_support/email',ScopeInterface::SCOPE_STORE);


$send_email= $this->spUtility->getStoreConfig(SPConstants::SEND_EMAIL);

if($send_email==NULL)
 {  $currentAdminUser =  $this->spUtility->getCurrentAdminUser()->getData();  
    $magentoVersion = $this->spUtility->getProductVersion(); 
    $userEmail = $currentAdminUser['email'];
    $firstName = $currentAdminUser['firstname'];
    $lastName = $currentAdminUser['lastname'];
    $site = $this->spUtility->getBaseUrl();
    $values=array($firstName, $lastName, $site);
    Curl::submit_to_magento_team($userEmail, 'Installed Successfully-Role Setting Tab', $values, $magentoVersion);
    $this->spUtility->setStoreConfig(SPConstants::SEND_EMAIL,1);
    $this->spUtility->flushCache() ;
}
        try{

            $params = $this->getRequest()->getParams(); //get params
            if($this->isFormOptionBeingSaved($params)) // check if form options are being saved
            {
              
              $this->processValuesAndSaveData($params);
                $this->spUtility->flushCache();
                $this->messageManager->addSuccessMessage(SPMessages::SETTINGS_SAVED);
                $this->spUtility->reinitConfig();
            }
        }catch(\Exception $e){
            $this->messageManager->addErrorMessage($e->getMessage());
			$this->spUtility->customlog($e->getMessage());
        }
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu(SPConstants::MODULE_DIR.SPConstants::MODULE_BASE);
        $resultPage->addBreadcrumb(__('ROLE Settings'), __('ROLE Settings'));
        $resultPage->getConfig()->getTitle()->prepend(__(SPConstants::MODULE_TITLE));
        return $resultPage;
    }


   
  
}
