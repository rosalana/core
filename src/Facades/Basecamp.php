<?php

namespace Rosalana\Core\Facades;

use Illuminate\Support\Facades\Facade;

class Basecamp extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'rosalana.basecamp';
    }
}