<?php

namespace Rosalana\Core\Exceptions\Service\Basecamp\Model;

class ModelFindFailedException extends ModelOperationFailedException
{
    public function __construct(string $modelClass, string|int $id, \Exception $previous)
    {
        parent::__construct(
            operation: 'find',
            modelClass: $modelClass,
            identifier: (string) $id,
            previous: $previous
        );
    }
}
