<?php

declare(strict_types=1);

namespace App\Entity;

use App\Model\SourceRepositoryInterface;

interface FileSourceInterface extends SourceInterface, SourceRepositoryInterface
{
    /**
     * @param non-empty-string $label
     */
    public function setLabel(string $label): static;
}
