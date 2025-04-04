<?php

namespace Rosalana\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Laravel\Prompts\Concerns\Colors;
use Rosalana\Core\Services\Package;

class RosalanaCommnad extends Command
{
    use Colors;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rosalana';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rosalana command line interface';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $version = Package::version();

        $this->newLine();
        $this->line('   _____                     __                           ____ _     ___ ');
        $this->line('  |      \ _ _   ____  __ _ |  |  __ _ ___ _    __ _     / ___| |   |_ _|');
        $this->line('  |  |)  |  _  \/  __// _  ||  | / _  |  _  \  / _  |   | |   | |    | | ');
        $this->line('  |     <  ( ) |\__ \| ( | ||  || ( | | | |  || ( | |   | |___| |___ | | ');
        $this->line('  |__|\__|\___//____/ \__,_||__| \__,_|_| |__| \__,_|    \____|_____|___|');
        $this->newLine();

        $this->line("Version: {$this->cyan($version === 'dev-master' ? 'dev-master' : 'v' . trim($version, '^') . '.x.x')}");
        $this->newLine();
        $this->newLine();

        $commands = collect(Artisan::all())
            ->filter(fn($cmd, $name) => str_starts_with($name, 'rosalana:'))
            ->map(fn($cmd, $name) => [
                $name,
                $cmd->getDescription(),
            ])
            ->sortKeys();

        $this->table(['Command', 'Description'], $commands, 'compact');
    }
}