<?php

namespace Rosalana\Core\Services;

class Package
{
    // sjednocuje zde všechny definované packages

    public array $packages = [
        'rosalana/core',
        'rosalana/accounts'
    ];

    public function all()
    {
        $models = [];

        foreach ($this->packages as $package) {
            $class = $this->getPackageNamespace($package) . '\\Providers\\' . $this->getPackageName($package);
            if (!class_exists($class)) {
                continue;
            }
            $models[] = new $class;
        }

        return $models;
    }

    public static function get(string $name)
    {
        // return package model by name
    }

    protected function getPackageNamespace($package)
    {
        return '\\Rosalana\\' . $this->getPackageName($package);
    }

    protected function getPackageName($package)
    {
        return ucfirst(explode('/', $package)[1]);
    }
}