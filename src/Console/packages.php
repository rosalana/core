<?php

namespace Rosalana\Core\Console;

use Illuminate\Support\Collection;
use Laravel\Prompts\Concerns\Colors;

trait Packages
{
    use Colors;
    /**
     * Packages available to install in rosalana ecosystem.
     */
    public function available()
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
                $package => $this->hasComposerPackage($package)
            ])
            ->filter();
    }

    /**
     * Packages that have been published in the host application.
     */
    public function published(): Collection
    {
        /**
         * Jak zjistíme jestli package byl publikován?
         * 
         * 1. Zjistíme jestli je package nainstalovany v composer.json
         * 2. Pokud package říká že je publikován a v rosalana.php je verze a ne null - už byla publikovana
         * 3. Pokud ale verze je null - nebyla publikovaná
         * 4. Pokud je verze jiná než v composer.json - nebyla publikovana nejnovější verze
         */

        // pouze instalované packages [name => version]
        $installed = $this->installed();

        // zjistíme jestli package říká že je publikovan a co je v rosalana.php

        $published = [];

        foreach ($installed as $package => $version) {
            $status = $this->red('not published');
            // Zjistíme jestli existují soubory a verze není null
            $isPublished = $this->hasPublished($package);

            if ($published) {
                $status = $this->yellow($version . ' (old version)');

                if ($version === $this->installed()[$package]) {
                    $status = $this->cyan($version . ' (up to date)');
                }
            }

            $published[$package] = $status;

        }

        return collect($published);





        // return $this->installed()->mapWithKeys(function ($version, $package) {
        //     return [$package => $this->hasPublished($package) ? $version : 'not published'];
        // });
    }

    /**
     * Check if the package's files exist and the version is the same 
     * as the installed version.
     */
    protected function hasPublished($package)
    {
        $packageFilesExists = new $this->getPackageNamespace($package) . '\\Providers\\' . 'EnsurePublished'();

        $rosalanaConfig = config('rosalana.installed', []);

        return $packageFilesExists && (array_key_exists($package, $rosalanaConfig) && !is_null($rosalanaConfig[$package]));
    }

    protected function getPackageNamespace($package)
    {
        return '\\Rosalana\\' . ucfirst(explode('/', $package)[1]);
    }

    protected function hasComposerPackage($package)
    {
        $packages = json_decode(file_get_contents(base_path('composer.json')), true);

        return $packages['require'][$package] ?? $packages['require-dev'][$package] ?? null;
    }
}
