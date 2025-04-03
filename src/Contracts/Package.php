<?php

namespace Rosalana\Core\Contracts;

interface Package
{
    /**
     * Self determine if the package is published.
     * Dont have to be all published to be considered published.
     */
    public function resolvePublished(): bool;

    /**
     * Return an array of options to publish.
     */
    public function publish(): array;
}