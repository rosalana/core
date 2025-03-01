<?php

namespace Rosalana\Core;

use Composer\InstalledVersions;
use Rosalana\Core\Contracts\PackageInterface;

abstract class Package implements PackageInterface
{
    public string $name;
    protected ?string $installedVersion;
    protected ?string $lastPublishedVersion;

    public function __construct()
    {
        $this->name = $this->name();
        $this->installedVersion = $this->installedVersion();
        $this->lastPublishedVersion = $this->publishedVersion();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isPublished(): bool
    {
        return $this->published();
    }

    public function installedVersion(): string|null
    {
        return InstalledVersions::getVersion($this->name);
    }

    public function publishedVersion(): string|null
    {
        return config('rosalana.installed.' . $this->name);
    }

    /**
     * Resolve name of the package.
     */
    abstract public function name(): string;
    /**
     * Determine if the package is published.
     */
    abstract public function published(): bool;
}
