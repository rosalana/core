<?php

namespace Rosalana\Core\Exceptions\Service\Basecamp\Model;


class AttemptReadFromUnreadableModelException extends ModelException
{
    public function __construct(string $modelClass)
    {
        parent::__construct(
            message: "Attempted to read from model [{$modelClass}] which does not implement the ReadableExternalModel contract and is therefore not readable.",
            code: 500
        );
    }
}