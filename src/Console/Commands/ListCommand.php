<?php

namespace Rosalana\Core\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Prompts\Concerns\Colors;
use Rosalana\Core\Console\InternalCommands;
use Rosalana\Core\Services\Package;

class ListCommand extends Command
{
    use InternalCommands;
    use Colors;
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
    protected $description = 'List all available Rosalana packages and their status';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->newLine();
        $this->components->info('📦  Rosalana Packages');
        $this->newLine();

        $packages = Package::all();

        foreach ($packages as $package) {
            $check = $package->installed ? '[✓]' : '[ ]';
            $name = str_pad($package->name,  24); // package name padding
            $version = str_pad($package->installedVersion ?? '—', 10);
            $status = $this->renderStatus($package->status->value);

            $this->line("{$check} {$name} {$version} {$status}");
            // $this->line("     <fg=gray>" . Package::getDescription($package->name) . "</>");
            $this->newLine();
        }
    }

    protected function renderStatus(string $status): string
    {
        return match ($status) {
            'up to date'     => '✅ Published (latest)',
            'old version'    => '⏳ Published (outdated)',
            'not published'  => '🔴 Not published',
            'not installed'  => '⛔ Not installed',
            default          => '❓ Unknown',
        };
    }
}
