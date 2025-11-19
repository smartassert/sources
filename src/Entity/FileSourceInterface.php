<?php

declare(strict_types=1);

namespace App\Entity;

use App\Model\SourceRepositoryInterface;

interface FileSourceInterface extends SourceInterface, SourceRepositoryInterface, IdentifiedEntityInterface
{
    /**
     * @param non-empty-string $label
     */
    public function setLabel(string $label): static;

    public function getIdentifier(): EntityIdentifierInterface;
}
