<?php

namespace Rosalana\Core\Exceptions\Service\Basecamp\Model;

class ModelCreateFailedException extends ModelOperationFailedException
{
    public function __construct(string $modelClass, \Exception $previous)
    {
        parent::__construct(
            operation: 'create',
            modelClass: $modelClass,
            identifier: '<new>',
            previous: $previous
        );
    }
}
