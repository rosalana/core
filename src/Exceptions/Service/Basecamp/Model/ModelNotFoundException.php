<?php

namespace Rosalana\Core\Exceptions\Service\Basecamp\Model;

class ModelNotFoundException extends ModelException
{
    public function __construct(string $modelClass, string|int $id, \Exception $previous)
    {
        parent::__construct(
            message: "Model [{$modelClass}] with identifier [{$id}] not found.",
            previous: $previous,
            code: 404
        );
    }
}
