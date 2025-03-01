<?php

namespace Rosalana\Core\Contracts;

interface PackageInterface
{
    /**
     * Resolve name of the package.
     */
    public function getName(): string;

    /**
     * Determine if the package is published.
     */
    public function isPublished(): bool;

    /**
     * Install the package.
     */
    public function install(): void;

    /**
     * Currently installed version of the package.
     */
    public function installedVersion(): string|null;

    /**
     * Last published version of the package.
     */
    public function publishedVersion(): string|null;
}