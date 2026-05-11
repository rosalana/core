<?php

namespace Rosalana\Core\Contracts\Basecamp\Model;

use Illuminate\Http\Client\Response;

interface WritableExternalModel
{
    public function create(array $data): Response;

    public function update(string|int $id, array $data): Response;
}
