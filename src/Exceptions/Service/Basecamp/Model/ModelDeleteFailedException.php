<?php

namespace Rosalana\Core\Exceptions\Service\Basecamp\Model;

class ModelDeleteFailedException extends ModelOperationFailedException
{
    public function __construct(string $modelClass, string|int $id, \Exception $previous)
    {
        parent::__construct(
            operation: 'delete',
            modelClass: $modelClass,
            identifier: (string) $id,
            previous: $previous
        );
    }
}
