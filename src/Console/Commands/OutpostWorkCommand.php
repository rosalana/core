<?php

namespace Rosalana\Core\Console;

use Illuminate\Console\Command;
use Rosalana\Core\Facades\App;
use Rosalana\Core\Facades\Outpost;

class OutpostWorkCommand extends Command
{
    protected $signature = 'outpost:work';

    protected $description = 'Listen to Outpost Redis stream';

    public function handle(): int
    {
        $this->components->info("Starting Outpost worker on `" . App::slug() . "`...");

        Outpost::worker(); // working...

        return Command::SUCCESS;
    }
}
