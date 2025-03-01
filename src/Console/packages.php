<?php

namespace Rosalana\Core\Console;

trait Packages
{
    public function available()
    {
        return [
            'rosalana/core',
            'rosalana/accounts'
        ];
    }

    public function installed()
    {
        return collect(array_filter($this->available(), function ($package) {
            return $this->hasComposerPackage($package);
        }))->mapWithKeys(function ($package) {
            return [$package => $this->hasComposerPackage($package)];
        });
    }

    protected function hasComposerPackage($package)
    {
        $packages = json_decode(file_get_contents(base_path('composer.json')), true);

        return $packages['require'][$package] ?? $packages['require-dev'][$package] ?? null;
    }
}