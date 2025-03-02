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
     * Update the package.
     */
    public function update(): void;
}