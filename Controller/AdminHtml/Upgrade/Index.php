<?php

namespace MiniOrange\SP\Controller\Adminhtml\Upgrade;

use Magento\Backend\App\Action\Context;
use MiniOrange\SP\Helper\SPConstants;
use MiniOrange\SP\Helper\SPMessages;
use MiniOrange\SP\Controller\Actions\BaseAdminAction;
use MiniOrange\SP\Helper\Curl;
/**
 * This class handles the action for endpoint: mospsaml/upgrade/Index
 * Extends the \Magento\Backend\App\Action for Admin Actions which 
 * inturn extends the \Magento\Framework\App\Action\Action class necessary
 * for each Controller class
 */ 
class Index extends BaseAdminAction
{

private $magentoVersion;
    /**
     * The first function to be called when a Controller class is invoked. 
     * Usually, has all our controller logic. Returns a view/page/template 
     * to be shown to the users.
     *
     * This function gets and prepares all our upgrade /license page.
     * It's called when you visis the moasaml/upgrade/Index
     * URL. It prepares all the values required on the license upgrade
     * page in the backend and returns the block to be displayed. 
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {           $send_email= $this->spUtility->getStoreConfig(SPConstants::SEND_EMAIL);
        
        if($send_email==NULL)
         {  $currentAdminUser =  $this->spUtility->getCurrentAdminUser()->getData(); 
            $magentoVersion = $this->spUtility->getProductVersion();  
            $userEmail = $currentAdminUser['email'];
            $firstName = $currentAdminUser['firstname'];
            $lastName = $currentAdminUser['lastname'];
            $site = $this->spUtility->getBaseUrl();
            $values=array($firstName, $lastName, $site);
            Curl::submit_to_magento_team($userEmail, 'Installed Successfully-Upgrade Tab', $values, $magentoVersion);
            $this->spUtility->setStoreConfig(SPConstants::SEND_EMAIL,1);
            $this->spUtility->flushCache() ;
        }
        try{
            // $this->checkIfValidPlugin(); //check if user has registered himself
        }catch(\Exception $e){
            $this->messageManager->addErrorMessage($e->getMessage());
			$this->spUtility->customlog($e->getMessage());
        }
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu(SPConstants::MODULE_DIR.SPConstants::MODULE_BASE);
        $resultPage->addBreadcrumb(__('Upgrade Plans'), __('Upgrade Plans'));
        $resultPage->getConfig()->getTitle()->prepend(__(SPConstants::MODULE_TITLE));
        return $resultPage;
    }

    /**
     * Is the user allowed to view the Identity Provider settings.
     * This is based on the ACL set by the admin in the backend.
     * Works in conjugation with acl.xml
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(SPConstants::MODULE_DIR.SPConstants::MODULE_UPGRADE);
    }
}