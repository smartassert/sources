<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Controller\SuiteRoutes;
use App\Entity\Suite;
use App\Exception\EntityNotFoundException;
use App\Repository\SuiteRepository;
use App\Security\EntityAccessChecker;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class SuiteResolver implements ValueResolverInterface
{
    public function __construct(
        private readonly SuiteRepository $suiteRepository,
        private readonly EntityAccessChecker $entityAccessChecker,
    ) {
    }

    /**
     * @return Suite[]
     *
     * @throws AccessDeniedException
     * @throws EntityNotFoundException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (Suite::class !== $argument->getType()) {
            return [];
        }

        $suiteId = $request->attributes->get(SuiteRoutes::ATTRIBUTE_SUITE_ID);
        if (!is_string($suiteId)) {
            $suiteId = '';
        }

        $suite = $this->suiteRepository->find($suiteId);
        if (null === $suite) {
            throw new EntityNotFoundException($suiteId, 'Suite');
        }

        $this->entityAccessChecker->denyAccessUnlessGranted($suite);

        return [$suite];
    }
}
