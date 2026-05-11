<?php

namespace Rosalana\Core\Contracts;

use Illuminate\Http\Client\Response;

interface ExternalModel extends ReadOnlyExternalModel
{
    public function create(array $data): Response;

    public function update(string|int $id, array $data): Response;

    public function delete(string|int $id): Response;
}