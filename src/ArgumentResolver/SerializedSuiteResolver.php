<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Controller\SerializedSuiteRoutes;
use App\Entity\SerializedSuite;
use App\Exception\EntityNotFoundException;
use App\Repository\SerializedSuiteRepository;
use App\Security\EntityAccessChecker;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class SerializedSuiteResolver implements ValueResolverInterface
{
    public function __construct(
        private readonly SerializedSuiteRepository $serializedSuiteRepository,
        private readonly EntityAccessChecker $entityAccessChecker,
    ) {
    }

    /**
     * @return SerializedSuite[]
     *
     * @throws AccessDeniedException
     * @throws EntityNotFoundException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (SerializedSuite::class !== $argument->getType()) {
            return [];
        }

        $suiteId = $request->attributes->get(SerializedSuiteRoutes::ATTRIBUTE_SUITE_ID);
        if (!is_string($suiteId)) {
            $suiteId = '';
        }

        $suite = $this->serializedSuiteRepository->find($suiteId);
        if (null === $suite) {
            throw new EntityNotFoundException($suiteId, 'Suite');
        }

        $this->entityAccessChecker->denyAccessUnlessGranted($suite);

        return [$suite];
    }
}
