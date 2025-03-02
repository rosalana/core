<?php

namespace Rosalana\Core;

use Composer\InstalledVersions;
use Illuminate\Process\Exceptions\ProcessFailedException;
use Illuminate\Support\Facades\Process;
use Rosalana\Core\Contracts\Package as PackageContract;

class Package implements PackageContract
{

    public string $name;
    public ?string $installedVersion;
    public ?string $publishedVersion;
    public bool $published;
    public bool $installed;
    public PackageStatus $status;

    public ?PackageContract $package = null;

    public function __construct(string $name)
    {
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
     * Install the package from scratch.
     */
    public function install(?string $version): void
    {
        $versionString = $version ? ':' . $version : '';
        $result = Process::run(['composer', 'require', "$this->name" . $versionString]);

        if ($result->failed()) {
            echo $result->errorOutput();
            throw new ProcessFailedException($result);
        }
    }

    /**
     * Update the package to the latest version.
     */
    public function update(): void 
    {
        //
    }

    /**
     * Publish the package.
     */
    public function publish(): array
    {
        return $this->package?->publish() ?? [];
    }

    /**
     * Determine if the package is published.
     */
    public function resolvePublished(): bool
    {
        return $this->package?->resolvePublished() && !is_null($this->resolvePublishedVersion());
    }

    /**
     * Get the installed version of the package.
     */
    protected function resolveInstalledVersion(): ?string
    {
        try {
            return InstalledVersions::getVersion($this->name);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Determine if the package is installed.
     */
    protected function resolveInstalled(): bool
    {
        return !is_null($this->installedVersion);
    }

    /**
     * Get the last published version of the package.
     */
    protected function resolvePublishedVersion(): ?string
    {
        return config('rosalana.installed.' . $this->name);
    }

    /**
     * Evaluate publication status:
     * - 'up to date' if published and version matches,
     * - 'old version' if published but version differs,
     * - 'not published' if package is not published,
     * - 'not installed' if package is not installed.
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

    /**
     * Resolve the package class.
     */
    protected function resolvePackageClass(): string
    {
        $name = $this->resolvePackageName();
        return '\\Rosalana\\' . $name . '\\Providers\\' . $name;
    }

    /**
     * Resolve the package name.
     */
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
