<?php

namespace Rosalana\Core\Contracts;

interface ReadOnlyExternalModel
{
    public function find(string|int $id);

    public function all();
}