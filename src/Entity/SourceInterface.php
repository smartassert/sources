<?php

declare(strict_types=1);

namespace App\Entity;

interface SourceInterface extends UserHeldEntityInterface
{
    public function getId(): string;

    public function setDeletedAt(\DateTimeImmutable $deletedAt): void;

    public function getDeletedAt(): ?\DateTimeImmutable;
}
