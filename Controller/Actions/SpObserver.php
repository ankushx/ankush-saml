<?php

namespace MiniOrange\SP\Controller\Actions;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\Result\PageFactory;
use MiniOrange\SP\Helper\Exception\SAMLResponseException;
use MiniOrange\SP\Helper\Exception\InvalidSignatureInResponseException;
use MiniOrange\SP\Helper\SPMessages;
use MiniOrange\SP\Controller\Actions\ReadResponseAction;
use MiniOrange\SP\Helper\SPConstants;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use MiniOrange\SP\Helper\SPUtility;

class SpObserver extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{
    private $requestParams = [
        'SAMLRequest',
        'SAMLResponse',
        'option'
    ];

    protected $messageManager;
    protected $readResponseAction;
    protected $spUtility;
    protected $testAction;
    protected $currentControllerName;
    protected $currentActionName;
    protected $readLogoutRequestAction;
    protected $request;
    protected $resultFactory;
    protected $_pageFactory;
    protected $formkey;
    protected $acsUrl;

    public function __construct(
        ManagerInterface $messageManager,
        Context $context,
        \MiniOrange\SP\Controller\Actions\ReadResponseAction $readResponseAction,
        SPUtility $spUtility,
        Http $httpRequest,
        ReadLogoutRequestAction $readLogoutRequestAction,
        RequestInterface $request,
        ShowTestResultsAction $testAction,
        ResultFactory $resultFactory,
        PageFactory $pageFactory,
        FormKey $formkey
    ) {
        //You can use dependency injection to get any class this observer may need.
        $this->messageManager = $messageManager;
        $this->readResponseAction = $readResponseAction;
        $this->spUtility = $spUtility;
        $this->readLogoutRequestAction = $readLogoutRequestAction;
        $this->currentActionName = $httpRequest->getActionName();
        $this->request = $request;
        $this->testAction = $testAction;
        $this->resultFactory=$resultFactory;
        $this->_pageFactory = $pageFactory;
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

    public function execute()
    {
        $this->spUtility->customlog(" inside SpObserver : exceute ");    
        $keys=array_keys($this->request->getParams());
        $operation=array_intersect($keys, $this->requestParams);
        try {
            $params= $this->request->getParams();
            $postData= $this->request->getPost();
            $this->spUtility->customlog(" inside SpObserver : params ",$params);  
            $this->spUtility->customlog(" inside SpObserver : postData ",$postData);   
            $isTest=isset($params['RealayState']) && $params['RelayState']==SPConstants::TEST_RELAYSTATE;
            if (count($operation) > 0) {
                $result = $this->_route_data(array_values($operation)[0], $params, $postData);
                if (!$this->spUtility->isBlank($result)) {
                    // $observer->getControllerAction()->getResponse()->setRedirect($result);
                    $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                    $resultRedirect->setUrl($result);
                    return $resultRedirect;
                    
                }
            }

        } catch (\Exception $e) {
            if ($isTest) { // show a failed validation screen
                $this->testAction->setSamlException($e)->setHasExceptionOccurred(true)->execute();
            }
        }
    }
    
    private function _route_data($op, $params, $postData)
    {
        $this->spUtility->customlog(" inside SpObserver : _route_data ");     
        switch ($op) {

            case $this->requestParams[0]:
                return $this->readLogoutRequestAction->setRequestParam($params)
                    ->setPostParam($postData)->execute();
            case $this->requestParams[1]:
                return $this->readResponseAction->setRequestParam($params)
                    ->setPostParam($postData)->execute();
        }
    }
}
