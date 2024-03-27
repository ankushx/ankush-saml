<?php

namespace MiniOrange\SP\Controller\Actions;

/**
 * This class is called to log the customer user in. RelayState and
 * user are set separately. This is a simple class.
 */
class CustomerLoginAction extends BaseAction
{
    private $relayState;
    private $user;
    private $customerSession;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \MiniOrange\SP\Helper\SPUtility $spUtility,
        \Magento\Customer\Model\Session $customerSession
    ) {
        //You can use dependency injection to get any class this observer may need.
        $this->customerSession = $customerSession;
        parent::__construct($context, $spUtility);
    }

    /**
     * Execute function to execute the classes function.
     */
    public function execute()
    {
        $this->spUtility->customlog("CustomerLoginAction : execute: relaystate: ".$this->relayState);
        if(isset($this->user))
        $this->customerSession->setCustomerAsLoggedIn($this->user);
        if (!$this->spUtility->isBlank($this->relayState)) {
            $this->relayState = 'customer/account';
        }
        $this->spUtility->reinitConfig();
        return $this->spUtility->getUrl($this->relayState);
    }
    

    /** Setter for the user Parameter */
    public function setUser($user)
    {
        $this->user = $user;
       
        return $this;
    }


    /** Setter for the RelayState Parameter
     * @param $relayState
     * @return CustomerLoginAction
     */
    public function setRelayState($relayState)
    {
        $this->relayState = $relayState;
        return $this;
    }
}
