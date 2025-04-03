<?php

namespace Rosalana\Core\Contracts;

interface Package
{
    /**
     * Self determine if the package is published.
     * #idea maybe should return array of bool for each option
     * ```
     * [
     *   'config' => true,
     *  'env' => false,
     * ]
     * ```
     */
    public function resolvePublished(): bool;

    /**
     * Return an array of options to publish.
     */
    public function publish(): array;
}