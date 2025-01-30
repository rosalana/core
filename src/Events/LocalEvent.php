<?php

namespace Rosalana\Core\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

abstract class LocalEvent implements ShouldQueue
{
    use Dispatchable, SerializesModels;

    /**
     * Můžeš tady definovat např. $connection, pokud chceš
     * aby se lokální eventy zpracovávaly jen "lokální" frontou.
     *
     * public $connection = 'local-db';
     */

    // Případné sdílené property nebo metody pro lokální eventy
}
