<?php

declare(strict_types=1);

namespace App\Entity;

interface SuiteInterface extends UserHeldEntityInterface, IdentifiedEntityInterface
{
    /**
     * @return non-empty-string
     */
    public function getId(): string;

    public function setSource(SourceInterface $source): void;

    /**
     * @param non-empty-string $label
     */
    public function setLabel(string $label): static;

    /**
     * @param non-empty-string[] $tests
     */
    public function setTests(array $tests): static;

    public function setDeletedAt(\DateTimeImmutable $deletedAt): void;

    public function getDeletedAt(): ?\DateTimeImmutable;
}
