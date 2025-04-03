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

        $this->table(
            ['Status', 'Package', 'Version', 'State', 'Description'],
            $packages->map(function ($package) {
                return [
                    $package->installed ? '[✓]' : '[ ]',
                    $package->name,
                    $package->installedVersion ?? '—',
                    $this->renderStatus($package->status->value),
                    \Rosalana\Core\Services\Package::getDescription($package->name),
                ];
            })->toArray()
        ,'borderless');
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
