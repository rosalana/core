<?php

namespace Rosalana\Core\Contracts\Basecamp\Model;

interface ReadableExternalModel
{
    public function find(string|int $id);

    public function all(array $query = []);
}