<?php

declare(strict_types=1);

namespace App\Services\SourceRepository\Factory;

use App\Entity\SourceInterface;
use App\Exception\SourceRepositoryCreationException;
use App\Model\SourceRepositoryInterface;

interface CreatorInterface
{
    public function createsFor(SourceInterface $source): bool;

    /**
     * @param array<string, string> $parameters
     *
     * @throws SourceRepositoryCreationException
     */
    public function create(SourceInterface $source, array $parameters): ?SourceRepositoryInterface;
}
