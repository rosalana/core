<?php

namespace Rosalana\Core\Console;

use Illuminate\Console\Command;

class ComposerManager extends Command
{
    public function require(string $package): void
    {
        $this->call('composer', ['require', $package]);
    }

    public function update(string $package): void
    {
        $this->call('composer', ['update', $package]);
    }
}