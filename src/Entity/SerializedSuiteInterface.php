<?php

declare(strict_types=1);

namespace App\Entity;

interface SerializedSuiteInterface extends UserHeldEntityInterface, IdentifiedEntityInterface
{
    /**
     * @return non-empty-string
     */
    public function getId(): string;

    /**
     * @return array<string, string>
     */
    public function getParameters(): array;

    public function getSuite(): Suite;

    public function getDirectoryPath(): string;

    public function getNotifyUrl(): string;
}
