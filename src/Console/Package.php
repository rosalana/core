<?php

namespace Rosalana\Core\Services;

use Illuminate\Support\Collection;
use Composer\InstalledVersions;

abstract class Package
{

    /**
     * Packages available to install in rosalana ecosystem.
     */
    public static function available()
    {
        return [
            'rosalana/core',
            'rosalana/accounts'
        ];
    }

    /**
     * Packages installed in the host application composer.json.
     */
    public function installed(): Collection
    {
        return collect($this->available())
            ->mapWithKeys(fn($package) => [
                $package => $this->getInstalledVersion($package)
            ])
            ->filter();
    }

    /**
     * Packages that have been published in the host application.
     */
    public function published(): Collection
    {
        return $this->installed()->mapWithKeys(function ($version, $package) {
            $status = $this->red('not published');
            $isPublished = $this->isPublished($package);

            return [$package => [
                'version' => $version,
            ]];

            // if ($isPublished) {
            //     $status = $this->yellow($version . ' (old version)');

            //     if ($version === $this->installed()[$package]) {
            //         $status = $this->cyan($version . ' (up to date)');
            //     }
            // }

            // return [$package => $status];
        });


        /**
         * Jak zjistíme jestli package byl publikován?
         * 
         * 1. Zjistíme jestli je package nainstalovany v composer.json
         * 2. Pokud package říká že je publikován a v rosalana.php je verze a ne null - už byla publikovana
         * 3. Pokud ale verze je null - nebyla publikovaná
         * 4. Pokud je verze jiná než v composer.json - nebyla publikovana nejnovější verze
         */

        // pouze instalované packages [name => version]
        // $installed = $this->installed();

        // // zjistíme jestli package říká že je publikovan a co je v rosalana.php

        // $published = [];

        // foreach ($installed as $package => $version) {
        //     $status = $this->red('not published');
        //     // Zjistíme jestli existují soubory a verze není null
        //     $isPublished = $this->hasPublished($package);

        //     if ($published) {
        //         $status = $this->yellow($version . ' (old version)');

        //         if ($version === $this->installed()[$package]) {
        //             $status = $this->cyan($version . ' (up to date)');
        //         }
        //     }

        //     $published[$package] = $status;

        // }

        // return collect($published);



    }

    /**
     * Check if the package's files exist and the version is the same 
     * as the installed version.
     */
    protected function isPublished($package)
    {
        $class = $this->getPackageNamespace($package) . '\\Providers\\EnsurePublished';
        if (!class_exists($class)) {
            return false;
        }
        $isPublished = new $class();

        $rosalanaConfig = config('rosalana.installed', []);

        if ($isPublished && array_key_exists($package, $rosalanaConfig) && !is_null($rosalanaConfig[$package])) {
            // retrun stored version
            return $rosalanaConfig[$package];
        }

        return false;
    }

    protected function getPackageNamespace($package)
    {
        return '\\Rosalana\\' . ucfirst(explode('/', $package)[1]);
    }

    protected function getInstalledVersion($package)
    {
        return InstalledVersions::getVersion($package);
    }
}
