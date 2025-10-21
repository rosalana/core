<?php

namespace Rosalana\Core\Exceptions\Service\Revizor;

class SignatureMismatchException extends \Exception 
{
    public function __construct($message = "Signature verification failed.")
    {
        parent::__construct($message);
    }
}
