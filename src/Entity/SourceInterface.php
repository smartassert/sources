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

    /**
     * @return non-empty-string
     */
    public function getUserId(): string;

    /**
     * @return non-empty-string
     */
    public function getLabel(): string;

    /**
     * @param non-empty-string $label
     */
    public function setLabel(string $label): static;

    public function getDeletedAt(): ?\DateTimeImmutable;

    public function setDeletedAt(\DateTimeImmutable $deletedAt): void;

    /**
     * @return non-empty-string[]
     */
    public function getRunParameterNames(): array;

    public function getType(): Type;
}
