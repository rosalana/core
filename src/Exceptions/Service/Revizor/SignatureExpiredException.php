<?php

namespace Rosalana\Core\Exceptions\Service\Revizor;

class SignatureExpiredException extends \Exception 
{
    public function __construct($message = "Ticket signature has expired.")
    {
        parent::__construct($message);
    }
}
