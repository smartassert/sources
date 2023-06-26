<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\Source\Type;

interface SourceInterface extends UserHeldEntityInterface
{
    /**
     * @return non-empty-string
     */
    public function getId(): string;

    public function setDeletedAt(\DateTimeImmutable $deletedAt): void;

    public function getDeletedAt(): ?\DateTimeImmutable;

    /**
     * @return non-empty-string[]
     */
    public function getRunParameterNames(): array;

    public function getType(): Type;
}
