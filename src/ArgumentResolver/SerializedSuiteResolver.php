<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Controller\SerializedSuiteRoutes;
use App\Entity\SerializedSuiteInterface;
use App\Repository\SerializedSuiteRepository;
use App\Security\EntityAccessChecker;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

readonly class SerializedSuiteResolver implements ValueResolverInterface
{
    public function __construct(
        private SerializedSuiteRepository $serializedSuiteRepository,
        private EntityAccessChecker $entityAccessChecker,
    ) {}

    /**
     * @return SerializedSuiteInterface[]
     *
     * @throws AccessDeniedException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (SerializedSuiteInterface::class !== $argument->getType()) {
            return [];
        }

        $suiteId = $request->attributes->get(SerializedSuiteRoutes::ATTRIBUTE_SUITE_ID);
        if (!is_string($suiteId)) {
            $suiteId = '';
        }

        $suite = $this->serializedSuiteRepository->find($suiteId);
        if (null === $suite) {
            throw new AccessDeniedException();
        }

        $this->entityAccessChecker->denyAccessUnlessGranted($suite);

        return [$suite];
    }
}
