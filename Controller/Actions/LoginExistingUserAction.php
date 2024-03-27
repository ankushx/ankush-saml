<?php

namespace MiniOrange\SP\Controller\Actions;

use MiniOrange\SP\Helper\Curl;
use MiniOrange\SP\Helper\SPConstants;
use MiniOrange\SP\Helper\SPMessages;
use MiniOrange\SP\Helper\Exception\AccountAlreadyExistsException;
use MiniOrange\SP\Helper\Exception\NotRegisteredException;

/**
 * Handles processing of customer login page form or the
 * registration page form if it was found that a user
 * exists in the system.
 *
 * The main function of this action class is to authenticate
 * the user credentials as provided by calling an API and
 * fetching all of the relevant information of the customer.
 * Store the key, token and email in the database.
 */
class LoginExistingUserAction extends BaseAdminAction
{
    /**
     * Execute function to execute the classes function.
     *
     * @throws \Exception
     */
    public function execute()
    {
        $this->checkIfRequiredFieldsEmpty(['email'=>$this->REQUEST,'password'=>$this->REQUEST,
                                                'submit'=>$this->REQUEST]);
        $email = $this->REQUEST['email'];
        $password = $this->REQUEST['password'];
        $submit = $this->REQUEST['submit'];
        $this->getCurrentCustomer($email, $password);
        $this->spUtility->flushCache() ;
    }
    

    /**
     * Function is used to make a cURL call which will fetch
     * the user's data based on the username password provided
     * by the user.
     *
     * @param $email
     * @param $password
     * @throws AccountAlreadyExistsException
     */
    private function getCurrentCustomer($email, $password)
    {
        $content = Curl::get_customer_key($email, $password);
        $customerKey = json_decode($content, true);
        if (json_last_error() == JSON_ERROR_NONE) {
            // set the user values in the database
            $this->spUtility->setStoreConfig(SPConstants::SAMLSP_EMAIL, $email);
            $this->spUtility->setStoreConfig(SPConstants::SAMLSP_KEY, $customerKey['id']);
            $this->spUtility->setStoreConfig(SPConstants::API_KEY, $customerKey['apiKey']);
            $this->spUtility->setStoreConfig(SPConstants::TOKEN, $customerKey['token']);
            $this->spUtility->setStoreConfig(SPConstants::TXT_ID, '');
            $this->spUtility->setStoreConfig(SPConstants::REG_STATUS, SPConstants::STATUS_COMPLETE_LOGIN);
            $this->getMessageManager()->addSuccessMessage(SPMessages::REG_SUCCESS);
        } else {
            // wrong credentials provided or there was some error in fetching the user details
            $this->spUtility->setStoreConfig('miniorange/samlsp/registration/status', SPConstants::STATUS_VERIFY_LOGIN);
            throw new AccountAlreadyExistsException;
        }
    }
}
