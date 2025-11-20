<?php

declare(strict_types=1);

namespace App\Entity;

use App\Model\SourceRepositoryInterface;

interface FileSourceInterface extends SourceInterface, SourceRepositoryInterface, IdentifiedEntityInterface
{
    public function getIdentifier(): EntityIdentifierInterface;
}
