<?php

namespace Rosalana\Core\Services;

use Illuminate\Support\Collection;
use Symfony\Component\Process\Process;
use Rosalana\Core\Package as AbstractPackage;

class Package
{
    /**
     * List of available packages in the ecosystem.
     */
    public static array $packages = [
        'rosalana/core' => 'Core constracts and services',
        'rosalana/accounts' => 'Package for managing user accounts and authentication',
    ];

    public static function getDescription(string $package): string
    {
        return self::$packages[$package];
    }

    /**
     * Get the version of the ecosystem.
     * @return string|null
     */
    public static function version(): ?string
    {
        $coreVersion = static::find('rosalana/core')->installedVersion;

        if ($coreVersion === 'dev-master') {
            return 'dev-master';
        } elseif (preg_match('/^(\d+)\./', $coreVersion, $matches)) {
            return $matches[1];
        } else {
            return null;
        }
    }

    public static function versions(): array
    {
        $process = Process::fromShellCommandline('composer show rosalana/core --all');
        $process->run();

        if (!$process->isSuccessful()) {
            return [];
        }

        $output = $process->getOutput();

        preg_match('/versions\s*:\s*(.*)/', $output, $matches);

        if (!isset($matches[1])) return [];

        $versions = collect(explode(',', $matches[1]))
            ->map(fn($v) => trim($v, '* v'))
            ->filter(fn($v) => preg_match('/^\d+\.\d+\.\d+$/', $v) || $v === 'dev-master')
            ->map(function ($version) {
                return $version === 'dev-master' ? 'dev-master' : explode('.', $version)[0];
            })
            ->unique()
            ->values()
            ->toArray();

        sort($versions); // seřaď podle major verze

        return $versions;
    }

    /**
     * Get the all packages.
     */
    public static function all(): Collection
    {
        $result = [];

        foreach (self::$packages as $package => $description) {
            $result[] = new AbstractPackage($package);
        }

        return collect($result);
    }

    /**
     * Find a package by name.
     */
    public static function find(string $name): ?AbstractPackage
    {
        if (array_key_exists($name, self::$packages)) {
            return new AbstractPackage($name);
        } else {
            return null;
        }
    }

    /**
     * Get the installed packages.
     */
    public static function installed(): Collection
    {
        return self::all()->filter(fn($package) => $package->installed);
    }

    /**
     * Get the not installed packages.
     */
    public static function notInstalled(): Collection
    {
        return self::all()->filter(fn($package) => !$package->installed);
    }

    /**
     * Get the published packages.
     */
    public static function published(): Collection
    {
        return self::all()->filter(fn($package) => $package->published);
    }
}
