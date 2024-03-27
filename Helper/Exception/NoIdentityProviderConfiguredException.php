<?php

namespace MiniOrange\SP\Helper\Exception;

use MiniOrange\SP\Helper\SPMessages;

/**
 * Exception denotes that user has not configured a SP.
 */
class NoIdentityProviderConfiguredException extends \Exception
{
    public function __construct()
    {
        $message     = SPMessages::parse('NO_IDP_CONFIG');
        $code         = 101;
        parent::__construct($message, $code, null);
    }

    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
