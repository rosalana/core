<?php

namespace Rosalana\Core\Exceptions\Service\Basecamp\Model;

class ModelUpdateFailedException extends ModelOperationFailedException
{
    public function __construct(string $modelClass, string|int $id, \Exception $previous)
    {
        parent::__construct(
            operation: 'update',
            modelClass: $modelClass,
            identifier: (string) $id,
            previous: $previous
        );
    }
}
