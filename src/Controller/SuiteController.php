<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Suite;
use App\Exception\EmptyEntityIdException;
use App\Exception\InvalidRequestException;
use App\Exception\ModifyReadOnlyEntityException;
use App\Exception\NonUniqueEntityLabelException;
use App\Repository\SuiteRepository;
use App\Request\SuiteRequest;
use App\Security\EntityAccessChecker;
use App\Services\ExceptionFactory;
use App\Services\Suite\Factory;
use App\Services\Suite\Mutator;
use SmartAssert\UsersSecurityBundle\Security\User;
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
     * @throws InvalidRequestException
     */
    #[Route(SuiteRoutes::ROUTE_SUITE_BASE, name: 'user_suite_create', methods: ['POST'])]
    public function create(SuiteRequest $request): Response
    {
        $this->entityAccessChecker->denyAccessUnlessGranted($request->source);

        try {
            return new JsonResponse($this->factory->create($request));
        } catch (NonUniqueEntityLabelException) {
            throw $this->exceptionFactory->createInvalidRequestExceptionForNonUniqueEntityLabel(
                $request,
                $request->label,
                'suite'
            );
        }
    }

    /**
     * @throws AccessDeniedException
     */
    #[Route(SuiteRoutes::ROUTE_SUITE, name: 'user_suite_get', methods: ['GET'])]
    public function get(Suite $suite): Response
    {
        $this->entityAccessChecker->denyAccessUnlessGranted($suite);

        return new JsonResponse($suite);
    }

    /**
     * @throws AccessDeniedException
     */
    #[Route(SuiteRoutes::ROUTE_SUITES, name: 'user_suite_list', methods: ['GET'])]
    public function list(User $user): Response
    {
        $suites = $this->repository->findForUser($user);

        return new JsonResponse($suites);
    }

    /**
     * @throws AccessDeniedException
     * @throws InvalidRequestException
     * @throws ModifyReadOnlyEntityException
     */
    #[Route(SuiteRoutes::ROUTE_SUITE, name: 'user_suite_update', methods: ['POST'])]
    public function update(Suite $suite, SuiteRequest $request): Response
    {
        $this->entityAccessChecker->denyAccessUnlessGranted($suite);

        if (null !== $suite->getDeletedAt()) {
            throw new ModifyReadOnlyEntityException($suite->id, 'suite');
        }

        try {
            return new JsonResponse($this->mutator->update($suite, $request));
        } catch (NonUniqueEntityLabelException) {
            throw $this->exceptionFactory->createInvalidRequestExceptionForNonUniqueEntityLabel(
                $request,
                $request->label,
                'suite'
            );
        }
    }

    /**
     * @throws AccessDeniedException
     */
    #[Route(SuiteRoutes::ROUTE_SUITE, name: 'user_suite_delete', methods: ['DELETE'])]
    public function delete(Suite $suite): Response
    {
        $this->entityAccessChecker->denyAccessUnlessGranted($suite);

        if (null === $suite->getDeletedAt()) {
            $this->repository->delete($suite);
        }

        return new JsonResponse($suite);
    }
}
