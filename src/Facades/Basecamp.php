<?php

namespace Rosalana\Core\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Rosalana\Core\Services\Basecamp\AppsService apps()
 * @method static \Rosalana\Accounts\Services\Basecamp\UsersService users()
 * @method static \Rosalana\Accounts\Services\Basecamp\AuthService auth()
 * 
 * @see \Rosalana\Core\Services\Basecamp\Manager
 */
class Basecamp extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'rosalana.basecamp';
    }
}