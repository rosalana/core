<?php

namespace Rosalana\Core\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for interacting with Rosalana App and its services.
 *
 * @method static string id()
 * @method static string key()
 * @method static string slug()
 * @method static string name()
 * @method static string version()
 * @method static mixed config(string $key, mixed $default = null)
 * @method static array meta()
 * @method static \Illuminate\Http\Client\Response self()
 * @method static \Illuminate\Http\Client\Response list()
 * @method static \Illuminate\Http\Client\Response find(string $name)
 * @method static \Rosalana\Core\Services\App\Context context()
 * @method static \Rosalana\Core\Services\App\Hooks hooks()
 *
 * @see \Rosalana\Core\Services\App\Manager
 */

class App extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'rosalana.app';
    }
}