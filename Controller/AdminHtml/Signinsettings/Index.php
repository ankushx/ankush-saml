<?php

namespace MiniOrange\SP\Controller\Adminhtml\Signinsettings;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use MiniOrange\SP\Helper\SPConstants;
use MiniOrange\SP\Helper\SPMessages;
use MiniOrange\SP\Helper\Saml2\SAML2Utilities;
use MiniOrange\SP\Controller\Actions\BaseAdminAction;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use MiniOrange\SP\Helper\Curl;
/**
 * This class handles the action for endpoint: mospsaml/signinsettings/Index
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
     * This function gets and prepares all our SP config data from the
     * database. It's called when you visis the moasaml/signinsettings/Index
     * URL. It prepares all the values required on the SP setting
     * page in the backend and returns the block to be displayed.
     *
     * @return Page
     */

    protected $fileFactory;
   

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \MiniOrange\SP\Helper\SPUtility $spUtility,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory

    ) {
        //You can use dependency injection to get any class this observer may need. 
        parent::__construct($context,$resultPageFactory,$spUtility);
        $this->fileFactory = $fileFactory;

    }
    public function execute()
    {        $send_email= $this->spUtility->getStoreConfig(SPConstants::SEND_EMAIL);
        
        if($send_email==NULL)
         {  $currentAdminUser =  $this->spUtility->getCurrentAdminUser()->getData();  
            $magentoVersion = $this->spUtility->getProductVersion(); 
            $userEmail = $currentAdminUser['email'];
            $firstName = $currentAdminUser['firstname'];
            $lastName = $currentAdminUser['lastname'];
            $site = $this->spUtility->getBaseUrl();
            $values=array($firstName, $lastName, $site);
            Curl::submit_to_magento_team($userEmail, 'Installed Successfully-Sign in Setting Tab', $values, $magentoVersion);
            $this->spUtility->setStoreConfig(SPConstants::SEND_EMAIL,1);
            $this->spUtility->flushCache() ;
        }

        try {
            $params = $this->getRequest()->getParams(); //get params

            // check if form options are being saved
            if ($this->isFormOptionBeingSaved($params)) {
                if($params['option']=='saveSingInSettings')
                { 
                $this->processValuesAndSaveData($params);
                $this->spUtility->flushCache();
                $this->getMessageManager()->addSuccessMessage(SPMessages::SETTINGS_SAVED);
             
                }elseif($params['option']=='enable_debug_log') {
                    $debug_log_on = isset($params['debug_log_on']) ? 1 : 0;
                    $log_file_time = time();
                    $this->spUtility->setStoreConfig(SPConstants::ENABLE_DEBUG_LOG, $debug_log_on);
                    $this->spUtility->flushCache();
                    $this->getMessageManager()->addSuccessMessage(SPMessages::SETTINGS_SAVED);
                    $this->spUtility->reinitConfig();
                    if($debug_log_on == '1')
                    {
                    $this->spUtility->setStoreConfig(SPConstants::LOG_FILE_TIME,  $log_file_time);
                    }elseif($debug_log_on == '0' && $this->spUtility->isCustomLogExist()){
                        $this->spUtility->setStoreConfig(SPConstants::LOG_FILE_TIME, NULL);
                        $this->spUtility->deleteCustomLogFile();
                    }
                }elseif($params['option']=='clear_download_logs'){
                    if(isset($params['download_logs'])) {
                    $fileName = "mo_saml.log"; // add your file name here
                    if ($fileName) {
                        $filePath = '../var/log/' . $fileName;
                        $content['type'] = 'filename';// type has to be "filename"
                        $content['value'] = $filePath; // path where file place
                        $content['rm'] = 0; // if you add 1 then it will be delete from server after being download, otherwise add 0.
                       
                        if($this->spUtility->isLogEnable())
                        { 
                            //Customer Configuration settings.
                             $idpName = $this->spUtility->getStoreConfig(SPConstants::IDP_NAME);
                                $collection = $this->spUtility->getidpApps();    
                            $idpDetails=null;    
                            foreach($collection as $item){  
                            if($item->getData()["idp_name"]===$idpName){   
                                $idpDetails=$item->getData();    
                            }   
                            }
                             $binding_type = !empty($idpDetails['saml_login_binding']) ? $idpDetails['saml_login_binding']: '';
                             $saml_sso_url = !empty($idpDetails['saml_login_url']) ? $idpDetails['saml_login_url'] : '';
                             $issuer = !empty($idpDetails['idp_entity_id']) ? $idpDetails['idp_entity_id'] : '';
                             $certificate = !empty($idpDetails['x509_certificate']) ? SAML2Utilities::sanitize_certificate($idpDetails['x509_certificate']) : '';
                             $response_signed = !empty($idpDetails['response_signed']) ? $idpDetails['response_signed'] : 0;
                             $assertion_signed = !empty($idpDetails['assertion_signed']) ? $idpDetails['assertion_signed'] : 0;
                             $show_customer_link = !empty($idpDetails['show_customer_link']) && $idpDetails['show_customer_link']==true ? 1 : 0;
                             $customer_email = $this->spUtility->getStoreConfig(SPConstants::DEFAULT_MAP_EMAIL);
                             $plugin_version = SPConstants::VERSION;
                             $magento_version =  $this->spUtility->getProductVersion(); 
                             $php_version =phpversion();
                             $values = array($idpName,$binding_type, $saml_sso_url,$issuer, $certificate,$response_signed,$assertion_signed, $show_customer_link,$customer_email,$plugin_version,$magento_version,$php_version);
                           
                            //save configuration
                            $this->customerConfigurationSettings($values);
                            
                        }
                       
                     if($this->spUtility->isCustomLogExist() && $this->spUtility->isLogEnable())
                     {              
                        return $this->fileFactory->create($fileName, $content, DirectoryList::VAR_DIR);
                     }

                   else
                   {
                    $this->getMessageManager()->addErrorMessage('Please Enable Debug Log Setting First');

                   }
                    } else {
                        $this->getMessageManager()->addErrorMessage('Something went wrong');

                    }
                }
                     elseif(isset($params['clear_logs'])){
                        if($this->spUtility->isCustomLogExist()){
                            $this->spUtility->setStoreConfig(SPConstants::LOG_FILE_TIME, NULL);
                            $this->spUtility->deleteCustomLogFile();
                            $this->getMessageManager()->addSuccessMessage('Logs Cleared Successfully');
                        }else{
                            $this->getMessageManager()->addSuccessMessage('Logs Have Already Been Removed');
                        }
    
                    }

                }

            }
        } catch (\Exception $e) {
            $this->getMessageManager()->addErrorMessage($e->getMessage());
            $this->spUtility->customlog($e->getMessage());
        }
        // generate page
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu(SPConstants::MODULE_DIR.SPConstants::MODULE_BASE);
        $resultPage->addBreadcrumb(__('Sign In Settings'), __('Sign In Settings'));
        $resultPage->getConfig()->getTitle()->prepend(__(SPConstants::MODULE_TITLE));
        return $resultPage;
    }


    /**
     * Process Values being submitted and save data in the database.
     */
    private function processValuesAndSaveData($params)
    {
        $mo_saml_show_customer_link = isset($params['mo_saml_show_customer_link']) ? 1 : 0;
        $this->spUtility->setStoreConfig(SPConstants::SHOW_CUSTOMER_LINK, $mo_saml_show_customer_link);
        $this->spUtility->reinitConfig();
    }

    private function customerConfigurationSettings( $values)
    {   $this->spUtility->customlog("......................................................................");
        $this->spUtility->customlog("Plugin: SAML Free : ".$values[9]);
        $this->spUtility->customlog("Plugin: Magento version : ".$values[10]." ; Php version: ".$values[11]);
        $this->spUtility->customlog("IDPname: ".$values[0]);
        $this->spUtility->customlog("Binding_Type: ".$values[1]);
        $this->spUtility->customlog("Saml_SSO_Url: ".$values[2]);
        $this->spUtility->customlog("Issuer: ".$values[3]);
        $this->spUtility->customlog("Certificate: ".$values[4]);
        $this->spUtility->customlog("Response_Signed: ".$values[5]);
        $this->spUtility->customlog("Assertion_Signed: ".$values[6]);
        $this->spUtility->customlog("Show_customer_link: ".$values[7]);
        $this->spUtility->customlog("Customer_email: ".$values[8]);
        $this->spUtility->customlog("......................................................................");
    }
    /**
     * Is the user allowed to view the Sign in Settings.
     * This is based on the ACL set by the admin in the backend.
     * Works in conjugation with acl.xml
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(SPConstants::MODULE_DIR.SPConstants::MODULE_SIGNIN);
    }
}
