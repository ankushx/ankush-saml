<?php

namespace MiniOrange\SP\Helper\Exception;

/**
 * Exception denotes that there was ErrorMessage set during JS validation.
 */
class JSErrorException extends \Exception
{
    public function __construct($message)
    {
        $code         = 103;
        parent::__construct($message, $code, null);
    }

    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
