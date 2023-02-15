<?php

declare(strict_types=1);

namespace App\Entity;

interface SourceInterface extends UserHeldEntityInterface
{
    /**
     * @return non-empty-string
     */
    public function getId(): string;

    public function setDeletedAt(\DateTimeImmutable $deletedAt): void;

    public function getDeletedAt(): ?\DateTimeImmutable;
}
