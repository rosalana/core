<?php

namespace Rosalana\Core\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

abstract class GlobalEvent implements ShouldQueue
{
    use Dispatchable, SerializesModels;

    /**
     * Pokud chceš odlišit fronty, můžeš nastavit:
     */
    public $connection = 'global-rabbit';   // jméno connection z config/queue.php
    public $queue = 'global-events';        // jméno fronty

    // Případné sdílené property nebo metody pro globální eventy
}
