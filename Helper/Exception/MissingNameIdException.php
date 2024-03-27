<?php

namespace MiniOrange\SP\Helper\Exception;

use MiniOrange\SP\Helper\SPMessages;

/**
 * Exception denotes that NameID was missing from the
 * response or request.
 */
class MissingNameIdException extends \Exception
{
    public function __construct()
    {
        $message     = SPMessages::parse('MISSING_NAMEID');
        $code         = 126;
        parent::__construct($message, $code, null);
    }

    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
