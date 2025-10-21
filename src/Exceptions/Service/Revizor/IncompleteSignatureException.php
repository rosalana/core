<?php

namespace Rosalana\Core\Exceptions\Service\Revizor;

class IncompleteSignatureException extends \Exception
{
    public function __construct($message = "Signature is missing or incomplete.")
    {
        parent::__construct($message);
    }
}
