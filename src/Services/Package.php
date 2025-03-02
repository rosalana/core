<?php

namespace Rosalana\Core\Services;

class Package
{
    /**
     * Seznam dostupných balíčků v ekosystému.
     */
    public static array $packages = [
        'rosalana/core',
        'rosalana/accounts',
    ];

    /**
     * Vrátí název balíčku, např. z "rosalana/core" získá "Core".
     */
    protected static function getPackageName(string $package): string
    {
        return ucfirst(explode('/', $package)[1]);
    }

    /**
     * Sestaví plně kvalifikovaný název třídy balíčku.
     *
     * Předpokládáme, že každá implementace je umístěna v namespace:
     * \Rosalana\{PackageName}\Providers\{PackageName}
     *
     * Například pro "rosalana/core" vrátí "\Rosalana\Core\Providers\Core".
     */
    protected static function getPackageClass(string $package): string
    {
        $name = self::getPackageName($package);
        return '\\Rosalana\\' . $name . '\\Providers\\' . $name;
    }

    /**
     * Statická metoda all() pro získání všech balíčků, které jsou definovány a jejich třídy existují.
     *
     * @return array Instance balíčků (objekty implementující příslušný kontrakt/abstraktní třídu).
     */
    public static function all(): array
    {
        $result = [];

        foreach (self::$packages as $package) {
            $class = self::getPackageClass($package);
            if (class_exists($class)) {
                $result[] = new $class();
            }
        }

        return $result;
    }
}
