<?php

namespace Rosalana\Core\Contracts;

use Illuminate\Http\Client\Response;

interface RemoveableExternalModel
{
    public function delete(string|int $id): Response;
}