<?php

declare(strict_types=1);

namespace App\Services\SourceRepository\Factory;

use App\Entity\SourceOriginInterface;
use App\Exception\SourceRepositoryCreationException;
use App\Model\SourceRepositoryInterface;

interface CreatorInterface
{
    public function createsFor(SourceOriginInterface $source): bool;

    /**
     * @param array<string, string> $parameters
     *
     * @throws SourceRepositoryCreationException
     */
    public function create(
        SourceOriginInterface $source,
        array $parameters
    ): ?SourceRepositoryInterface;
}
