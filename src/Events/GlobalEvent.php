<?php

namespace Rosalana\Core\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

abstract class GlobalEvent implements ShouldQueue
{
    use Dispatchable, SerializesModels;

    public $connection;
    public $queue;

    public function __construct()
    {
        $this->connection = config('rosalana.events.global_connection');
        $this->queue = config('rosalana.events.global_queue');
    }

    // Můžeš doplnit další metody, logging, atd.
}
