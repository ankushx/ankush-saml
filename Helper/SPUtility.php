<?php

namespace MiniOrange\SP\Helper;

use MiniOrange\SP\Helper\SPConstants;
use MiniOrange\SP\Helper\Curl;
use MiniOrange\SP\Helper\Data;
use DOMDocument;
use MiniOrange\SP\Helper\Exception\InvalidOperationException;
use MiniOrange\SP\Helper\Saml2\SAML2Utilities;
use MiniOrange\SP\Helper\IdentityProviders;
use \Magento\Framework\App\ProductMetadataInterface;
use MiniOrange\SP\Model\MiniorangeSamlIDPsFactory;

/**
 * This class contains some common Utility functions
 * which can be called from anywhere in the module. This is
 * mostly used in the action classes to get any utility
 * function or data from the database.
 */
class SPUtility extends Data
{
    protected $customerSession;
    protected $authSession;
    protected $cacheTypeList;
    protected $cacheFrontendPool;
    protected $fileSystem;
    protected $logger;
    protected $reinitableConfig;
    protected $_logger;
    protected $productMetadata;


    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Backend\Helper\Data $helperBackend,
        \Magento\Framework\Url $frontendUrl,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool,
        \Magento\Framework\Filesystem\Driver\File $fileSystem,
        \Magento\Framework\App\Config\ReinitableConfigInterface $reinitableConfig,
        \MiniOrange\SP\Logger\Logger $logger2,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        MiniorangeSamlIDPsFactory $miniorangeSamlIDPsFactory,
    ) {
        $this->customerSession = $customerSession;
        $this->authSession = $authSession;
        $this->cacheTypeList = $cacheTypeList;
        $this->logger = $logger;
        $this->cacheFrontendPool = $cacheFrontendPool;
        $this->fileSystem = $fileSystem;
        $this->reinitableConfig = $reinitableConfig;
        $this->helperBackend= $helperBackend;
        $this->_logger = $logger2;
        $this->productMetadata = $productMetadata; 
           parent::__construct(
               $scopeConfig,
               $customerFactory,
               $urlInterface,
               $configWriter,
               $assetRepo,
               $helperBackend,
               $frontendUrl,
               $miniorangeSamlIDPsFactory
           );
    }

    /**
     * This function returns phone number as a obfuscated
     * string which can be used to show as a message to the user.
     *
     * @param $phone references the phone number.
     */
    public function getHiddenPhone($phone)
    {
        $hidden_phone = 'xxxxxxx' . substr($phone, strlen($phone) - 3);
        return $hidden_phone;
    }
    

    /**
     * This function checks if a value is set or
     * empty. Returns true if value is empty
     *
     * @return True or False
     * @param $value references the variable passed.
     */
    public function isBlank($value)
    {
        if (! isset($value) || empty($value)) {
            return true;
        }
        return false;
    }
    
 //CUSTOM LOG FILE OPERATION
    /**
     * This function print custom log in var/mo_saml.log file.
     */
    public function customlog($txt)
    {   
        $this->isLogEnable() ? $this->_logger->debug($txt): NULL; 
    }
       /**
    * This function check whether any custom log file exist or not. 
     */ 
    public function isCustomLogExist()
    {   if($this->fileSystem->isExists("../var/log/mo_saml.log")){
        return 1;
    }elseif($this->fileSystem->isExists("var/log/mo_saml.log")){
        return 1;
    }
      return 0; 
    }

    public function deleteCustomLogFile()
    {if($this->fileSystem->isExists("../var/log/mo_saml.log")){
        $this->fileSystem->deleteFile("../var/log/mo_saml.log");
    }elseif($this->fileSystem->isExists("var/log/mo_saml.log")){
        $this->fileSystem->deleteFile("var/log/mo_saml.log");
    }
}

