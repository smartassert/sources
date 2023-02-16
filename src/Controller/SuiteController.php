<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\SourceOriginInterface;
use App\Exception\EmptyEntityIdException;
use App\Request\SuiteRequest;
use App\Security\UserSourceAccessChecker;
use App\Services\Suite\Factory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class SuiteController
{
    public function __construct(
        private readonly UserSourceAccessChecker $userSourceAccessChecker,
        private readonly Factory $factory,
    ) {
    }

    /**
     * @throws AccessDeniedException
     * @throws EmptyEntityIdException
     */
    #[Route(SuiteRoutes::ROUTE_SUITE_BASE, name: 'user_suite_create', methods: ['POST'])]
    public function create(SourceOriginInterface $source, SuiteRequest $request): Response
    {
        $this->userSourceAccessChecker->denyAccessUnlessGranted($source);

        return new JsonResponse($this->factory->createFromSuiteRequest($source, $request));
    }
}
