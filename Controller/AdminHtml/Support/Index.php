<?php

namespace MiniOrange\SP\Controller\Adminhtml\Support;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use MiniOrange\SP\Helper\SPConstants;
use MiniOrange\SP\Helper\SPMessages;
use MiniOrange\SP\Helper\Curl;
use MiniOrange\SP\Helper\Saml2\SAML2Utilities;
use MiniOrange\SP\Controller\Actions\BaseAdminAction;

/**
 * This class handles the action for endpoint: mospsaml/support/Index
 * Extends the \Magento\Backend\App\Action for Admin Actions which
 * inturn extends the \Magento\Framework\App\Action\Action class necessary
 * for each Controller class
 *
 * This class handles processing and sending or support request
 */
class Index extends BaseAdminAction
{
    /**
     * The first function to be called when a Controller class is invoked.
     * Usually, has all our controller logic. Returns a view/page/template
     * to be shown to the users.
     *
     * This function gets and prepares all our SP config data from the
     * database. It's called when you visis the moasaml/metadata/Index
     * URL. It prepares all the values required on the SP setting
     * page in the backend and returns the block to be displayed.
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {   $send_email= $this->spUtility->getStoreConfig(SPConstants::SEND_EMAIL);
        
        if($send_email==NULL)
         {  $currentAdminUser =  $this->spUtility->getCurrentAdminUser()->getData();  
            $userEmail = $currentAdminUser['email'];
            $firstName = $currentAdminUser['firstname'];
            $lastName = $currentAdminUser['lastname'];
            $site = $this->spUtility->getBaseUrl();
            $values=array($firstName, $lastName, $site);
            Curl::submit_to_magento_team($userEmail, 'Installed Successfully-Support Tab', $values);
            $this->spUtility->setStoreConfig(SPConstants::SEND_EMAIL,1);
            $this->spUtility->flushCache() ;
        }
        try {
            $params = $this->getRequest()->getParams(); //get params
            if ($this->isFormOptionBeingSaved($params)) {
                $this->checkIfSupportQueryFieldsEmpty(['email'=>$params,'query'=>$params]);
                $email = $params['email'];
                $phone = $params['phone'];
                $query = $params['query'];
                $companyName = $this->spUtility->getBaseUrl();
               Curl::submit_contact_us(
                   $email,
                   $phone,
                   $query
               );
                error_log("after contact us fun");
                $this->getMessageManager()->addSuccessMessage(SPMessages::QUERY_SENT);
            }
        } catch (\Exception $e) {
            $this->getMessageManager()->addErrorMessage($e->getMessage());
            $this->spUtility->customlog($e->getMessage());
        }

         $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);        

        
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        
        return $resultRedirect;
       }


    /**
     * Is the user allowed to view the Support settings.
     * This is based on the ACL set by the admin in the backend.
     * Works in conjugation with acl.xml
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(SPConstants::MODULE_DIR.SPConstants::MODULE_SUPPORT);
    }
}
