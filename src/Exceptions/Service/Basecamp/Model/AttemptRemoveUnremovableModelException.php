<?php

namespace Rosalana\Core\Exceptions\Service\Basecamp\Model;

class AttemptRemoveUnremovableModelException extends ModelException
{
    public function __construct(string $modelClass)
    {
        parent::__construct(
            message: "Attempted to remove model [{$modelClass}] which does not implement the RemoveableExternalModel contract and is therefore not removable.",
            code: 500
        );
    }
}
