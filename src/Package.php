<?php

namespace Rosalana\Core;

use Composer\InstalledVersions;
use Rosalana\Core\Contracts\Package as PackageContract;

class Package implements PackageContract
{
    protected string $name;
    protected string $installedVersion;
    protected ?string $publishedVersion;
    protected bool $published;
    protected bool $installed;
    protected PackageStatus $status;

    protected ?PackageContract $package = null;

    public function __construct(string $name)
    {
        // Inicializace hodnot – voláme metody, které mohou být specifické pro každou implementaci
        $this->name = $name;

        $packageClass = $this->resolvePackageClass();
        if (class_exists($packageClass)) {
            $this->package = new $packageClass();
        }

        $this->installedVersion = $this->resolveInstalledVersion();
        $this->installed = $this->resolveInstalled();
        $this->publishedVersion = $this->resolvePublishedVersion();
        $this->published = $this->resolvePublished();
        $this->status = $this->determinePublishStatus();
    }

    /**
     * Získá nainstalovanou verzi pomocí Composeru.
     */
    protected function resolveInstalledVersion(): ?string
    {
        try {
            return InstalledVersions::getVersion($this->name);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function resolveInstalled(): bool
    {
        return !is_null($this->installedVersion);
    }

    /**
     * Načte publikovanou verzi z konfiguračního souboru (např. rosalana.php).
     */
    protected function resolvePublishedVersion(): ?string
    {
        return config('rosalana.installed.' . $this->name);
    }

    public function resolvePublished(): bool
    {
        return $this->package?->resolvePublished() ?? false;
    }

    public function publish(): void
    {
        $this->package?->publish();
    }

    /**
     * Vyhodnotí stav publikace:
     * - 'up to date' pokud je publikován a verze odpovídá,
     * - 'old version' pokud je publikován, ale verze se liší,
     * - 'not published' pokud balíček není publikován.
     */
    protected function determinePublishStatus(): PackageStatus
    {
        if (!$this->installed) {
            return PackageStatus::NOT_INSTALLED;
        }

        if ($this->published) {
            if ($this->installedVersion === $this->publishedVersion) {
                return PackageStatus::UP_TO_DATE;
            }
            return PackageStatus::OLD_VERSION;
        }
        return PackageStatus::NOT_PUBLISHED;
    }

    protected function resolvePackageClass(): string
    {
        $name = $this->resolvePackageName();
        return '\\Rosalana\\' . $name . '\\Providers\\' . $name;
    }

    protected function resolvePackageName(): string
    {
        return ucfirst(explode('/', $this->name)[1]);
    }
}

enum PackageStatus: string
{
    case UP_TO_DATE = 'up to date';
    case OLD_VERSION = 'old version';
    case NOT_PUBLISHED = 'not published';
    case NOT_INSTALLED = 'not installed';
}
