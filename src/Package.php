<?php

namespace Rosalana\Core;

use Composer\InstalledVersions;

abstract class Package
{
    protected string $name;
    protected string $installedVersion;
    protected ?string $publishedVersion;
    protected bool $published;
    protected string $publishStatus;

    public function __construct()
    {
        // Inicializace hodnot – voláme metody, které mohou být specifické pro každou implementaci
        $this->name = $this->resolveName();
        $this->installedVersion = $this->resolveInstalledVersion();
        $this->publishedVersion = $this->resolvePublishedVersion();
        $this->published = $this->resolvePublished();
        $this->publishStatus = $this->determinePublishStatus();
    }

    /**
     * Metoda, která vrací název balíčku (např. 'rosalana/core').
     * Musí být implementována v konkrétní třídě.
     */
    abstract protected function resolveName(): string;

    /**
     * Metoda, kterou implementace určí, zda je balíček publikován.
     */
    abstract protected function resolvePublished(): bool;

    /**
     * Metoda, která definuje, co se má stát při publikaci balíčku.
     */
    abstract public function publish(): void;

    /**
     * Získá nainstalovanou verzi pomocí Composeru.
     */
    protected function resolveInstalledVersion(): string
    {
        // Pokud není nalezena, vrací prázdný řetězec (můžeš upravit dle potřeby)
        return InstalledVersions::getVersion($this->resolveName()) ?? '';
    }

    /**
     * Načte publikovanou verzi z konfiguračního souboru (např. rosalana.php).
     */
    protected function resolvePublishedVersion(): ?string
    {
        return config('rosalana.installed.' . $this->resolveName());
    }

    /**
     * Vyhodnotí stav publikace:
     * - 'up to date' pokud je publikován a verze odpovídá,
     * - 'old version' pokud je publikován, ale verze se liší,
     * - 'not published' pokud balíček není publikován.
     */
    protected function determinePublishStatus(): string
    {
        if ($this->published) {
            if ($this->installedVersion === $this->publishedVersion) {
                return 'up to date';
            }
            return 'old version';
        }
        return 'not published';
    }
}
