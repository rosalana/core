<?php

namespace Rosalana\Core\Contracts;

interface ReadableExternalModel
{
    public function find(string|int $id);

    public function all();
}