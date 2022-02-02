<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Entity\SourceInterface;
use App\Repository\SourceRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

abstract class AbstractSourceResolver implements ArgumentValueResolverInterface
{
    public function __construct(
        protected SourceRepository $repository,
    ) {
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        return $this->supportsArgumentType((string) $argument->getType());
    }

    /**
     * @return \Generator<?SourceInterface>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): \Generator
    {
        if ($this->supports($request, $argument)) {
            $sourceId = $request->attributes->get('sourceId');

            if (is_string($sourceId)) {
                $source = $this->repository->find($sourceId);

                yield $this->doYield($source);
            }
        }
    }

    abstract protected function supportsArgumentType(string $type): bool;

    abstract protected function doYield(?SourceInterface $source): ?SourceInterface;
}
