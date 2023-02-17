<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Controller\SourceRoutes;
use App\Entity\SourceInterface;
use App\Exception\EntityNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

abstract class AbstractSourceResolver implements ValueResolverInterface
{
    /**
     * @return SourceInterface[]
     *
     * @throws EntityNotFoundException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (!$this->supportsArgumentType((string) $argument->getType())) {
            return [];
        }

        $sourceId = $request->attributes->get(SourceRoutes::ATTRIBUTE_SOURCE_ID);
        if (!is_string($sourceId)) {
            return [];
        }

        $source = $this->find($sourceId);
        if (null === $source) {
            throw new EntityNotFoundException($sourceId, 'Source');
        }

        return [$source];
    }

    abstract protected function find(string $id): ?SourceInterface;

    abstract protected function supportsArgumentType(string $type): bool;
}
