<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Entity\SourceInterface;
use App\Repository\SourceRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class SourceResolver implements ArgumentValueResolverInterface
{
    public function __construct(
        private SourceRepository $repository,
    ) {
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        return SourceInterface::class === $argument->getType();
    }

    /**
     * @return \Generator<?SourceInterface>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): \Generator
    {
        $type = $argument->getType();

        if (SourceInterface::class === $type) {
            $sourceId = $request->attributes->get('sourceId');

            if (is_string($sourceId)) {
                yield $this->repository->find($sourceId);
            }
        }
    }
}
