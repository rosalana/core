<?php

namespace Rosalana\Core\Services;

use Illuminate\Support\Collection;
use Symfony\Component\Process\Process;
use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Facades\Process as ProcessFacade;
use Rosalana\Core\Package as AbstractPackage;

class Package
{
    /**
     * List of available packages in the ecosystem.
     */
    public static array $packages = [
        'rosalana/core' => 'Core constracts and services',
        'rosalana/accounts' => 'Package for managing user accounts and authentication',
        'rosalana/roles' => 'Package for managing user roles and permissions',
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
        }
    
        // Odstraň prefix 'v' pokud existuje (např. v0.3.5 → 0.3.5)
        $normalized = ltrim($coreVersion, 'v');
    
        // Získej major verzi (první číslo)
        if (preg_match('/^(\d+)\./', $normalized, $matches)) {
            return '^'.$matches[1];
        }
    
        return null;
    }

    public static function versions(?string $packageName = null): array
    {
        $packageName ??= 'rosalana/core';
        $process = Process::fromShellCommandline("composer show $packageName --all");
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
     * Multi-update all packages to the given version to keep them in sync.
     */
    public static function switchVersion(string $version): ProcessResult
    {
        $packages = Package::installed()->map(fn($p) => "$p->name:$version")->all();

        return ProcessFacade::run(['composer', 'require', ...$packages, '--with-all-dependencies']);
    }

    /**
     * Check if installed packages are compatible with the given version.
     * If not, return the list of incompatible packages.
     * 
     * @param string $version
     * @return Collection
     */
    public static function checkCompatibility(string $version): Collection
    {
        $packages = Package::installed()->filter(fn($p) => !$p->hasVersion($version));

        return $packages;
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
