<?php

declare(strict_types=1);

namespace App\Services\SourceRepository\Factory;

use App\Entity\OriginSourceInterface;
use App\Exception\SourceRepositoryCreationException;
use App\Model\SourceRepositoryInterface;

interface CreatorInterface
{
    public function createsFor(OriginSourceInterface $origin): bool;

    /**
     * @param array<string, string> $parameters
     *
     * @throws SourceRepositoryCreationException
     */
    public function create(
        OriginSourceInterface $origin,
        array $parameters
    ): ?SourceRepositoryInterface;
}
