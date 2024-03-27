<?php
namespace MiniOrange\SP\Block;

use MiniOrange\SP\Helper\SPConstants;

/**
 * This class is used to denote our admin block for all our
 * backend templates. This class has certain commmon
 * functions which can be called from our admin template pages.
 */
class Sp extends \Magento\Framework\View\Element\Template
{


    private $spUtility;
    private $adminRoleModel;
    private $userGroupModel;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \MiniOrange\SP\Helper\SPUtility $spUtility,
        \Magento\Authorization\Model\ResourceModel\Role\Collection $adminRoleModel,
        \Magento\Customer\Model\ResourceModel\Group\Collection $userGroupModel,
        array $data = []
    ) {
        $this->spUtility = $spUtility;
        $this->adminRoleModel = $adminRoleModel;
        $this->userGroupModel = $userGroupModel;
        parent::__construct($context, $data);
    }

    /**
     * This function is a test function to check if the template
     * is being loaded properly in the frontend without any issues.
     */
    public function getHelloWorldTxt()
    {
        return 'Hello world!';
    }
    public function isDebugLogEnable()
    {
        return $this->spUtility->getStoreConfig(SPConstants::ENABLE_DEBUG_LOG);
    }

    public function getAcsUrl()
    {

        return $this->spUtility->getAcsUrl();
    }
    /**
     * This function retrieves the miniOrange customer Email
     * from the database. To be used on our template pages.
     */
    public function getCustomerEmail()
    {
        return $this->spUtility->getStoreConfig(SPConstants::SAMLSP_EMAIL);
    }


    /**
     * This function retrieves the miniOrange customer key from the
     * database. To be used on our template pages.
     */
    public function getCustomerKey()
    {
        return $this->spUtility->getStoreConfig(SPConstants::SAMLSP_KEY);
    }
    /**

     * This function retrieves the miniOrange plugin version from the

     * database. To be used on our template pages.

     */

    public function getCurrentVersion()
    {
      return SPConstants::VERSION;

    }

    public function getIdpGuideBaseUrl($idp)
    {
        return $this->spUtility->getIdpGuideBaseUrl($idp);
    }

    /**
     * This function retrieves the miniOrange API key from the database.
     * To be used on our template pages.
     */
    public function getApiKey()
    {
        return $this->spUtility->getStoreConfig(SPConstants::API_KEY);
    }


    /**
     * This function retrieves the token key from the database.
     * To be used on our template pages.
     */
    public function getToken()
    {
        return $this->spUtility->getStoreConfig(SPConstants::TOKEN);
    }


    /**
     * This function checks if the admin has signed enabled
     * response signed in the module settings.
     */
    public function isResponseSigned()
    {
        return $this->spUtility->getStoreConfig(SPConstants::RESPONSE_SIGNED);
    }




    /**
     * This function checks if the admin has enabled signed
     * assertion in the module settings.
     */
    public function isAssertionSigned()
    {
        return $this->spUtility->getStoreConfig(SPConstants::ASSERTION_SIGNED);
    }


    /**
     * This function checks if the SP has been configured or not.
     */
    public function isSPConfigured()
    {
        return $this->spUtility->isSPConfigured();
    }


    /**
     * This function fetches the Issuer value saved by the admin for the IDP
     */
    public function getSAMLIssuer()
    {
        return $this->spUtility->getStoreConfig(SPConstants::ISSUER);
    }


    /**
     * This function fetches the SSO URL saved by the admin for the IDP
     */
    public function getSSOUrl()
    {
        return $this->spUtility->getStoreConfig(SPConstants::SAML_SSO_URL);
    }


    /**
     * This function fetches the Name of the IDP saved by the admin for the IDP
     */
    public function getIdentityProviderName()
    {
        return $this->spUtility->getStoreConfig(SPConstants::IDP_NAME);
    }

    /**
     * upload metadata
     */
    public function uploadMetadata()
    {
        $this->spUtility->handle_upload_metadata();
    }

    /**
     * This function fetches the SSO binding type saved by the admin for the IDP
     */
    public function getLoginBindingType()
    {
        return $this->spUtility->getStoreConfig(SPConstants::BINDING_TYPE);
    }


    /**
     * This function gets the admin CSS URL to be appended to the
     * admin dashboard screen.
     */
    public function getAdminCssURL()
    {
        return $this->spUtility->getAdminCssUrl('adminSettings.css');
    }


    /**
     * This function gets the admin JS URL to be appended to the
     * admin dashboard pages for plugin functionality
     */
    public function getAdminJSURL()
    {
        return $this->spUtility->getAdminJSUrl('adminSettings.js');
    }


    /**
     * This function gets the IntelTelInput JS URL to be appended
     * to admin pages to show country code dropdown on phone number
     * fields.
     */
    public function getIntlTelInputJs()
    {
        return $this->spUtility->getAdminJSUrl('intlTelInput.min.js');
    }

    /**
     * Get all admin roles set by the admin on his site.
     */
    public function getAllRoles()
    {
        return $this->adminRoleModel->toOptionArray();
    }


    /**
     * Get all customer groups set by the admin on his site.
     */
    public function getAllGroups()
    {
        return $this->userGroupModel->toOptionArray();
    }


    /**
     * This function fetches the X509 cert saved by the admin for the IDP
     * in the plugin settings.
     */
    public function getX509Cert()
    {
        return $this->spUtility->getStoreConfig(SPConstants::X509CERT);
    }


    /**
     * This function fetches/creates the TEST Configuration URL of the
     * Plugin.
     */
    public function getTestUrl($idp_name=NULL)
    {
        return $this->getSPInitiatedUrl(SPConstants::TEST_RELAYSTATE,$idp_name);
    }


    /**
     * Get/Create Issuer URL of the site
     */
    public function getIssuerUrl()
    {
        return $this->spUtility->getIssuerUrl();
    }


    /**
     * Get/Create Base URL of the site
     */
    public function getBaseUrl()
    {
        return $this->spUtility->getBaseUrl();
    }


    /**
     * Create the URL for one of the SAML SP plugin
     * sections to be shown as link on any of the
     * template files.
     */
    public function getExtensionPageUrl($page)
    {
        return $this->spUtility->getAdminUrl('mospsaml/'.$page.'/index');
    }


    /**
     * Reads the Tab and retrieves the current active tab
     * if any.
     */
    public function getCurrentActiveTab()
    {
        $page = $this->getUrl('*/*/*', ['_current' => true, '_use_rewrite' => false]);
        $start = strpos($page, 'mospsaml/')+9;
        $end = strpos($page, '/index/key');
        return substr($page, $start, $end-$start);
    }


    /**
     * Get/Create a MetadataURL for the site
     */
    public function getMetadataUrl()
    {
        return $this->getBaseUrl() . 'mospsaml/actions/Metadata';
    }

    /**
     * Is the option to show SSO link on the Customer login page enabled
     * by the admin.
     */
    public function showCustomerLink()
    {
        return $this->spUtility->getStoreConfig(SPConstants::SHOW_CUSTOMER_LINK);
    }


    /**
     * Create/Get the SP initiated URL for the site.
     */
    public function getSPInitiatedUrl($relayState = null, $idp_name=NULL)
    {
        return $this->spUtility->getSPInitiatedUrl($relayState,$idp_name);
    }


    /**
     * This fetches the setting saved by the admin which decides if the
     * account should be mapped to username or email in Magento.
     */
    public function getAccountMatcher()
    {
        return $this->spUtility->getStoreConfig(SPConstants::MAP_MAP_BY);
    }

         /**
     * This fetches the setting saved by the admin which decides what
     * attribute in the SAML response should be mapped to the Magento
     * Username.
     */
    public function samlUsernameMapping()
    {
        $samlAmUsername = $this->spUtility->getStoreConfig(SPConstants::MAP_USERNAME);
        return !$this->spUtility->isBlank( $samlAmUsername) ?  $samlAmUsername : 'NameID';
    }

    public function getEmailMapping()
    {
        $amEmail = $this->spUtility->getStoreConfig(SPConstants::MAP_EMAIL);
        return !$this->spUtility->isBlank( $amEmail) ?  $amEmail : 'NameID';
    }


    /**
     * Get the default role to be set for the user if it
     * doesn't match any of the role/group mappings
     */
    public function getDefaultRole()
    {
        $defaultRole = $this->spUtility->getStoreConfig(SPConstants::MAP_DEFAULT_ROLE);
        return !$this->spUtility->isBlank($defaultRole) ?  $defaultRole : SPConstants::DEFAULT_ROLE;
    }


    /**
     * This fetches the registration status in the plugin.
     * Used to detect at what stage is the user at for
     * registration with miniOrange
     */
    public function getRegistrationStatus()
    {
        return $this->spUtility->getStoreConfig(SPConstants::REG_STATUS);
    }


    /**
     * Get the Current Admin user from session
     */
    public function getCurrentAdminUser()
    {
        return $this->spUtility->getCurrentAdminUser();
    }


    /**
     * Fetches/Creates the text of the button to be shown
     * for SP inititated login from the admin / customer
     * login pages.
     */
    public function getSSOButtonText()
    {
        $buttonText = $this->spUtility->getStoreConfig(SPConstants::BUTTON_TEXT);
        $idpName = $this->spUtility->getStoreConfig(SPConstants::IDP_NAME);
        return !$this->spUtility->isBlank($buttonText) ?  $buttonText : 'Login with ' . $idpName;
    }


     /**
      * Get base url of miniorange
      */
    public function getMiniOrangeUrl()
    {
        return $this->spUtility->getMiniOrangeUrl();
    }


    /* ===================================================================================================
                THE FUNCTIONS BELOW ARE FREE PLUGIN SPECIFIC AND DIFFER IN THE PREMIUM VERSION
       ===================================================================================================
     */


    /**
     * This function checks if the user has completed the registration
     * and verification process. Returns TRUE or FALSE.
     */
    public function isEnabled()
    {
        return $this->spUtility->micr();
    }

    
     /**
     * To get the configuration of Identity Provider
     */
    public function getAllIdpConfiguration(){
        return $this->spUtility->getIDPApps();
    }
}
