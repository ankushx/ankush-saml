<?php
namespace MiniOrange\SP\Controller\Actions;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Message\ManagerInterface;
use MiniOrange\SP\Helper\SPMessages;
use MiniOrange\SP\Helper\SPConstants;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use MiniOrange\SP\Helper\SPUtility;
use DOMDocument;
use Magento\Customer\Model\Session;
use MiniOrange\SP\Controller\Actions\ProcessResponseAction;
use Magento\Framework\Session\SessionManagerInterface;
use MiniOrange\SP\Helper\Data;
use MiniOrange\SP\Helper\Saml2\Lib\AESEncryption;
/**
 * This class handles the action for endpoint: moidpsaml/idpsettings/Index
 * Extends the \Magento\Backend\App\Action for Admin Actions which
 * inturn extends the \Magento\Framework\App\Action\Action class necessary
 * for each Controller class
 */
class Metadata extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{
    protected $messageManager;
    protected $spUtility;
    protected $request;
    protected $formkey;
    protected $acsUrl;
    protected $customerSession;
    protected $session;
    protected $data;
    protected $urlInterface;
    protected $eventManager;
    public function __construct(
        ManagerInterface $messageManager,
        Context $context,
        SPUtility $spUtility,
        RequestInterface $request,
        FormKey $formkey,
        Session $customerSession,
        SessionManagerInterface $session,
        Data $data,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\UrlInterface $urlInterface
    ) {
        $this->messageManager = $messageManager;
        $this->spUtility = $spUtility;
        $this->request = $request;
        $this->customerSession = $customerSession;
        $this->session = $session;
        $this->data=$data;
        $this->eventManager = $eventManager;
        $this->urlInterface = $urlInterface;
        parent::__construct($context);
        $this->formkey=$formkey;
        $this->getRequest()->setParam('form_key', $this->formkey->getFormKey());
    }
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
    /**
     * The first function to be called when a Controller class is invoked.
     * Usually, has all our controller logic. Returns a view/page/template
     * to be shown to the users.
     *
     * This function gets and prepares all our SP config data from the
     * database. It's called when you visis the moasaml/idpsettings/Index
     * URL. It prepares all the values required on the SP setting
     * page in the backend and returns the block to be displayed.
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
     
    public function execute()
    {
            $base_url =  $this->spUtility->getBaseUrl();
            $header    = 'Content-Type: text/xml';
            header($header);
            print_r('<?xml version="1.0"?>
            <md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" entityID="'.$base_url.'mospsaml/metadata/index">
            <md:SPSSODescriptor WantAuthnRequestsSigned="true" protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
            <md:KeyDescriptor use="signing">
            <KeyInfo xmlns="http://www.w3.org/2000/09/xmldsig#">
            <X509Data>
            <X509Certificate>
            MIIEEzCCAvugAwIBAgIUPnF5ximRj6qaE72pmZaEa/v9ltswDQYJKoZIhvcNAQELBQAwgZgxCzAJBgNVBAYTAklOMRQwEgYDVQQIDAtNYWhhcmFzaHRyYTENMAsGA1UEBwwEUFVORTETMBEGA1UECgwKbWluaU9yYW5nZTEQMA4GA1UECwwHTWFnZW50bzERMA8GA1UEAwwIeGVjdXJpZnkxKjAoBgkqhkiG9w0BCQEWG21hZ2VudG9zdXBwb3J0QHhlY3VyaWZ5LmNvbTAeFw0yMzAxMTgxODQ4NTlaFw0yODAxMTcxODQ4NTlaMIGYMQswCQYDVQQGEwJJTjEUMBIGA1UECAwLTWFoYXJhc2h0cmExDTALBgNVBAcMBFBVTkUxEzARBgNVBAoMCm1pbmlPcmFuZ2UxEDAOBgNVBAsMB01hZ2VudG8xETAPBgNVBAMMCHhlY3VyaWZ5MSowKAYJKoZIhvcNAQkBFhttYWdlbnRvc3VwcG9ydEB4ZWN1cmlmeS5jb20wggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQDAwmji257/4jltOcMiv0uEpUqtCoeMRHSxSGzIvtymmkEhZw+2xsD8AUSTubyH+ZKYkZIm3Zlank9rxvDJA5sw701ziTPvARTIa6rtnGczap7xeTAtkbTWeewfEL/ThgGQXJ4eb9lasq9I+Vdi23quhaU1jBOuxL+36aeglzEYGVW6JpbqQCRjmpLv/2nJaQZ6gMayJ/2jCLq4+7I3kX1rgVUXs8I/atKqyDsYF/QFEWFRjITtXh94bxq9krXqnHQMa0YRnRUvpIn4EDxPTExVXxFb6NQ+3Waq9dKmxJuh1MW6uAimFLxZsDeUmid0tVxkfgHa1jfxSOepHk8urvrBAgMBAAGjUzBRMB0GA1UdDgQWBBTKFj++Zmkb8RzoCY9Og7d7JowgSjAfBgNVHSMEGDAWgBTKFj++Zmkb8RzoCY9Og7d7JowgSjAPBgNVHRMBAf8EBTADAQH/MA0GCSqGSIb3DQEBCwUAA4IBAQBjmD8Fr6jUQbMvAqc6bLS34Fe/fFAgLw5j7/IShoYPOSZjrokqzUqm1mRC6XrmzrX2qnrctwQ0d2S9wLDsHmH9Lk4Qx+Qi6YxzKnCpluL7vtgEu1Ub1JHuCEyZe7MIKXTY+Y991TKKDcbkAAohsWu+iW7sZc8AdsXdm5MkpxQar26DKmc44huAn4RnISuvh3CtoYdS8Y+iPGGSc3FRV8ppYW4E/eD2IgW4sX5AM/r/91to50VTxUAIFrHgWEuo/khq7awEwcJ+uFkyhwdyRUb1QaapwX+iuS4G8AOLOVG2uvlGu7gxQSGP+vpUtRk1S/H90cmswdR0CUZdwx8ubUoC
            </X509Certificate>
            </X509Data>
            </KeyInfo>
            </md:KeyDescriptor>
            <md:NameIDFormat>urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress</md:NameIDFormat>
            <md:NameIDFormat>urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified</md:NameIDFormat>
            <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="'.$base_url.'mospsaml/actions/spObserver"/>
            <md:SingleSignOnService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="'.$base_url.'mospsaml/actions/spObserver"/>
            <md:SingleSignOnService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" Location="'.$base_url.'mospsaml/actions/spObserver"/>
            </md:SPSSODescriptor>
            </md:EntityDescriptor>
            ');
            return;
        
    }
}