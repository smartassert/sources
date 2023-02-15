<?php

declare(strict_types=1);

namespace App\Entity;

interface SourceInterface
{
    public function getId(): string;

    /**
     * @return non-empty-string
     */
    public function getUserId(): string;

    public function setDeletedAt(\DateTimeImmutable $deletedAt): void;

    public function getDeletedAt(): ?\DateTimeImmutable;
}
