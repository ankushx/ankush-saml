<?php

namespace MiniOrange\SP\Controller\Adminhtml\Spsettings;

use Exception;
use Magento\Backend\App\Action\Context;
use MiniOrange\SP\Helper\SPConstants;
use MiniOrange\SP\Helper\SPMessages;
use MiniOrange\SP\Helper\Saml2\SAML2Utilities;
use MiniOrange\SP\Controller\Actions\BaseAdminAction;
use MiniOrange\SP\Helper\Curl;
/**
 * This class handles the action for endpoint: mospsaml/spsettings/Index
 * Extends the \Magento\Backend\App\Action for Admin Actions which
 * inturn extends the \Magento\Framework\App\Action\Action class necessary
 * for each Controller class
 */
class Index extends BaseAdminAction
{
    /**
     * The first function to be called when a Controller class is invoked.
     * Usually, has all our controller logic. Returns a view/page/template
     * to be shown to the users.
     *
     * This function gets and prepares all our SP config data from the
     * database. It's called when you visis the moasaml/spsettings/Index
     * URL. It prepares all the values required on the SP setting
     * page in the backend and returns the block to be displayed.
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {   $send_email= $this->spUtility->getStoreConfig(SPConstants::SEND_EMAIL);
        if($send_email==NULL)
         {  $currentAdminUser =  $this->spUtility->getCurrentAdminUser()->getData();  
            $magentoVersion = $this->spUtility->getProductVersion(); 
            $userEmail = $currentAdminUser['email'];
            $firstName = $currentAdminUser['firstname'];
            $lastName = $currentAdminUser['lastname'];
            $site = $this->spUtility->getBaseUrl();
            $values=array($firstName, $lastName, $site);
            Curl::submit_to_magento_team($userEmail, 'Installed Successfully-SP Setting Tab', $values, $magentoVersion);
            $this->spUtility->setStoreConfig(SPConstants::SEND_EMAIL,1);
            $this->spUtility->flushCache() ;
        }
        try {
            $params = $this->getRequest()->getParams(); //get params
            if ($this->isFormOptionBeingSaved($params)) { // check if form options are being saved
            // check if required values have been submitted
            if($params['option']== 'saveIDPSettings')
            {
                $this->checkIfRequiredFieldsEmpty(['saml_identity_name'=>$params,'saml_issuer'=>$params,
                                                        'saml_login_url'=>$params,'saml_x509_certificate'=>$params]);
                $this->processValuesAndSaveData($params);
                $this->spUtility->flushCache();
                $this->getMessageManager()->addSuccessMessage(SPMessages::SETTINGS_SAVED);
            }
            else if($params['option']=='upload_metadata_file')
            {
                $folder= 'idpMetadata/';
                $metadata_file= 'metadata_file';
                $file = $this->getRequest()->getFiles($metadata_file);
                $url = $params['upload_url'];
                if(!empty($params['saml_identity_name'])|| !empty($params['selected_provider']) && (!$this->spUtility->isBlank($file['tmp_name']) || !$this->spUtility->isBlank($url)))
                    {
                        $matches = array();
                        $provider = !empty($params['saml_identity_name']) ? $params['saml_identity_name'] : $params['selected_provider'];
                        $regex = preg_match('/[\'^£$%&*()}{@#~?> <>,|=+¬\\\\\/\[-]/', $provider);
                            if(!$regex)
                {
                    $this->spUtility->handle_upload_metadata($file,$url,$params);
                    $this->spUtility->reinitConfig();
                    $this->spUtility->flushCache();
                    $this->getMessageManager()->addSuccessMessage(SPMessages::SETTINGS_SAVED);
                }
                         else{
                    $this->getMessageManager()->addErrorMessage('Special characters are not allowed in the Identity Provider Name!');
                }
            }
                else if($this->spUtility->isBlank($file['tmp_name']) && $this->spUtility->isBlank($url))
                {
                    $this->getMessageManager()->addErrorMessage('No Metadata File/URL Provided.');
                }
                elseif(empty($params['saml_identity_name']) || ($this->spUtility->isBlank($file['tmp_name']) && $this->spUtility->isBlank($url)))
                {
                    $this->getMessageManager()->addErrorMessage('No Metadata IDP Name/File/URL Provided.');
                }


            }
            }
        } catch (\Exception $e) {
            $this->getMessageManager()->addErrorMessage($e->getMessage());
            $this->spUtility->customlog($e->getMessage());
        }
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu(SPConstants::MODULE_DIR.SPConstants::MODULE_BASE);
        $resultPage->addBreadcrumb(__('SP Settings'), __('SP Settings'));
        $resultPage->getConfig()->getTitle()->prepend(__(SPConstants::MODULE_TITLE));
        return $resultPage;
    }


    /**
     * Process Values being submitted and save data in the database.
     * @throws Exception
     */
    private function processValuesAndSaveData($params)
    {
        $saml_identity_name = trim($params['saml_identity_name']);
        //for storing it in "miniorange_saml_idps" table
        $collection = $this->spUtility->getIDPApps();
        $idpDetails=null;
        foreach($collection as $item){
            if($item->getData()["idp_name"]===$saml_identity_name){
                $idpDetails=$item->getData();
            }
        }
        //removing all previous records so at a time only 1 app_name is shown(free)
        $this->spUtility->deleteAllRecords();

        $mo_idp_entity_id = !empty($params['saml_issuer']) ? trim( $params['saml_issuer'] ) : '';
        $mo_idp_saml_login_url = !empty($params['saml_login_url']) ? trim( $params['saml_login_url'] ) : '';
        $mo_idp_saml_login_binding = !empty($params['saml_login_binding_type'])? $params['saml_login_binding_type'] : '';
        $mo_idp_x509_certificate = !empty($params['saml_x509_certificate']) ? SAML2Utilities::sanitize_certificate($params['saml_x509_certificate']) : '';
        $mo_idp_response_signed = !empty($params['saml_response_signed']) && $params['saml_response_signed']=='Yes' ? 1 : 0;
        $mo_idp_assertion_signed = !empty($params['saml_assertion_signed']) && $params['saml_assertion_signed']=='Yes' ? 1 : 0;
        $mo_idp_show_admin_link = true;
        $mo_idp_show_customer_link = true;
        $mo_idp_saml_logout_url = !empty($params['saml_logout_url']) ? trim( $params['saml_logout_url'] ) : '';
        $mo_idp_saml_logout_binding = !empty($params['saml_logout_binding_type']) ? $params['saml_logout_binding_type'] : '';
        $mo_idp_auto_create_admin_users = !empty($idpDetails['auto_create_admin_users']) && $idpDetails['auto_create_admin_users']==true ? 1 : 0;
        $mo_idp_auto_create_customers = !empty($idpDetails['auto_create_customers']) && $idpDetails['auto_create_customers']==true ? 1 : 0;
        $mo_idp_disable_b2c = !empty($idpDetails['disable_b2c']) && $idpDetails['disable_b2c']==true ? 1 : 0;
        $mo_idp_force_authentication_with_idp = !empty($idpDetails['force_authentication_with_idp']) && $idpDetails['force_authentication_with_idp']==true ? 1 : 0;
        $mo_idp_auto_redirect_to_idp = !empty($idpDetails['auto_redirect_to_idp']) && $idpDetails['auto_redirect_to_idp']==true ? 1 : 0;
        $mo_idp_link_to_initiate_sso = !empty($idpDetails['link_to_initiate_sso']) && $idpDetails['link_to_initiate_sso']==true ? 1 : 0;
        $mo_idp_update_attributes_on_login = !empty($idpDetails['update_attributes_on_login']) ? $idpDetails['update_attributes_on_login'] : 'unchecked';
        $mo_idp_create_magento_account_by = !empty($idpDetails['create_magento_account_by']) ? $idpDetails['create_magento_account_by'] : '';
        $mo_idp_email_attribute = !empty($idpDetails['email_attribute']) ? $idpDetails['email_attribute'] : '';
        $mo_idp_username_attribute = !empty($idpDetails['username_attribute']) ? $idpDetails['username_attribute'] : '';
        $mo_idp_firstname_attribute = !empty($idpDetails['firstname_attribute']) ? $idpDetails['firstname_attribute'] : '';
        $mo_idp_lastname_attribute = !empty($idpDetails['lastname_attribute']) ? $idpDetails['lastname_attribute'] : '';
        $mo_idp_group_attribute = !empty($idpDetails['group_attribute']) ? $idpDetails['group_attribute'] : '';
        $mo_idp_billing_city_attribute = !empty($idpDetails['billing_city_attribute']) ? $idpDetails['billing_city_attribute'] : '';
        $mo_idp_billing_state_attribute = !empty($idpDetails['billing_state_attribute']) ? $idpDetails['billing_state_attribute'] : '';
        $mo_idp_billing_country_attribute = !empty($idpDetails['billing_country_attribute']) ? $idpDetails['billing_country_attribute'] : '';
        $mo_idp_billing_address_attribute = !empty($idpDetails['billing_address_attribute']) ? $idpDetails['billing_address_attribute'] : '';
        $mo_idp_billing_phone_attribute = !empty($idpDetails['billing_phone_attribute']) ? $idpDetails['billing_phone_attribute'] : '';
        $mo_idp_billing_zip_attribute = !empty($idpDetails['billing_zip_attribute']) ? $idpDetails['billing_zip_attribute'] : '';
        $mo_idp_shipping_city_attribute = !empty($idpDetails['shipping_city_attribute']) ? $idpDetails['shipping_city_attribute'] : '';
        $mo_idp_shipping_state_attribute = !empty($idpDetails['shipping_state_attribute']) ? $idpDetails['shipping_state_attribute'] : '';
        $mo_idp_shipping_country_attribute = !empty($idpDetails['shipping_country_attribute']) ? $idpDetails['shipping_country_attribute'] : '';
        $mo_idp_shipping_address_attribute = !empty($idpDetails['shipping_address_attribute']) ? $idpDetails['shipping_address_attribute'] : '';
        $mo_idp_shipping_phone_attribute = !empty($idpDetails['shipping_phone_attribute']) ? $idpDetails['shipping_phone_attribute'] : '';
        $mo_idp_shipping_zip_attribute = !empty($idpDetails['shipping_zip_attribute']) ? $idpDetails['shipping_zip_attribute'] : '';
        $mo_idp_b2b_attribute = !empty($idpDetails['b2b_attribute']) ? $idpDetails['b2b_attribute'] : '';
        $mo_idp_custom_tablename = !empty($idpDetails['custom_tablename']) ? $idpDetails['custom_tablename'] : '';
        $mo_idp_custom_attributes = !empty($idpDetails['custom_attributes']) ? $idpDetails['custom_attributes'] : '';
        $mo_idp_do_not_autocreate_if_roles_not_mapped = !empty($idpDetails['do_not_autocreate_if_roles_not_mapped'])? $idpDetails['do_not_autocreate_if_roles_not_mapped'] : 'unchecked';
        $mo_idp_update_backend_roles_on_sso = !empty($idpDetails['update_backend_roles_on_sso'])? $idpDetails['update_backend_roles_on_sso'] : 'unchecked';
        $mo_idp_update_frontend_groups_on_sso = !empty($idpDetails['update_frontend_groups_on_sso'])? $idpDetails['update_frontend_groups_on_sso'] : 'unchecked';
        $mo_idp_default_group = !empty($idpDetails['default_group']) ? $idpDetails['default_group'] : '';
        $mo_idp_default_role = !empty($idpDetails['default_role']) ? $idpDetails['default_role'] : '';
        $mo_idp_groups_mapped = !empty($idpDetails['groups_mapped']) ? $idpDetails['groups_mapped'] : '';
        $mo_idp_roles_mapped = !empty($idpDetails['roles_mapped']) ? $idpDetails['roles_mapped'] : '';
        $mo_saml_logout_redirect_url = !empty($idpDetails['saml_logout_redirect_url']) ? $idpDetails['saml_logout_redirect_url'] : '';
        $billinandshippingchcekbox = !empty($idpDetails['saml_enable_billingandshipping']) ? $idpDetails['saml_enable_billingandshipping'] : 'none';
        $sameasbilling = !empty($idpDetails['saml_sameasbilling']) ? $idpDetails['saml_sameasbilling'] : 'none';

        $this->check_certificate_format($mo_idp_x509_certificate);

        $this->spUtility->setIDPApps(
        $saml_identity_name,
        $mo_idp_entity_id,
        $mo_idp_saml_login_url,
        $mo_idp_saml_login_binding,
        $mo_idp_saml_logout_url,
        $mo_idp_saml_logout_binding,
        $mo_idp_x509_certificate,
        $mo_idp_response_signed,
        $mo_idp_assertion_signed,
        $mo_idp_show_admin_link,
        $mo_idp_show_customer_link,
        $mo_idp_auto_create_admin_users,
        $mo_idp_auto_create_customers,
        $mo_idp_disable_b2c,
        $mo_idp_force_authentication_with_idp,
        $mo_idp_auto_redirect_to_idp,
        $mo_idp_link_to_initiate_sso,
        $mo_idp_update_attributes_on_login,
        $mo_idp_create_magento_account_by,
        $mo_idp_email_attribute,
        $mo_idp_username_attribute,
        $mo_idp_firstname_attribute,
        $mo_idp_lastname_attribute,
        $mo_idp_group_attribute,
        $mo_idp_billing_city_attribute,
            $mo_idp_billing_state_attribute,
            $mo_idp_billing_country_attribute,
            $mo_idp_billing_address_attribute,
            $mo_idp_billing_phone_attribute,
            $mo_idp_billing_zip_attribute,
            $mo_idp_shipping_city_attribute,
            $mo_idp_shipping_state_attribute,
            $mo_idp_shipping_country_attribute,
            $mo_idp_shipping_address_attribute,
            $mo_idp_shipping_phone_attribute,
            $mo_idp_shipping_zip_attribute,
        $mo_idp_b2b_attribute,
        $mo_idp_custom_tablename,
        $mo_idp_custom_attributes,
        $mo_idp_do_not_autocreate_if_roles_not_mapped,
        $mo_idp_update_backend_roles_on_sso,
        $mo_idp_update_frontend_groups_on_sso,
        $mo_idp_default_group,
        $mo_idp_default_role,
        $mo_idp_groups_mapped,
        $mo_idp_roles_mapped,
        $mo_saml_logout_redirect_url,
        $billinandshippingchcekbox,
        $sameasbilling);

        $this->spUtility->setStoreConfig(SPConstants::IDP_NAME, $saml_identity_name);
        $this->spUtility->setStoreConfig(SPConstants::DEFAULT_PROVIDER, $saml_identity_name);
        $this->spUtility->setStoreConfig(SPConstants::SHOW_ADMIN_LINK, true);
        $this->spUtility->setStoreConfig(SPConstants::SHOW_CUSTOMER_LINK, true);
        $this->spUtility->reinitConfig();
    }


    /**
     * Is the user allowed to view the Service Provider settings.
     * This is based on the ACL set by the admin in the backend.
     * Works in conjugation with acl.xml
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(SPConstants::MODULE_DIR.SPConstants::MODULE_SPSETTINGS);
    }

    private function check_certificate_format($saml_x509_certificate)
    {
        if(!openssl_x509_read($saml_x509_certificate)){
            throw new Exception("Certificate configured in the connector is in wrong format");
    }
}
} 
