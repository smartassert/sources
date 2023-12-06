<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Suite;
use App\Exception\DuplicateObjectException;
use App\Exception\EmptyEntityIdException;
use App\Exception\ModifyReadOnlyEntityException;
use App\Repository\SuiteRepository;
use App\Request\SuiteRequest;
use App\Services\Suite\Factory;
use App\Services\Suite\Mutator;
use SmartAssert\UsersSecurityBundle\Security\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

readonly class SuiteController
{
    public function __construct(
        private Factory $factory,
        private SuiteRepository $repository,
        private Mutator $mutator,
    ) {
    }

    /**
     * @throws EmptyEntityIdException
     * @throws DuplicateObjectException
     */
    #[Route(SuiteRoutes::ROUTE_SUITE_BASE, name: 'suite_create', methods: ['POST'])]
    public function create(SuiteRequest $request): Response
    {
        return new JsonResponse($this->factory->create($request));
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
     * @throws ModifyReadOnlyEntityException
     * @throws DuplicateObjectException
     */
    #[Route(SuiteRoutes::ROUTE_SUITE, name: 'suite_update', methods: ['PUT'])]
    public function update(Suite $suite, SuiteRequest $request): Response
    {
        if (null !== $suite->getDeletedAt()) {
            throw new ModifyReadOnlyEntityException($suite);
        }

        return new JsonResponse($this->mutator->update($suite, $request));
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
