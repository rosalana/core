<?php

namespace Rosalana\Core\Contracts;

interface Package
{
    /**
     * Self determine if the package is published.
     */
    public function resolvePublished(): bool;

    /**
     * Return an array of options to publish.
     */
    public function publish(): array;

    /**
     * Refresh the published files.
     */
    public function refresh(): void;
}