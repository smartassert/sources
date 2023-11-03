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
use App\Services\InvalidRequestExceptionFactory;
use App\Services\Suite\Factory;
use App\Services\Suite\Mutator;
use SmartAssert\UsersSecurityBundle\Security\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SuiteController
{
    public function __construct(
        private readonly Factory $factory,
        private readonly SuiteRepository $repository,
        private readonly Mutator $mutator,
        private readonly InvalidRequestExceptionFactory $invalidRequestExceptionFactory,
    ) {
    }

    /**
     * @throws EmptyEntityIdException
     * @throws InvalidRequestException
     */
    #[Route(SuiteRoutes::ROUTE_SUITE_BASE, name: 'suite_create', methods: ['POST'])]
    public function create(SuiteRequest $request): Response
    {
        try {
            return new JsonResponse($this->factory->create($request));
        } catch (NonUniqueEntityLabelException $exception) {
            throw $this->invalidRequestExceptionFactory->createInvalidRequestExceptionForNonUniqueEntityLabel(
                $request,
                $request->label,
                $exception->objectType,
            );
        }
    }

    #[Route(SuiteRoutes::ROUTE_SUITE, name: 'suite_get', methods: ['GET'])]
    public function get(Suite $suite): Response
    {
        return new JsonResponse($suite);
    }

    #[Route(SuiteRoutes::ROUTE_SUITES, name: 'suite_list', methods: ['GET'])]
    public function list(User $user): Response
    {
        $suites = $this->repository->findForUser($user);

        return new JsonResponse($suites);
    }

    /**
     * @throws InvalidRequestException
     * @throws ModifyReadOnlyEntityException
     */
    #[Route(SuiteRoutes::ROUTE_SUITE, name: 'suite_update', methods: ['PUT'])]
    public function update(Suite $suite, SuiteRequest $request): Response
    {
        if (null !== $suite->getDeletedAt()) {
            throw new ModifyReadOnlyEntityException($suite->id, 'suite');
        }

        try {
            return new JsonResponse($this->mutator->update($suite, $request));
        } catch (NonUniqueEntityLabelException $exception) {
            throw $this->invalidRequestExceptionFactory->createInvalidRequestExceptionForNonUniqueEntityLabel(
                $request,
                $request->label,
                $exception->objectType,
            );
        }
    }

    #[Route(SuiteRoutes::ROUTE_SUITE, name: 'suite_delete', methods: ['DELETE'])]
    public function delete(Suite $suite): Response
    {
        if (null === $suite->getDeletedAt()) {
            $this->repository->delete($suite);
        }

        return new JsonResponse($suite);
    }
}
