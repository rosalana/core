<?php

namespace Rosalana\Core\Exceptions\Service\Basecamp\Model;

class ModelRefreshFailedException extends ModelOperationFailedException
{
    public function __construct(string $modelClass, string|int $id, \Exception $previous)
    {
        parent::__construct(
            operation: 'refresh',
            modelClass: $modelClass,
            identifier: (string) $id,
            previous: $previous
        );
    }
}