public function getProductVersion(){
    return  $this->productMetadata->getVersion(); 
}
public function isLogEnable()
{
    return $this->getStoreConfig(SPConstants::ENABLE_DEBUG_LOG);
}
    /**
     * This function checks if cURL has been installed
     * or enabled on the site.
     *
     * @return True or False
     */
    public function isCurlInstalled()
    {
        if (in_array('curl', get_loaded_extensions())) {
            return 1;
        } else {
            return 0;
        }
    }


    /**
     * This function checks if the phone number is in the correct format or not.
     *
     * @param $phone refers to the phone number entered
     */
    public function validatePhoneNumber($phone)
    {
        if (!preg_match(MoIDPConstants::PATTERN_PHONE, $phone, $matches)) {
            return false;
        } else {
            return true;
        }
    }


    /**
     * This function is used to obfuscate and return
     * the email in question.
     *
     * @param $email refers to the email id to be obfuscated
     * @return obfuscated email id.
     */
    public function getHiddenEmail($email)
    {
        if (!isset($email) || trim($email)==='') {
            return "";
        }

        $emailsize = strlen($email);
        $partialemail = substr($email, 0, 1);
        $temp = strrpos($email, "@");
        $endemail = substr($email, $temp-1, $emailsize);
        for ($i=1; $i<$temp; $i++) {
            $partialemail = $partialemail . 'x';
        }

        $hiddenemail = $partialemail . $endemail;
               
        return $hiddenemail;
    }


    /**
     * set customer Session Data
     *
     * @param $key
     * @param $value
     */
    public function setSessionData($key, $value)
    {
        return $this->customerSession->setData($key, $value);
    }
    

    /**
     * Get customer Session data based off on the key
     *
     * @param $key
     * @param $remove
     */
    public function getSessionData($key, $remove = false)
    {
        return $this->customerSession->getData($key, $remove);
    }

    /**
     * Check if the admin has configured the plugin with
     * the Identity Provier. Returns true or false
     */
    public function isSPConfigured()
    {
        $loginUrl = $this->getStoreConfig(SPConstants::SAML_SSO_URL);
        return $this->isBlank($loginUrl) ? false : true;
    }


    /**
     * This function is used to check if customer has completed
     * the registration process. Returns TRUE or FALSE. Checks
     * for the email and customerkey in the database are set
     * or not.
     */
    public function micr()
    {
        $email = $this->getStoreConfig(SPConstants::SAMLSP_EMAIL);
        $key = $this->getStoreConfig(SPConstants::SAMLSP_KEY);
        return !$this->isBlank($email) && !$this->isBlank($key) ? true : false;
    }


    /**
     * Check if there's an active session of the user
     * for the frontend or the backend. Returns TRUE
     * or FALSE
     */
    public function isUserLoggedIn()
    {
        return $this->customerSession->isLoggedIn()
                || $this->authSession->isLoggedIn();
    }

    /**
     * Get the Current Admin User who is logged in
     */
    public function getCurrentAdminUser()
    {
        return $this->authSession->getUser();
    }


    /**
     * Get the Current Admin User who is logged in
     */
    public function getCurrentUser()
    {
        return $this->customerSession->getCustomer();
    }

    /**
     * Get the customer login url
     */
    public function getCustomerLoginUrl()
    {
        return $this->getUrl('customer/account/login');
    }

    /**
     * Desanitize the cert
     */
    public function desanitizeCert($cert)
    {
        return SAML2Utilities::desanitize_certificate($cert);
    }


    /**
     * Sanitize the cert
     */
    public function sanitizeCert($cert)
    {
        return SAML2Utilities::sanitize_certificate($cert);
    }


    /**
     * Flush Magento Cache. This has been added to make
     * sure the admin/user has a smooth experience and
     * doesn't have to flush his cache over and over again
     * to see his changes.
     */
    public function flushCache()
    {
        $types = ['db_ddl']; // we just need to clear the database cache
        foreach ($types as $type) {
            $this->cacheTypeList->cleanType($type);
        }
    
        foreach ($this->cacheFrontendPool as $cacheFrontend) {
            $cacheFrontend->getBackend()->clean();
        }
    }


    /**
     * Get data in the file specified by the path
     */
    public function getFileContents($file)
    {
        return $this->fileSystem->fileGetContents($file);
    }

    
    /**
     * Put data in the file specified by the path
     */
    public function putFileContents($file, $data)
    {
        $this->fileSystem->filePutContents($file, $data);
    }


    /** Get the Current User's logout url */
    public function getLogoutUrl()
    {
        if ($this->customerSession->isLoggedIn()) {
            return $this->getUrl('customer/account/logout');
        }
        if ($this->authSession->isLoggedIn()) {
            return $this->getAdminUrl('adminhtml/auth/logout');
        }
        return '/';
    }

    public function reinitConfig(){
        $this->reinitableConfig->reinit();
    }

    //---------------Handle upload Metadata--------------

    public function handle_upload_metadata($file,$url,$params) {
        if (isset($file) || isset($url)) {
            if (!empty($file['tmp_name'])) {
                $file = file_get_contents($file['tmp_name']);
            } else {
                $url = filter_var($url, FILTER_SANITIZE_URL);
                $arrContextOptions = array(
                    "ssl" => array(
                        "verify_peer" => false,
                        "verify_peer_name" => false,
                    ),
                );
                if (empty($url)) {
                    return;
                } else {
                    $file = file_get_contents($url, false, stream_context_create($arrContextOptions));
                }
            }
            $this->upload_metadata($file,$params);
        } 
	}

    public function upload_metadata($file,$params) {
		$document = new DOMDocument();
        $document->loadXML($file);
        restore_error_handler();
        $first_child = $document->firstChild;
        if (!empty($first_child)) {
            $metadata = new IDPMetadataReader($document);
            $identity_providers = $metadata->getIdentityProviders();
            if (empty($identity_providers)) {
                return;
            }
            foreach ($identity_providers as $key => $idp) {
                $saml_login_url = $idp->getLoginURL('HTTP-Redirect');
                $saml_issuer = $idp->getEntityID();
                $saml_x509_certificate = $idp->getSigningCertificate();
                $database_name = 'saml';
                $updatefieldsarray = array(
                    'samlIssuer' => isset($saml_issuer) ? $saml_issuer : 0,
                    'ssourl' => isset($saml_login_url) ? $saml_login_url : 0,
                    'loginBindingType' => 'HttpRedirect',
                    'certificate' => isset($saml_x509_certificate) ? $saml_x509_certificate[0] : 0,
                );

                $this->generic_update_query($database_name, $updatefieldsarray,$params);
                break;
            }
            return;
        } else {
            
            return;
        }
	}

    public function generic_update_query($database_name, $updatefieldsarray,$params){
        error_log("In BesamlController: generic_update_query()");
        $idp_obj = json_encode($updatefieldsarray);

        if(empty($params['saml_identity_name']))
        $params['saml_identity_name'] = $params['selected_provider'];
        $mo_idp_app_name = trim( $params['saml_identity_name'] );
        $collection = $this->getIDPApps();    
        $idpDetails=null;    
        foreach($collection as $item){
            if($item->getData()["idp_name"]===$mo_idp_app_name){   
                $idpDetails=$item->getData();    
            }   
        }

         foreach ($updatefieldsarray as $key => $value)
        {
			if($key=='samlIssuer')
            $mo_idp_entity_id = trim( $value );
            if($key=='ssourl')
            $mo_idp_saml_login_url = trim( $value ); 
            if($key=='loginBindingType')
            $mo_idp_saml_login_binding = trim( $value );
            if($key=='certificate')
            $mo_idp_x509_certificate = SAML2Utilities::sanitize_certificate(trim( $value ));
        }
        $mo_idp_saml_logout_url = !empty($params['saml_logout_url']) ? trim( $params['saml_logout_url'] ) : '';
        $mo_idp_saml_logout_binding = 'HttpRedirect';
        $mo_idp_response_signed = !empty($params['saml_response_signed']) && $params['saml_response_signed']=='Yes' ? 1 : 0;
        $mo_idp_assertion_signed = !empty($params['saml_assertion_signed']) && $params['saml_assertion_signed']=='Yes' ? 1 : 0;
        $mo_idp_show_admin_link = true;
        $mo_idp_show_customer_link = true;
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
        $mo_idp_do_not_autocreate_if_roles_not_mapped = !empty($idpDetails['do_not_autocreate_if_roles_not_mapped']) && $idpDetails['do_not_autocreate_if_roles_not_mapped']==true ? 1 : 0;
        $mo_idp_update_backend_roles_on_sso = !empty($idpDetails['update_backend_roles_on_sso']) && $idpDetails['update_backend_roles_on_sso']==true ? 1 : 0;
        $mo_idp_update_frontend_groups_on_sso = !empty($idpDetails['update_frontend_groups_on_sso']) && $idpDetails['update_frontend_groups_on_sso']==true ? 1 : 0;
        $mo_idp_default_group = !empty($idpDetails['default_group']) ? $idpDetails['default_group'] : '';
        $mo_idp_default_role = !empty($idpDetails['default_role']) ? $idpDetails['default_role'] : '';
        $mo_idp_groups_mapped = !empty($idpDetails['groups_mapped']) ? $idpDetails['groups_mapped'] : '';
        $mo_idp_roles_mapped = !empty($idpDetails['roles_mapped']) ? $idpDetails['roles_mapped'] : '';
        $mo_saml_logout_redirect_url = !empty($idpDetails['saml_logout_redirect_url']) ? $idpDetails['saml_logout_redirect_url'] : '';
        $billinandshippingchcekbox = !empty($idpDetails['saml_enable_billingandshipping']) ? $idpDetails['saml_enable_billingandshipping'] : 'none';
        $sameasbilling = !empty($idpDetails['saml_sameasbilling']) ? $idpDetails['saml_sameasbilling'] : 'none';


        $this->deleteAllRecords();

        if(!empty($mo_idp_app_name))
        $this->setIDPApps(
        $mo_idp_app_name,
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
        $this->setStoreConfig(SPConstants::IDP_NAME, $mo_idp_app_name);
        $this->setStoreConfig(SPConstants::DEFAULT_PROVIDER, $mo_idp_app_name);
        $this->setStoreConfig(SPConstants::SHOW_CUSTOMER_LINK, true);
		$this->reinitConfig();

    }

     /**
     * Get idp details
     * used in ShowTestResultsAction.php
     */
    public function getClientDetails()
    {
        $saml_identity_name = $this->getStoreConfig(SPConstants::IDP_NAME); 
         $saml_customer_link = $this->getStoreConfig(SPConstants::SHOW_CUSTOMER_LINK);
         
         $collection = $this->getIDPApps();    
         $idpDetails=null;   
         foreach($collection as $item){  
             if($item->getData()["idp_name"]=== $saml_identity_name){   
                 $idpDetails=$item->getData();    
                }   
            }
            $saml_issuer = $idpDetails['idp_entity_id'];
            $saml_login_binding_type =$idpDetails['saml_login_binding'];
            $saml_login_url = $idpDetails['saml_login_url']; 
            $saml_x509_certificate = $idpDetails['x509_certificate'];    
            $saml_assertion_signed = $idpDetails['assertion_signed'];     
            $saml_response_signed = $idpDetails['response_signed'];

        return array( $saml_identity_name, $saml_login_binding_type,$saml_login_url,$saml_issuer, $saml_x509_certificate, $saml_customer_link,$saml_response_signed, $saml_assertion_signed);
    }

}
