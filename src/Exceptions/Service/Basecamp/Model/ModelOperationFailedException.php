<?php

namespace Rosalana\Core\Exceptions\Service\Basecamp\Model;

class ModelOperationFailedException extends ModelException
{
    public function __construct(string $operation, string $modelClass, string $identifier, \Exception $previous)
    {
        parent::__construct(
            message: "Failed to perform [{$operation}] operation on model [{$modelClass}] with identifier [{$identifier}]. Error: {$previous->getMessage()}",
            code: 500,
            previous: $previous
        );
    }
}
