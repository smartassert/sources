<?php

declare(strict_types=1);

namespace App\Entity;

interface GitSourceInterface extends SourceInterface
{
    /**
     * @return non-empty-string
     */
    public function getHostUrl(): string;

    /**
     * @return non-empty-string
     */
    public function getPath(): string;

    public function getCredentials(): string;

    /**
     * @param non-empty-string $label
     */
    public function setLabel(string $label): static;

    /**
     * @param non-empty-string $hostUrl
     */
    public function setHostUrl(string $hostUrl): void;

    /**
     * @param non-empty-string $path
     */
    public function setPath(string $path): void;

    public function setCredentials(string $credentials): void;
}
