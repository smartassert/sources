<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Controller\SuiteRoutes;
use App\Entity\SerializedSuite;
use App\Exception\EntityNotFoundException;
use App\Repository\SerializedSuiteRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class SerializedSuiteResolver implements ValueResolverInterface
{
    public function __construct(
        private readonly SerializedSuiteRepository $serializedSuiteRepository,
    ) {
    }

    /**
     * @return SerializedSuite[]
     *
     * @throws EntityNotFoundException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (SerializedSuite::class !== $argument->getType()) {
            return [];
        }

        $suiteId = $request->attributes->get(SuiteRoutes::ATTRIBUTE_SUITE_ID);
        if (!is_string($suiteId)) {
            $suiteId = '';
        }

        $suite = $this->serializedSuiteRepository->find($suiteId);
        if (null === $suite) {
            throw new EntityNotFoundException($suiteId, 'Suite');
        }

        return [$suite];
    }
}
