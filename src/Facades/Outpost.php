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
 * @method static void request(?string $name = null, array $payload = [])
 * @method static void confirm(?string $name = null, array $payload = [])
 * @method static void fail(?string $name = null, array $payload = [])
 * @method static void unreachable(?string $name = null, array $payload = [])
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
