<?php

namespace MiniOrange\SP\Helper\Exception;

use MiniOrange\SP\Helper\SPMessages;

/**
 * Exception denotes that IDP is not valid as it maynot
 * have all the necessary information about a IDP
 */
class InvalidIdentityProviderException extends \Exception
{
    public function __construct()
    {
        $message     = SPMessages::parse('INVALID_IDP');
        $code         = 119;
        parent::__construct($message, $code, null);
    }

    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
