<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Controller\SourceRoutes;
use App\Entity\SourceInterface;
use App\Repository\SourceRepository;
use App\Security\EntityAccessChecker;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

abstract class AbstractSourceResolver implements ValueResolverInterface
{
    public function __construct(
        private readonly SourceRepository $sourceRepository,
        private readonly EntityAccessChecker $entityAccessChecker,
    ) {}

    /**
     * @return SourceInterface[]
     *
     * @throws AccessDeniedException
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

        $source = $this->sourceRepository->find($sourceId);
        if (null === $source) {
            throw new AccessDeniedException();
        }

        $this->entityAccessChecker->denyAccessUnlessGranted($source);

        return [$source];
    }

    abstract protected function supportsArgumentType(string $type): bool;
}
