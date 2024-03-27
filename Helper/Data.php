<?php

namespace MiniOrange\SP\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;
use MiniOrange\SP\Helper\SPConstants;
use MiniOrange\SP\Model\MiniorangeSamlIDPsFactory;

/**
 * This class contains functions to get and set the required data
 * from Magento database or session table/file or generate some
 * necessary values to be used in our module.
 */
class Data extends AbstractHelper
{

    protected $scopeConfig;
    protected $customerFactory;
    protected $urlInterface;
    protected $configWriter;
    protected $assetRepo;
    protected $helperBackend;
    protected $frontendUrl;
    protected $miniorangeSamlIDPsFactory;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Backend\Helper\Data $helperBackend,
        \Magento\Framework\Url $frontendUrl,
        MiniorangeSamlIDPsFactory $miniorangeSamlIDPsFactory
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->customerFactory = $customerFactory;
        $this->urlInterface = $urlInterface;
        $this->configWriter = $configWriter;
        $this->assetRepo = $assetRepo;
        $this->helperBackend = $helperBackend;
        $this->frontendUrl = $frontendUrl;
        $this->miniorangeSamlIDPsFactory = $miniorangeSamlIDPsFactory;
    }


    /**
     * Get base url of miniorange
     */
    public function getMiniOrangeUrl()
    {
        return SPConstants::HOSTNAME;
    }

    /**
     * Function to extract data stored in the store config table.
     *
     * @param $config
     */
    public function getStoreConfig($config)
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue('miniorange/samlsp/' . $config, $storeScope);
    }


    /**
     * Function to store data stored in the store config table.
     *
     * @param $config
     * @param $value
     */
    public function setStoreConfig($config, $value)
    {
        $this->configWriter->save('miniorange/samlsp/' . $config, $value);
    }
    

    public function getIdpGuideBaseUrl($idp)
    {
        $url = 'https://plugins.miniorange.com/'.$idp;
        return $url;
    }

    /**
     * This function is used to save user attributes to the
     * database and save it. Mostly used in the SSO flow to
     * update user attributes. Decides which user to update.
     *
     * @param $url
     * @param $value
     * @param $id
     * @param $admin
     * @throws \Exception
     */
    public function saveConfig($url, $value, $id)
    {
        $this->saveCustomerStoreConfig($url, $value, $id);
    }
    

    /**
     * Function to extract information stored in the customer user table.
     *
     * @param $config
     * @param $id
     */
    public function getCustomerStoreConfig($config, $id)
    {
        return $this->customerFactory->create()->load($id)->getData($config);
    }


    /**
     * This function is used to save customer attributes to the
     * database and save it. Mostly used in the SSO flow to
     * update user attributes.
     *
     * @param $url
     * @param $value
     * @param $id
     * @throws \Exception
     */
    private function saveCustomerStoreConfig($url, $value, $id)
    {
        $data = [$url=>$value];
        $model = $this->customerFactory->create()->load($id)->addData($data);
        $model->setId($id)->save();
    }


    /**
     * Function to get the sites Base URL.
     */
    public function getBaseUrl()
    {
        return  $this->urlInterface->getBaseUrl();
    }


    public function getAcsUrl()
    {
        $url="mospsaml/actions/spObserver";
        return $this->getBaseUrl().$url;
    }

    /**
     * Function get the current url the user is on.
     */
    public function getCurrentUrl()
    {
        return  $this->urlInterface->getCurrentUrl();
    }


    /**
     * Function to get the url based on where the user is.
     *
     * @param $url
     */
    public function getUrl($url, $params = [])
    {
        return  $this->urlInterface->getUrl($url, ['_query'=>$params]);
    }


    /**
     * Function to get the sites frontend url.
     *
     * @param $url
     */
    public function getFrontendUrl($url, $params = [])
    {
        return  $this->frontendUrl->getUrl($url, ['_query'=>$params]);
    }


    /**
     * Function to get the sites Issuer URL.
     */
    public function getIssuerUrl()
    {
        return $this->getBaseUrl() . SPConstants::ISSUER_URL_PATH;
    }


    /**
     * Function to get the Image URL of our module.
     *
     * @param $image
     */
    public function getImageUrl($image)
    {
        return $this->assetRepo->getUrl(SPConstants::MODULE_DIR.SPConstants::MODULE_IMAGES.$image);
    }


    /**
     * Get Admin CSS URL
     */
    public function getAdminCssUrl($css)
    {
        return $this->assetRepo->getUrl(SPConstants::MODULE_DIR.SPConstants::MODULE_CSS.$css, ['area'=>'adminhtml']);
    }


    /**
     * Get Admin JS URL
     */
    public function getAdminJSUrl($js)
    {
        return $this->assetRepo->getUrl(SPConstants::MODULE_DIR.SPConstants::MODULE_JS.$js, ['area'=>'adminhtml']);
    }


    /**
     * Get Admin Metadata Download URL
     */
    public function getMetadataUrl()
    {
        return $this->assetRepo->getUrl(SPConstants::MODULE_DIR.SPConstants::MODULE_METADATA, ['area'=>'adminhtml']);
    }



    /**
     * Function to get the resource as a path instead of the URL.
     *
     * @param $key
     */
    public function getResourcePath($key)
    {
        return $this->assetRepo
                    ->createAsset(SPConstants::MODULE_DIR.SPConstants::MODULE_CERTS.$key, ['area'=>'adminhtml'])
                    ->getSourceFile();
    }


    /**
     * Get admin Base url for the site.
     */
    public function getAdminBaseUrl()
    {
        return $this->helperBackend->getHomePageUrl();
    }

    /**
     * Get the Admin url for the site based on the path passed,
     * Append the query parameters to the URL if necessary.
     *
     * @param $url
     * @param $params
     */
    public function getAdminUrl($url, $params = [])
    {
        return $this->helperBackend->getUrl($url, ['_query'=>$params]);
    }


    /**
     * Get the Admin secure url for the site based on the path passed,
     * Append the query parameters to the URL if necessary.
     *
     * @param $url
     * @param $params
     */
    public function getAdminSecureUrl($url, $params = [])
    {
        return $this->helperBackend->getUrl($url, ['_secure'=>true,'_query'=>$params]);
    }


    /**
     * Get the SP InitiatedURL
     *
     * @param $relayState
     */
    public function getSPInitiatedUrl($relayState = null, $idp_name=NULL)
    {
        $relayState = is_null($relayState) ?$this->getCurrentUrl() : $relayState;
        return $this->getFrontendUrl(
            SPConstants::SAML_LOGIN_URL,
            ["relayState"=>$relayState]
        )."&idp_name=".$idp_name;
    }

    /**
     * Set the entry in the miniorange_saml_idps Table
     *
     */  
    public function setIDPApps( $saml_identity_name,
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
    $sameasbilling
    )   
{   
    $model = $this->miniorangeSamlIDPsFactory->create();    
    $model->addData([   
        "idp_name" => $saml_identity_name,   
        "idp_entity_id" => $mo_idp_entity_id,   
        "saml_login_url" => $mo_idp_saml_login_url,  
        "saml_login_binding" => $mo_idp_saml_login_binding, 
        "saml_logout_url" => $mo_idp_saml_logout_url, 
        "saml_logout_binding" => $mo_idp_saml_logout_binding,
        "x509_certificate" => $mo_idp_x509_certificate,    
        "response_signed" => $mo_idp_response_signed,  
        "assertion_signed" => $mo_idp_assertion_signed,
        "show_admin_link" => $mo_idp_show_admin_link,  
        "show_customer_link" => $mo_idp_show_customer_link, 
        "auto_create_admin_users" =>$mo_idp_auto_create_admin_users , 
        "auto_create_customers" => $mo_idp_auto_create_customers,
        "disable_b2c" => $mo_idp_disable_b2c,
        "force_authentication_with_idp" => $mo_idp_force_authentication_with_idp,
        "auto_redirect_to_idp" => $mo_idp_auto_redirect_to_idp,
        "link_to_initiate_sso" => $mo_idp_link_to_initiate_sso,
        "update_attributes_on_login" => $mo_idp_update_attributes_on_login,
        "create_magento_account_by" => $mo_idp_create_magento_account_by,
        "email_attribute" => $mo_idp_email_attribute,
        "username_attribute" => $mo_idp_username_attribute,
        "firstname_attribute" => $mo_idp_firstname_attribute,
        "lastname_attribute" => $mo_idp_lastname_attribute,
        "group_attribute" => $mo_idp_group_attribute,
        "billing_city_attribute" => $mo_idp_billing_city_attribute,
        "billing_state_attribute" => $mo_idp_billing_state_attribute,
        "billing_country_attribute" => $mo_idp_billing_country_attribute,
        "billing_address_attribute" => $mo_idp_billing_address_attribute,
        "billing_phone_attribute" => $mo_idp_billing_phone_attribute,
        "billing_zip_attribute" => $mo_idp_billing_zip_attribute,
        "shipping_city_attribute" => $mo_idp_shipping_city_attribute,
        "shipping_state_attribute" => $mo_idp_shipping_state_attribute,
        "shipping_country_attribute" => $mo_idp_shipping_country_attribute,
        "shipping_address_attribute" => $mo_idp_shipping_address_attribute,
        "shipping_phone_attribute" => $mo_idp_shipping_phone_attribute,
        "shipping_zip_attribute" => $mo_idp_shipping_zip_attribute,
        "b2b_attribute" => $mo_idp_b2b_attribute,
        "custom_tablename" => $mo_idp_custom_tablename,
        "custom_attributes" => $mo_idp_custom_attributes,
        "do_not_autocreate_if_roles_not_mapped" => $mo_idp_do_not_autocreate_if_roles_not_mapped,
        "update_backend_roles_on_sso" => $mo_idp_update_backend_roles_on_sso,
        "update_frontend_groups_on_sso" => $mo_idp_update_frontend_groups_on_sso,
        "default_group" => $mo_idp_default_group,
        "default_role" => $mo_idp_default_role,
        "groups_mapped" => $mo_idp_groups_mapped,
        "roles_mapped" => $mo_idp_roles_mapped,
        "saml_logout_redirect_url"=> $mo_saml_logout_redirect_url,
        "saml_enable_billingandshipping"=>$billinandshippingchcekbox,
        "saml_sameasbilling" => $sameasbilling
    ]); 
    $model->save(); 
}

  /**
     * Get All the entry from the miniorange_saml_idps Table
     */
    public function getIDPApps()    
    {   
        $model = $this->miniorangeSamlIDPsFactory->create();    
        $collection = $model->getCollection();  
        return $collection; 
    }   

    /**
     * Delete the entry in the miniorange_saml_idps Table
     *
     * @param $id
     */
    public function deleteIDPApps($id)  
    {   
        $model = $this->miniorangeSamlIDPsFactory->create();    
        $model->load($id);  
        $model->delete();   
        
    }
    
        /**
     * Delete all the records
     */
    public function deleteAllRecords()
{
    $this->miniorangeSamlIDPsFactory
        ->create()
        ->getCollection()
        ->walk('delete');
}
}
