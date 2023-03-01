<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\SourceInterface;
use App\Entity\SourceOriginInterface;
use App\Entity\Suite;
use App\Exception\EmptyEntityIdException;
use App\Exception\InvalidRequestException;
use App\Exception\NonUniqueSuiteLabelException;
use App\Repository\SuiteRepository;
use App\Request\CreateSuiteRequest;
use App\Request\SuiteRequest;
use App\Security\EntityAccessChecker;
use App\Services\ExceptionFactory;
use App\Services\Suite\Factory;
use App\Services\Suite\Mutator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class SuiteController
{
    public function __construct(
        private readonly EntityAccessChecker $entityAccessChecker,
        private readonly Factory $factory,
        private readonly SuiteRepository $repository,
        private readonly Mutator $mutator,
        private readonly ExceptionFactory $exceptionFactory,
    ) {
    }

    /**
     * @throws AccessDeniedException
     * @throws EmptyEntityIdException
     */
    #[Route(SuiteRoutes::ROUTE_SUITE_BASE, name: 'user_suite_create', methods: ['POST'])]
    public function create(SourceOriginInterface $source, CreateSuiteRequest $request): Response
    {
        $this->entityAccessChecker->denyAccessUnlessGranted($source);

        return new JsonResponse($this->factory->create($source, $request));
    }

    /**
     * @throws AccessDeniedException
     */
    #[Route(SuiteRoutes::ROUTE_SUITE, name: 'user_suite_get', methods: ['GET'])]
    public function get(SourceInterface $source, Suite $suite): Response
    {
        $this->entityAccessChecker->denyAccessUnlessGranted($source);
        $this->entityAccessChecker->denyAccessUnlessGranted($suite);

        return new JsonResponse($suite);
    }

    /**
     * @throws AccessDeniedException
     */
    #[Route(SuiteRoutes::ROUTE_SUITE_BASE, name: 'user_suite_list', methods: ['GET'])]
    public function list(SourceOriginInterface $source): Response
    {
        $this->entityAccessChecker->denyAccessUnlessGranted($source);

        $suites = $this->repository->findBy(
            [
                'userId' => $source->getUserId(),
                'deletedAt' => null,
            ],
            [
                'label' => 'ASC',
            ]
        );

        return new JsonResponse($suites);
    }

    /**
     * @throws AccessDeniedException
     * @throws InvalidRequestException
     */
    #[Route(SuiteRoutes::ROUTE_SUITE, name: 'user_suite_update', methods: ['POST'])]
    public function update(SourceInterface $source, Suite $suite, SuiteRequest $request): Response
    {
        $this->entityAccessChecker->denyAccessUnlessGranted($source);
        $this->entityAccessChecker->denyAccessUnlessGranted($suite);

        try {
            return new JsonResponse($this->mutator->update($suite, $request));
        } catch (NonUniqueSuiteLabelException) {
            throw $this->exceptionFactory->createInvalidRequestExceptionForNonUniqueEntityLabel(
                $request,
                $request->label,
                'suite'
            );
        }
    }
}
