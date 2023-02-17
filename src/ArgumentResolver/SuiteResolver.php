<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Controller\SourceRoutes;
use App\Controller\SuiteRoutes;
use App\Entity\Suite;
use App\Exception\EntityNotFoundException;
use App\Repository\SourceRepository;
use App\Repository\SuiteRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class SuiteResolver implements ValueResolverInterface
{
    public function __construct(
        private readonly SourceRepository $sourceRepository,
        private readonly SuiteRepository $suiteRepository,
    ) {
    }

    /**
     * @return Suite[]
     *
     * @throws EntityNotFoundException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (Suite::class !== $argument->getType()) {
            return [];
        }

        $sourceId = $request->attributes->get(SourceRoutes::ATTRIBUTE_SOURCE_ID);
        if (!is_string($sourceId)) {
            return [];
        }

        $source = $this->sourceRepository->find($sourceId);
        if (null === $source) {
            throw new EntityNotFoundException($sourceId, 'Source');
        }

        $suiteId = $request->attributes->get(SuiteRoutes::ATTRIBUTE_SUITE_ID);
        if (!is_string($suiteId)) {
            return [];
        }

        $suite = $this->suiteRepository->find($suiteId);
        if (null === $suite) {
            throw new EntityNotFoundException($suiteId, 'Suite');
        }

        return [$suite];
    }
}
