<?php

namespace Rosalana\Core\Exceptions\Service\Basecamp\Model;

class AttemptWriteToUnwritableModelException extends ModelException
{
    public function __construct(string $modelClass)
    {
        parent::__construct(
            message: "Attempted to write to model [{$modelClass}] which does not implement the WritableExternalModel contract and is therefore not writable.",
            code: 500
        );
    }
}
