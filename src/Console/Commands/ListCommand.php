<?php

namespace Rosalana\Core\Console\Commands;

use Illuminate\Console\Command;
use Rosalana\Core\Console\InternalCommands;
use Rosalana\Core\Services\Package;

class ListCommand extends Command
{
    use InternalCommands;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rosalana:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List available Rosalana packages';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        dd(Package::all());
    }
}
