<?php

namespace Rosalana\Core\Exceptions\Service\Basecamp\Model;

use Rosalana\Core\Exceptions\Http\RosalanaHttpException;

class ModelException extends RosalanaHttpException
{
    public function __construct(string $message = '', int $code = 0, ?\Exception $previous = null)
    {
        if ($previous instanceof RosalanaHttpException) {
            $response = $previous->getResponse();
        }

        parent::__construct($response ?? [], $message, $code, $previous);
    }
}
