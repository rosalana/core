<?php

namespace Rosalana\Core\Contracts;

interface Package
{
    /**
     * Self determine if the package is published.
     */
    public function resolvePublished(): bool;

    /**
     * Publish the package.
     */
    public function publish(): void;

    /**
     * Refresh the published files.
     */
    public function refresh(): void;
}