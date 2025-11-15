<?php

namespace Rosalana\Core\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for interacting with Rosalana Basecamp and remote applications.
 *
 * @method static \Rosalana\Core\Services\Basecamp\AppsService apps()
 * @method static \Rosalana\Core\Services\Basecamp\TicketsService tickets()
 * @method static \Rosalana\Accounts\Services\Basecamp\UsersService users()
 * @method static \Rosalana\Accounts\Services\Basecamp\AuthService auth()
 * 
 * @method static \Illuminate\Http\Client\Response get(string $endpoint, ?string $pipeline = null)
 * @method static \Illuminate\Http\Client\Response post(string $endpoint, array $data = [], ?string $pipeline = null)
 * @method static \Illuminate\Http\Client\Response put(string $endpoint, array $data = [], ?string $pipeline = null)
 * @method static \Illuminate\Http\Client\Response patch(string $endpoint, array $data = [], ?string $pipeline = null)
 * @method static \Illuminate\Http\Client\Response delete(string $endpoint, ?string $pipeline = null)
 * 
 * @method static \Rosalana\Core\Services\Basecamp\Manager withPipeline(string $pipeline)
 * @method static \Rosalana\Core\Services\Basecamp\Manager withAuth(?string $token = null)
 * @method static \Rosalana\Core\Services\Basecamp\Manager to(string $name)
 * @method static \Rosalana\Core\Services\Basecamp\Manager version(string $version)
 * @method static \Rosalana\Core\Services\Basecamp\Manager reset()
 * @method static \Rosalana\Core\Services\Basecamp\Manager after(string $alias, \Closure $callback)
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
