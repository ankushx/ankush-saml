<?php

namespace MiniOrange\SP\Controller\Adminhtml\Metadata;

use Magento\Backend\App\Action\Context;
use MiniOrange\SP\Helper\SPConstants;
use MiniOrange\SP\Helper\SPMessages;
use MiniOrange\SP\Helper\Saml2\SAML2Utilities;
use MiniOrange\SP\Helper\Saml2\MetadataGenerator;
use MiniOrange\SP\Controller\Actions\BaseAdminAction;

/**
 * This class handles the action for endpoint: mospsaml/metadata/Index
 * Extends the \Magento\Backend\App\Action for Admin Actions which
 * inturn extends the \Magento\Framework\App\Action\Action class necessary
 * for each Controller class
 */
class Index extends BaseAdminAction
{

    private $fileSystem;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \MiniOrange\SP\Helper\SPUtility $spUtility,
        \Magento\Framework\Filesystem\Driver\File $fileSystem
    ) {
        //You can use dependency injection to get any class this observer may need.
        parent::__construct($context, $resultPageFactory, $spUtility);
        $this->fileSystem = $fileSystem;
    }


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
     * @deprecated - don't use this
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $entity_id = $this->spUtility->getIssuerUrl();
        $acs_url = $this->spUtility->getAcsUrl();
        $certificate = $this->spUtility->getFileContents($this->spUtility->getResourcePath('sp-certificate.crt'));
        $certificate = $this->spUtility->desanitizeCert($certificate);
        $metadata = new MetadataGenerator($entity_id, true, true, $certificate, $acs_url, $acs_url, $acs_url, $acs_url, $acs_url);
        $metadata = $metadata->generateSPMetadata();
    }


    /**
     * Is the user allowed to view the Metadata File.
     * This is based on the ACL set by the admin in the backend.
     * Works in conjugation with acl.xml
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(SPConstants::MODULE_DIR.SPConstants::METADATA_DOWNLOAD);
    }
}
