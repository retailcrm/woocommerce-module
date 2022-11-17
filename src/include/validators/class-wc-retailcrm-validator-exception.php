<?php

class ValidatorException extends Exception
{
    public function __construct(string $message = '', int $code = 0)
    {
        // Add filter, if you need customization message of exception
        parent::__construct(apply_filters('retailcrm_validator_message', $message), $code);
    }
}
