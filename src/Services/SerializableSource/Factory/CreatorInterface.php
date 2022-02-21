<?php

declare(strict_types=1);

namespace App\Services\SerializableSource\Factory;

use App\Entity\OriginSourceInterface;
use App\Exception\SerializableSourceCreationException;
use App\Model\SerializableSourceInterface;

interface CreatorInterface
{
    public function createsFor(OriginSourceInterface $origin): bool;

    /**
     * @param array<string, string> $parameters
     *
     * @throws SerializableSourceCreationException
     */
    public function create(
        OriginSourceInterface $origin,
        array $parameters
    ): ?SerializableSourceInterface;
}
