<?php

namespace Rosalana\Core\Facades;

use Illuminate\Support\Facades\Facade;

class Trace extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'rosalana.trace';
    }
}