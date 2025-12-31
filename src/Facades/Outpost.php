<?php

namespace Rosalana\Core\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for interacting with Rosalana Outpost (global async queue system).
 * 
 * @method static void worker()
 * @method static \Rosalana\Core\Services\Outpost\Manager to(string|array $name)
 * @method static \Rosalana\Core\Services\Outpost\Manager except(string|array $name)
 * @method static \Rosalana\Core\Services\Outpost\Manager broadcast()
 * @method static \Rosalana\Core\Services\Outpost\Manager responseTo(\Rosalana\Core\Services\Outpost\Message $message)
 * @method static \Rosalana\Core\Services\Outpost\Promise request(?string $name = null, array $payload = [])
 * @method static \Rosalana\Core\Services\Outpost\Promise confirm(?string $name = null, array $payload = [])
 * @method static \Rosalana\Core\Services\Outpost\Promise fail(?string $name = null, array $payload = [])
 * @method static \Rosalana\Core\Services\Outpost\Promise unreachable(?string $name = null, array $payload = [])
 * @method static \Rosalana\Core\Services\Outpost\Manager reset()
 * 
 * @see \Rosalana\Core\Services\Outpost\Manager
 */
class Outpost extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'rosalana.outpost';
    }
}
