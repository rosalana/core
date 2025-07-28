<?php

namespace Rosalana\Core\Facades;

use Illuminate\Support\Facades\Facade;

class App extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'rosalana.app';
    }
}