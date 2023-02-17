<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Entity\SourceInterface;
use App\Exception\SourceNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

abstract class AbstractSourceResolver implements ValueResolverInterface
{
    /**
     * @return SourceInterface[]
     *
     * @throws SourceNotFoundException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (!$this->supportsArgumentType((string) $argument->getType())) {
            return [];
        }

        $sourceId = $request->attributes->get('sourceId');
        if (!is_string($sourceId)) {
            return [];
        }

        $source = $this->find($sourceId);
        if (null === $source) {
            throw new SourceNotFoundException($sourceId);
        }

        return [$source];
    }

    abstract protected function find(string $id): ?SourceInterface;

    abstract protected function supportsArgumentType(string $type): bool;
}
