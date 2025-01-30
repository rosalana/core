<?php

namespace Rosalana\Core\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

abstract class LocalEvent implements ShouldQueue
{
    use Dispatchable, SerializesModels;

    public $connection;
    public $queue;

    public function __construct()
    {
        $this->connection = config('rosalana.events.local_connection');
        $this->queue = config('rosalana.events.local_queue');
    }

    // Případné další metody či property, které jsou pro lokální eventy společné
}
