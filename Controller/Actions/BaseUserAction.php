<?php

namespace MiniOrange\SP\Controller\Actions;

use MiniOrange\SP\Helper\SPConstants;

/**
 * The base user action class has some common functionality
 * and variables common for user related actions. This class
 * was specifically created to remove code duplication
 * as pointed out by Magento QA.
 */
abstract class BaseUserAction extends BaseAction
{
    protected $emailAttribute;
    protected $usernameAttribute;
    protected $firstName;
    protected $lastName;
    protected $checkIfMatchBy;
    protected $groupName;

    public function processUserValues()
    {
        $this->emailAttribute = $this->spUtility->getStoreConfig(SPConstants::MAP_EMAIL);
        $this->emailAttribute = $this->spUtility->isBlank($this->emailAttribute) ? SPConstants::DEFAULT_MAP_EMAIL : $this->emailAttribute;
        $this->usernameAttribute = $this->spUtility->getStoreConfig(SPConstants::MAP_USERNAME);
        $this->usernameAttribute = $this->spUtility->isBlank($this->usernameAttribute) ? SPConstants::DEFAULT_MAP_USERN : $this->usernameAttribute;
        $this->checkIfMatchBy = $this->spUtility->getStoreConfig(SPConstants::MAP_MAP_BY);
    }
}
