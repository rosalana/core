<?php

namespace Rosalana\Core\Contracts\Basecamp\Model;

use Illuminate\Http\Client\Response;

interface RemoveableExternalModel
{
    public function delete(string|int $id): Response;
}